<?php

namespace App\Console\Commands;

use App\OfficialRatings\Models\OfficialRating;
use App\OfficialRatings\Models\OfficialRatingPlayer;
use App\OfficialRatings\Services\OfficialRatingService;
use App\Tournaments\Models\Tournament;
use App\Tournaments\Models\TournamentPlayer;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateOfficialRatingCommand extends Command
{
    protected $signature = 'rating:recalculate
                           {rating? : The ID of the official rating to recalculate}
                           {--all : Recalculate all active official ratings}
                           {--dry-run : Show what would be changed without making changes}
                           {--force : Force recalculation without confirmation}';

    protected $description = 'Recalculate official rating from existing tournament_players data.';

    private OfficialRatingService $ratingService;

    public function __construct(OfficialRatingService $ratingService)
    {
        parent::__construct();
        $this->ratingService = $ratingService;
    }

    public function handle(): int
    {
        $ratingId = $this->argument('rating');
        $all = $this->option('all');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if (!$ratingId && !$all) {
            $this->error('Please specify either a rating ID or use --all flag');
            return 1;
        }

        if ($ratingId && $all) {
            $this->error('Cannot specify both rating ID and --all flag');
            return 1;
        }

        try {
            if ($all) {
                return $this->recalculateAllRatings($dryRun, $force);
            } else {
                return $this->recalculateSpecificRating($ratingId, $dryRun, $force);
            }
        } catch (Exception $e) {
            $this->error('Failed to recalculate ratings: '.$e->getMessage());
            return 1;
        }
    }

    private function recalculateAllRatings(bool $dryRun, bool $force): int
    {
        $ratings = OfficialRating::where('is_active', true)->get();

        if ($ratings->isEmpty()) {
            $this->info('No active official ratings found.');
            return 0;
        }

        $this->info("Found {$ratings->count()} active official ratings:");
        foreach ($ratings as $rating) {
            $this->line("- {$rating->name} (ID: {$rating->id})");
        }

        if (!$force && !$dryRun) {
            if (!$this->confirm('Do you want to recalculate all these ratings?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $totalUpdated = 0;
        foreach ($ratings as $rating) {
            $this->info("\nProcessing rating: {$rating->name}");
            $updated = $this->processRating($rating, $dryRun);
            $totalUpdated += $updated;
        }

        $action = $dryRun ? 'would be updated' : 'updated';
        $this->info("\nTotal players {$action}: {$totalUpdated}");

        return 0;
    }

    private function processRating(OfficialRating $rating, bool $dryRun): int
    {
        $this->line("Game: {$rating->game->name}");
        $this->line("Initial Rating: {$rating->initial_rating}");

        // Get all tournaments associated with this rating
        $tournaments = $rating
            ->tournaments()
            ->where('status', 'completed')
            ->where('is_counting', true)
            ->orderBy('end_date')
            ->get()
        ;

        if ($tournaments->isEmpty()) {
            $this->warn('No completed tournaments found for this rating.');
            return 0;
        }

        $this->info("Found {$tournaments->count()} completed tournaments");

        // Get all players who participated in these tournaments
        $playerIds = TournamentPlayer::whereIn('tournament_id', $tournaments->pluck('id'))
            ->whereNotNull('position')
            ->where('status', 'confirmed')
            ->distinct()
            ->pluck('user_id')
        ;

        $this->info("Found {$playerIds->count()} unique players");

        if ($dryRun) {
            return $this->simulateRecalculation($rating, $tournaments, $playerIds);
        } else {
            return $this->performRecalculation($rating, $tournaments, $playerIds);
        }
    }

    private function simulateRecalculation(OfficialRating $rating, $tournaments, $playerIds): int
    {
        $this->info("\n🔍 DRY RUN - Simulating changes:");

        $updatedCount = 0;
        $bar = $this->output->createProgressBar($playerIds->count());
        $bar->start();

        foreach ($playerIds as $userId) {
            $currentPlayer = $rating->getPlayerRating($userId);
            $simulatedPlayer = $this->calculatePlayerRating($rating, $tournaments, $userId);

            if ($currentPlayer) {
                if ($this->hasPlayerChanged($currentPlayer, $simulatedPlayer)) {
                    $this->showPlayerChanges($currentPlayer, $simulatedPlayer);
                    $updatedCount++;
                }
            } else {
                $this->line("\n➕ New player would be added:");
                $this->line("   User ID: {$simulatedPlayer['user_id']}");
                $this->line("   Rating: {$simulatedPlayer['rating_points']}");
                $this->line("   Tournaments: {$simulatedPlayer['tournaments_played']}");
                $updatedCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        return $updatedCount;
    }

    private function calculatePlayerRating(OfficialRating $rating, $tournaments, int $userId): array
    {
        $tournamentRecords = [];
        $totalRatingPoints = $rating->initial_rating;
        $tournamentsPlayed = 0;
        $tournamentsWon = 0;
        $lastTournamentAt = null;

        foreach ($tournaments as $tournament) {
            $tournamentPlayer = TournamentPlayer::where('tournament_id', $tournament->id)
                ->where('user_id', $userId)
                ->whereNotNull('position')
                ->where('status', 'confirmed')
                ->first()
            ;

            if (!$tournamentPlayer) {
                continue;
            }

            // Calculate rating points with coefficient
            $basePoints = $tournamentPlayer->rating_points;
            $coefficient = $tournament->pivot->rating_coefficient;
            $adjustedPoints = (int) ($basePoints * $coefficient);

            $won = $tournamentPlayer->position === 1;

            $tournamentRecords[] = [
                'tournament_id'   => $tournament->id,
                'rating_points'   => $adjustedPoints,
                'tournament_date' => $tournament->end_date->format('Y-m-d'),
                'won'             => $won,
                'added_at'        => now()->format('Y-m-d H:i:s'),
            ];

            $totalRatingPoints += $adjustedPoints;
            $tournamentsPlayed++;
            if ($won) {
                $tournamentsWon++;
            }

            if (!$lastTournamentAt || $tournament->end_date > $lastTournamentAt) {
                $lastTournamentAt = $tournament->end_date;
            }
        }

        return [
            'user_id'            => $userId,
            'rating_points'      => $totalRatingPoints,
            'tournaments_played' => $tournamentsPlayed,
            'tournaments_won'    => $tournamentsWon,
            'last_tournament_at' => $lastTournamentAt,
            'tournament_records' => $tournamentRecords,
        ];
    }

    private function hasPlayerChanged(OfficialRatingPlayer $current, array $simulated): bool
    {
        return $current->rating_points !== $simulated['rating_points'] ||
            $current->tournaments_played !== $simulated['tournaments_played'] ||
            $current->tournaments_won !== $simulated['tournaments_won'] ||
            count($current->tournament_records ?? []) !== count($simulated['tournament_records']);
    }

    private function showPlayerChanges(OfficialRatingPlayer $current, array $simulated): void
    {
        $user = $current->user;
        $this->line("\n🔄 Changes for {$user->firstname} {$user->lastname} (ID: {$user->id}):");

        if ($current->rating_points !== $simulated['rating_points']) {
            $this->line("   Rating: {$current->rating_points} → {$simulated['rating_points']}");
        }

        if ($current->tournaments_played !== $simulated['tournaments_played']) {
            $this->line("   Tournaments: {$current->tournaments_played} → {$simulated['tournaments_played']}");
        }

        if ($current->tournaments_won !== $simulated['tournaments_won']) {
            $this->line("   Wins: {$current->tournaments_won} → {$simulated['tournaments_won']}");
        }

        $currentRecordsCount = count($current->tournament_records ?? []);
        $simulatedRecordsCount = count($simulated['tournament_records']);
        if ($currentRecordsCount !== $simulatedRecordsCount) {
            $this->line("   Tournament Records: {$currentRecordsCount} → {$simulatedRecordsCount}");
        }
    }

    private function performRecalculation(OfficialRating $rating, $tournaments, $playerIds): int
    {
        $this->info("\n💾 Performing actual recalculation:");

        return DB::transaction(function () use ($rating, $tournaments, $playerIds) {
            $updatedCount = 0;
            $bar = $this->output->createProgressBar($playerIds->count());
            $bar->start();

            foreach ($playerIds as $userId) {
                $playerData = $this->calculatePlayerRating($rating, $tournaments, $userId);

                // Get or create player
                $player = $rating->getPlayerRating($userId);
                if (!$player) {
                    $player = OfficialRatingPlayer::create([
                        'official_rating_id' => $rating->id,
                        'user_id'            => $userId,
                        'rating_points'      => $rating->initial_rating,
                        'position'           => 0,
                        'tournaments_played' => 0,
                        'tournaments_won'    => 0,
                        'is_active'          => true,
                        'tournament_records' => [],
                    ]);
                }

                // Clear existing tournament records
                $player->update([
                    'rating_points'      => $rating->initial_rating,
                    'tournaments_played' => 0,
                    'tournaments_won'    => 0,
                    'tournament_records' => [],
                    'last_tournament_at' => null,
                ]);

                // Add all tournament records
                foreach ($playerData['tournament_records'] as $record) {
                    $tournament = Tournament::find($record['tournament_id']);
                    $player->addTournament(
                        $record['tournament_id'],
                        $record['rating_points'],
                        $tournament->end_date,
                        $record['won'],
                    );
                }

                $updatedCount++;
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();

            // Recalculate positions
            $this->info('🔢 Recalculating positions...');
            $this->ratingService->recalculatePositions($rating);

            return $updatedCount;
        });
    }

    private function recalculateSpecificRating($ratingId, bool $dryRun, bool $force): int
    {
        $rating = OfficialRating::find($ratingId);

        if (!$rating) {
            $this->error("Official rating with ID {$ratingId} not found.");
            return 1;
        }

        $this->info("Processing rating: {$rating->name} (ID: {$rating->id})");

        if (!$force && !$dryRun) {
            if (!$this->confirm('Do you want to recalculate this rating?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $updated = $this->processRating($rating, $dryRun);

        $action = $dryRun ? 'would be updated' : 'updated';
        $this->info("Players {$action}: {$updated}");

        return 0;
    }
}
