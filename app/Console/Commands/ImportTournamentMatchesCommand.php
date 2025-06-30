<?php

namespace App\Console\Commands;

use App\Core\Models\User;
use App\Tournaments\Enums\EliminationRound;
use App\Tournaments\Models\Tournament;
use App\Tournaments\Models\TournamentMatch;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use ValueError;

class ImportTournamentMatchesCommand extends Command
{
    protected $signature = 'import:tournament-matches {file}';
    protected $description = 'Import tournament matches data from Excel file';

    /**
     * Map of match code patterns to elimination rounds
     * FM = Finals Match
     * SFM = Semi-Finals Match
     * QFM = Quarter-Finals Match
     * R[N]M = Round of [2^(N+3)] Match (e.g., R1M = Round of 16)
     * GR[A-I]M = Group [A-I] Match
     */
    private array $roundMap = [
        'FM'   => 'finals',
        'SFM'  => 'semifinals',
        'QFM'  => 'quarterfinals',
        'R1M'  => 'round_16',
        'R2M'  => 'round_32',
        'R3M'  => 'round_64',
        'R4M'  => 'round_128',
        'R5M'  => 'round_256',
        'R6M'  => 'round_512',
        'R7M'  => 'round_1024',
        'R8M'  => 'round_2048',
        'GRAM' => 'groups',
        'GRBM' => 'groups',
        'GRCM' => 'groups',
        'GRDM' => 'groups',
        'GREM' => 'groups',
        'GRFM' => 'groups',
        'GRGM' => 'groups',
        'GRHM' => 'groups',
        'GRIM' => 'groups',
    ];

    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $this->info('Starting tournament matches import...');

        DB::beginTransaction();

        try {
            // Load Excel file
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $data = $worksheet->toArray();

            // Remove header row
            $headers = array_shift($data);

            $this->info("Found ".count($data)." matches to import");

            $importedCount = 0;
            $skippedCount = 0;
            $errors = [];

            // Create progress bar
            $bar = $this->output->createProgressBar(count($data));
            $bar->start();

            foreach ($data as $index => $row) {
                try {
                    // Skip empty rows
                    if (empty(array_filter($row))) {
                        $skippedCount++;
                        $bar->advance();
                        continue;
                    }

                    // Map row data to associative array
                    $matchData = array_combine($headers, $row);

                    // Validate required fields
                    if (!$this->validateMatchData($matchData, $index + 2)) {
                        $skippedCount++;
                        $bar->advance();
                        continue;
                    }

                    // Parse match code
                    $parsedData = $this->parseMatchCode($matchData['match_code']);

                    // Check if tournament exists
                    $tournament = Tournament::find($matchData['tournament_id']);
                    if (!$tournament) {
                        $errors[] = "Row ".($index + 2).": Tournament ID {$matchData['tournament_id']} not found";
                        $skippedCount++;
                        $bar->advance();
                        continue;
                    }

                    // Check if players exist
                    $player1 = User::find($matchData['player_id1']);
                    $player2 = User::find($matchData['player_id2']);

                    if (!$player1 || !$player2) {
                        $errors[] = "Row ".($index + 2).": One or both players not found";
                        $skippedCount++;
                        $bar->advance();
                        continue;
                    }

                    // Determine winner
                    $winnerId = null;
                    if ($matchData['winner_player_id']) {
                        $winnerId = $matchData['winner_player_id'];
                    }

                    // For group stage matches, extract group info from match code
                    $adminNotes = null;
                    if ($parsedData['stage'] === 'group' && preg_match('/GR([A-Z])M/', $matchData['match_code'],
                            $groupMatch)) {
                        $adminNotes = 'Group '.$groupMatch[1];
                    }

                    // Create or update match
                    $match = TournamentMatch::updateOrCreate(
                        [
                            'tournament_id' => $matchData['tournament_id'],
                            'match_code'    => $matchData['match_code'],
                        ],
                        [
                            'stage'            => $parsedData['stage'],
                            'round'            => $parsedData['round'],
                            'bracket_position' => $parsedData['position'],
                            'bracket_side'     => $parsedData['bracket_side'],
                            'player1_id'       => $matchData['player_id1'],
                            'player2_id'       => $matchData['player_id2'],
                            'player1_score'    => (int) $matchData['player1_score'],
                            'player2_score'    => (int) $matchData['player2_score'],
                            'winner_id'        => $winnerId,
                            'races_to'         => $this->determineRacesTo($matchData),
                            'status'           => 'completed',
                            'completed_at'     => $tournament->end_date,
                            'admin_notes'      => $adminNotes,
                        ],
                    );

                    $importedCount++;

                } catch (Exception $e) {
                    $errors[] = "Row ".($index + 2).": ".$e->getMessage();
                    $skippedCount++;
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            // Show summary
            $this->info("Import completed!");
            $this->info("- Imported: {$importedCount} matches");
            $this->info("- Skipped: {$skippedCount} rows");

            if (!empty($errors)) {
                $this->warn("\nErrors encountered:");
                foreach (array_slice($errors, 0, 10) as $error) {
                    $this->line("  - {$error}");
                }

                if (count($errors) > 10) {
                    $this->line("  ... and ".(count($errors) - 10)." more errors");
                }
            }

            // Ask for confirmation
            if ($this->confirm('Do you want to commit these changes?')) {
                DB::commit();
                $this->info('Changes committed successfully!');
            } else {
                DB::rollBack();
                $this->info('Changes rolled back.');
            }

        } catch (Exception $e) {
            DB::rollBack();
            $this->error('Import failed: '.$e->getMessage());
            $this->error('Stack trace: '.$e->getTraceAsString());
            return 1;
        }

        return 0;
    }

    private function validateMatchData(array $data, int $rowNumber): bool
    {
        $required = ['tournament_id', 'player_id1', 'player_id2', 'match_code'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                $this->warn("Row {$rowNumber}: Missing required field '{$field}'");
                return false;
            }
        }

        return true;
    }

    private function parseMatchCode(string $matchCode): array
    {
        $parts = explode('_', $matchCode);

        $result = [
            'stage'        => 'bracket',
            'round'        => null,
            'bracket_side' => 'upper',
            'position'     => null,
        ];

        // Determine bracket side and stage
        if (isset($parts[0])) {
            switch ($parts[0]) {
                case 'UB':
                    $result['bracket_side'] = 'upper';
                    $result['stage'] = 'bracket';
                    break;
                case 'LB':
                    $result['bracket_side'] = 'lower';
                    $result['stage'] = 'bracket';
                    break;
                case 'GR':
                    $result['stage'] = 'group';
                    $result['bracket_side'] = null;
                    break;
            }
        }

        // Extract round and position
        if (isset($parts[1])) {
            // Extract the round type (e.g., FM, SFM, R1M)
            preg_match('/^([A-Z]+M?)(\d+)?$/', $parts[1], $matches);

            if (isset($matches[1])) {
                $roundType = $matches[1];

                // Map to round name
                if (isset($this->roundMap[$roundType])) {
                    $result['round'] = $this->roundMap[$roundType];

                    // Validate the round is a valid enum value
                    try {
                        EliminationRound::from($result['round']);
                    } catch (ValueError $e) {
                        throw new Exception("Invalid round value: {$result['round']}");
                    }
                }

                // Extract position number
                if (isset($matches[2])) {
                    $result['position'] = (int) $matches[2];
                }
            }
        }

        return $result;
    }

    private function determineRacesTo(array $matchData): int
    {
        // Calculate races to based on scores
        $maxScore = max((int) $matchData['player1_score'], (int) $matchData['player2_score']);

        // Common races to values in pool
        $commonRaces = [3, 4, 5, 6, 7, 8, 9, 10, 11, 13, 15, 17, 19, 21];

        foreach ($commonRaces as $races) {
            if ($maxScore <= $races) {
                return $races;
            }
        }

        // Default for high scores
        return 21;
    }
}
