<?php

namespace App\Console\Commands;

use App\Core\Models\City;
use App\Core\Models\Country;
use App\Core\Models\Game;
use App\Core\Models\User;
use App\OfficialRatings\Models\OfficialRating;
use App\OfficialRatings\Models\OfficialRatingPlayer;
use App\OfficialRatings\Services\OfficialRatingService;
use App\Tournaments\Models\Tournament;
use App\Tournaments\Models\TournamentPlayer;
use DateTime;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportTournamentsCommand extends Command
{
    protected $signature = 'import:tournaments {file}';
    protected $description = 'Import tournaments data from Excel file';
    private OfficialRatingService $ratingService;
    private array $gameMap = [];
    private array $cityMap = [];
    private array $countryMap = [];
    private OfficialRating $rating;

    public function __construct(OfficialRatingService $ratingService)
    {
        parent::__construct();
        $this->ratingService = $ratingService;
    }

    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $this->info('Starting import process...');

        DB::beginTransaction();

        try {
            // Load Excel file
            $spreadsheet = IOFactory::load($filePath);

            // Get data from all sheets
            $tournamentsData = $this->getSheetData($spreadsheet, 'tournaments');
            $playersData = $this->getSheetData($spreadsheet, 'players');
            $tournamentPlayersData = $this->getSheetData($spreadsheet, 'tournaments_players');

            $this->info("Loaded data:");
            $this->info("- Tournaments: ".count($tournamentsData));
            $this->info("- Players: ".count($playersData));
            $this->info("- Tournament Results: ".count($tournamentPlayersData));

            // Step 1: Create official rating
            $this->createOfficialRating();

            // Step 2: Create/get games
            $this->createGames();

            // Step 3: Create countries and cities
            $this->createGeography($playersData);

            // Step 4: Create users
            $this->createUsers($playersData);

            // Step 5: Create tournaments
            $this->createTournaments($tournamentsData);

            // Step 6: Create tournament players and results
            $this->createTournamentResults($tournamentPlayersData);

            // Step 7: Update official rating from tournaments using new system
            $this->updateOfficialRatingWithNewSystem();

            DB::commit();

            $this->info('Import completed successfully!');

        } catch (Exception $e) {
            DB::rollBack();
            $this->error('Import failed: '.$e->getMessage());
            $this->error('Stack trace: '.$e->getTraceAsString());
            return 1;
        }

        return 0;
    }

    private function getSheetData($spreadsheet, $sheetName): array
    {
        $worksheet = $spreadsheet->getSheetByName($sheetName);
        if (!$worksheet) {
            throw new Exception("Sheet '{$sheetName}' not found");
        }

        $data = $worksheet->toArray();
        $headers = array_shift($data); // Remove header row

        $result = [];
        foreach ($data as $row) {
            if (empty(array_filter($row))) {
                continue;
            } // Skip empty rows

            $result[] = array_combine($headers, $row);
        }

        return $result;
    }

    private function createOfficialRating()
    {
        $this->info('Creating official rating...');

        // Find a pool game for the rating
        $poolGame = Game::where('name', 'LIKE', '%Пул%')->first();
        if (!$poolGame) {
            $poolGame = Game::where('type', 'pool')->first();
        }

        if (!$poolGame) {
            throw new Exception('No pool game found for rating');
        }

        $this->rating = OfficialRating::create([
            'name'               => 'Lviv Pool Rating',
            'description' => 'Офіційний рейтинг з пулу м.Львів',
            'game_type'   => $poolGame->type,
            'is_active'          => true,
            'initial_rating'     => 0,
            'calculation_method' => 'tournament_points',
        ]);

        $this->info("Created rating: {$this->rating->name} (ID: {$this->rating->id})");
    }

    private function createGames()
    {
        $this->info('Creating games...');

        $games = [
            'pool-8'           => 'Пул 8',
            'pool-9'           => 'Пул 9',
            'pool-10'          => 'Пул 10',
            'pool-14+1'        => 'Пул 14+1',
            'pool-8,9,10,14+1' => 'Пул 8,9,10,14+1',
            'killer_pool'      => 'Killer Pool',
            'pool-8,9,10'      => 'Пул Багатоборство',
        ];

        foreach ($games as $type => $name) {
            $game = Game::firstOrCreate(
                ['name' => $name],
                [
                    'type'           => 'pool',
                    'is_multiplayer' => false,
                    'rules'          => null,
                ],
            );

            $this->gameMap[$type] = $game->id;
            $this->info("Game: {$name} (ID: {$game->id})");
        }
    }

    private function createGeography(array $playersData)
    {
        $this->info('Creating countries and cities...');

        $countries = [];
        $cities = [];

        foreach ($playersData as $player) {
            $countries[$player['COUNTRY']] = true;
            $cities[$player['COUNTRY']][$player['CITY']] = true;
        }

        foreach ($countries as $countryName => $v) {
            $country = Country::firstOrCreate(['name' => $countryName]);
            $this->countryMap[$countryName] = $country->id;
            $this->info("Country: {$countryName} (ID: {$country->id})");

            if (isset($cities[$countryName])) {
                foreach ($cities[$countryName] as $cityName => $v) {
                    $city = City::firstOrCreate(
                        ['name' => $cityName, 'country_id' => $country->id],
                    );
                    $this->cityMap[$cityName] = $city->id;
                    $this->info("City: {$cityName} (ID: {$city->id})");
                }
            }
        }
    }

    private function createUsers(array $playersData)
    {
        $this->info('Creating users...');

        $userInsert = [];
        foreach ($playersData as $player) {
            $email = $this->generateEmail($player['FIRSTNAME'], $player['LASTNAME']);
            $phone = $this->generatePhone($player['ID']);

            $userInsert[] = [
                'id'                => $player['ID'],
                'firstname'         => $player['FIRSTNAME'],
                'lastname'          => $player['LASTNAME'],
                'phone'             => $phone,
                'email'             => $email,
                'password'          => Hash::make('password123'),
                'sex'               => $player['SEX'] ?? 'M',
                'home_city_id'      => $this->cityMap[$player['CITY']] ?? null,
                'is_active'         => true,
                'email_verified_at' => now(),
            ];

            $this->info("User: {$player['FIRSTNAME']} {$player['LASTNAME']} (ID: {$player['ID']})");
        }

        User::insert($userInsert);
    }

    private function generateEmail(string $firstname, string $lastname): string
    {
        $email = strtolower(
            $this->transliterate($firstname).'.'.
            $this->transliterate($lastname).
            '@lvivpool.com',
        );

        // Check if email exists and add number if needed
        $counter = 1;
        $originalEmail = $email;
        while (User::where('email', $email)->exists()) {
            $email = str_replace('@lvivpool.com', $counter.'@lvivpool.com', $originalEmail);
            $counter++;
        }

        return $email;
    }

    private function transliterate(string $text): string
    {
        $map = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'h', 'ґ' => 'g',
            'д' => 'd', 'е' => 'e', 'є' => 'ie', 'ж' => 'zh', 'з' => 'z',
            'и' => 'y', 'і' => 'i', 'ї' => 'i', 'й' => 'i', 'к' => 'k',
            'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p',
            'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f',
            'х' => 'kh', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch',
            'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'iu', 'я' => 'ia',
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'H', 'Ґ' => 'G',
            'Д' => 'D', 'Е' => 'E', 'Є' => 'Ie', 'Ж' => 'Zh', 'З' => 'Z',
            'И' => 'Y', 'І' => 'I', 'Ї' => 'I', 'Й' => 'I', 'К' => 'K',
            'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O', 'П' => 'P',
            'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F',
            'Х' => 'Kh', 'Ц' => 'Ts', 'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Shch',
            'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'Iu', 'Я' => 'Ia',
        ];

        return strtr($text, $map);
    }

    private function generatePhone(int $id): string
    {
        return '+38099'.str_pad($id, 7, '0', STR_PAD_LEFT);
    }

    private function createTournaments(array $tournamentsData)
    {
        $this->info('Creating tournaments...');

        $tournamentInsert = [];
        foreach ($tournamentsData as $tournament) {
            $gameId = $this->gameMap[$tournament['DISCIPLINE']] ?? null;
            if (!$gameId) {
                $this->warn("Game not found for discipline: {$tournament['DISCIPLINE']}");
                continue;
            }

            $startDate = $this->parseDate($tournament['START_DATE']);
            $endDate = $this->parseDate($tournament['FINISH_DATE']);

            $tournamentInsert[] = [
                'id'         => $tournament['ID'],
                'name'       => $tournament['NAME'],
                'game_id'    => $gameId,
                'status'     => 'completed',
                'start_date' => $startDate,
                'end_date'   => $endDate,
                'city_id'    => $this->cityMap['Львів'] ?? null,
                'organizer'  => 'Imported',
                'is_old'     => (bool) $tournament['IS_OLD'],
            ];

            $this->info("Tournament: {$tournament['NAME']} (ID: {$tournament['ID']})");
        }

        Tournament::insert($tournamentInsert);
        // Link tournament to official rating

        foreach ($tournamentsData as $tournament) {
            $this->rating->tournaments()->attach($tournament['ID'], [
                'rating_coefficient' => 1.0,
                'is_counting'        => true,
            ]);
        }
    }

    private function parseDate($dateString): string
    {
        if ($dateString instanceof DateTime) {
            return $dateString->format('Y-m-d');
        }

        $timestamp = strtotime($dateString);
        if ($timestamp === false) {
            return now()->format('Y-m-d');
        }

        return date('Y-m-d', $timestamp);
    }

    private function createTournamentResults(array $tournamentPlayersData)
    {
        $this->info('Creating tournament results...');

        // Group by tournament to calculate positions
        $tournamentResults = [];
        foreach ($tournamentPlayersData as $result) {
            $tournamentResults[$result['TOURNAMENT_ID']][] = $result;
        }

        foreach ($tournamentResults as $tournamentId => $results) {
            $tournament = Tournament::find($tournamentId);
            if (!$tournament) {
                $this->warn("Tournament not found: {$tournamentId}");
                continue;
            }

            foreach ($results as $index => $result) {
                $userId = $result['PLAYER_ID'];
                if (!$userId) {
                    $this->warn("User not found: {$result['PLAYER_ID']}");
                    continue;
                }

                $position = $index + 1;

                TournamentPlayer::create([
                    'tournament_id' => $tournamentId,
                    'user_id'       => $userId,
                    'position'      => $position,
                    'rating_points' => $result['RATING'],
                    'status'        => 'confirmed',
                    'registered_at' => $tournament->start_date,
                ]);

                // Add player to official rating if not exists
                if (!$this->rating->hasPlayer($userId)) {
                    OfficialRatingPlayer::create([
                        'official_rating_id' => $this->rating->id,
                        'user_id'            => $userId,
                        'rating_points'      => $this->rating->initial_rating,
                        'position'           => 0,
                        'is_active'          => true,
                        'tournament_records' => [],
                    ]);
                }
            }

            $this->info("Added results for tournament: {$tournament->name}");
        }
    }

    private function updateOfficialRatingWithNewSystem()
    {
        $this->info('Updating official rating from tournaments using new system...');

        $tournaments = $this->rating->tournaments()->orderBy('end_date')->get();
        $updatedCount = 0;

        foreach ($tournaments as $tournament) {
            try {
                $count = $this->ratingService->updateRatingFromTournament($this->rating, $tournament);
                $updatedCount += $count;
                $this->info("Updated rating from tournament: {$tournament->name} ({$count} players)");
            } catch (Exception $e) {
                $this->warn("Failed to update rating from tournament {$tournament->name}: ".$e->getMessage());
            }
        }

        // Final position recalculation
        $this->ratingService->recalculatePositions($this->rating);

        $this->info("Rating update completed. Total players updated: {$updatedCount}");

        // Show integrity report
        $report = $this->ratingService->getRatingIntegrityReport($this->rating);
        $this->info("Data integrity check: {$report['players_with_issues']} issues found out of {$report['total_players']} players");

        if ($report['players_with_issues'] > 0) {
            $this->warn("There are data integrity issues. Consider running 'recalculate-from-records' command.");
        }
    }
}
