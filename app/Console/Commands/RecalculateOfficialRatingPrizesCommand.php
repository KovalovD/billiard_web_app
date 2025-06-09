<?php

namespace App\Console\Commands;

use App\OfficialRatings\Models\OfficialRating;
use App\OfficialRatings\Models\OfficialRatingPlayer;
use App\OfficialRatings\Services\OfficialRatingService;
use App\Tournaments\Models\TournamentPlayer;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateOfficialRatingPrizesCommand extends Command
{
    protected $signature = 'rating:recalculate-prizes
                           {rating? : The ID of the official rating to recalculate prizes for}
                           {--all : Recalculate prizes for all active official ratings}
                           {--dry-run : Show what would be changed without making changes}
                           {--force : Force recalculation without confirmation}';

    protected $description = 'Recalculate official rating prizes from existing tournament_players data.';

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
            $this->error('Failed to recalculate prizes: '.$e->getMessage());
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
            if (!$this->confirm('Do you want to recalculate prizes for all these ratings?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $totalUpdated = 0;
        foreach ($ratings as $rating) {
            $this->info("\nProcessing rating: {$rating->name}");
            $updated = $this->processRating($rating, $dryRun);
            $totalUpdated += $updated;
            $this->ratingService->recalculatePositions($rating);
        }

        $action = $dryRun ? 'would be updated' : 'updated';
        $this->info("\nTotal players {$action}: {$totalUpdated}");

        return 0;
    }

    private function processRating(OfficialRating $rating, bool $dryRun): int
    {
        $this->line("Game Type Name: {$rating->game_type_name}");

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

        // Get all players in the rating
        $players = $rating->players()->get();
        $this->info("Found {$players->count()} players in the rating");

        if ($dryRun) {
            return $this->simulateRecalculation($rating, $tournaments, $players);
        } else {
            return $this->performRecalculation($rating, $tournaments, $players);
        }
    }

    private function simulateRecalculation(OfficialRating $rating, $tournaments, $players): int
    {
        $this->info("\n🔍 DRY RUN - Simulating changes:");

        $updatedCount = 0;
        $bar = $this->output->createProgressBar($players->count());
        $bar->start();

        foreach ($players as $player) {
            if (empty($player->tournament_records)) {
                $this->line("\n🔄 Would reset values for player {$player->user->firstname} {$player->user->lastname} (ID: {$player->user_id}):");
                $this->line("   Current values:");
                $this->line("   - Rating Points: {$player->rating_points}");
                $this->line("   - Prize Amount: {$player->total_prize_amount}");
                $this->line("   - Killer Pool Prize: {$player->total_killer_pool_prize_amount}");
                $updatedCount++;
            } else {
                $simulatedPlayer = $this->calculatePlayerPrizes($rating, $tournaments, $player->user_id);
                if ($this->hasPlayerPrizesChanged($player, $simulatedPlayer)) {
                    $this->showPlayerPrizeChanges($player, $simulatedPlayer);
                    $updatedCount++;
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        return $updatedCount;
    }

    private function calculatePlayerPrizes(OfficialRating $rating, $tournaments, int $userId): array
    {
        $totalPrizeAmount = 0;
        $totalBonusAmount = 0;
        $totalAchievementAmount = 0;
        $totalKillerPoolPrizeAmount = 0;

        // Get all tournament players for this user in one query
        $tournamentPlayers = TournamentPlayer::whereIn('tournament_id', $tournaments->pluck('id'))
            ->where('user_id', $userId)
            ->whereNotNull('position')
            ->where('status', 'confirmed')
            ->with(['tournament.game'])
            ->get()
        ;

        foreach ($tournamentPlayers as $tournamentPlayer) {
            if ($tournamentPlayer->tournament->game->is_multiplayer) {
                $totalKillerPoolPrizeAmount += (float) $tournamentPlayer->prize_amount;
            } else {
                $totalPrizeAmount += (float) $tournamentPlayer->prize_amount;
            }

            $totalBonusAmount += (float) $tournamentPlayer->bonus_amount;
            $totalAchievementAmount += (float) $tournamentPlayer->achievement_amount;
        }

        return [
            'user_id'                        => $userId,
            'total_prize_amount'             => $totalPrizeAmount,
            'total_bonus_amount'             => $totalBonusAmount,
            'total_achievement_amount'       => $totalAchievementAmount,
            'total_killer_pool_prize_amount' => $totalKillerPoolPrizeAmount,
            'total_money_earned'             => $totalPrizeAmount + $totalBonusAmount + $totalAchievementAmount + $totalKillerPoolPrizeAmount,
        ];
    }

    private function hasPlayerPrizesChanged(OfficialRatingPlayer $current, array $simulated): bool
    {
        return abs($current->total_prize_amount - $simulated['total_prize_amount']) > 0.01 ||
            abs($current->total_bonus_amount - $simulated['total_bonus_amount']) > 0.01 ||
            abs($current->total_achievement_amount - $simulated['total_achievement_amount']) > 0.01 ||
            abs($current->total_killer_pool_prize_amount - $simulated['total_killer_pool_prize_amount']) > 0.01;
    }

    private function showPlayerPrizeChanges(OfficialRatingPlayer $current, array $simulated): void
    {
        $user = $current->user;
        $this->line("\n🔄 Prize changes for {$user->firstname} {$user->lastname} (ID: {$user->id}):");

        if (abs($current->total_prize_amount - $simulated['total_prize_amount']) > 0.01) {
            $this->line("   Prize Amount: {$current->total_prize_amount} → {$simulated['total_prize_amount']}");
        }

        if (abs($current->total_bonus_amount - $simulated['total_bonus_amount']) > 0.01) {
            $this->line("   Bonus Amount: {$current->total_bonus_amount} → {$simulated['total_bonus_amount']}");
        }

        if (abs($current->total_achievement_amount - $simulated['total_achievement_amount']) > 0.01) {
            $this->line("   Achievement Amount: {$current->total_achievement_amount} → {$simulated['total_achievement_amount']}");
        }

        if (abs($current->total_killer_pool_prize_amount - $simulated['total_killer_pool_prize_amount']) > 0.01) {
            $this->line("   Killer Pool Prize Amount: {$current->total_killer_pool_prize_amount} → {$simulated['total_killer_pool_prize_amount']}");
        }
    }

    private function performRecalculation(OfficialRating $rating, $tournaments, $players): int
    {
        $this->info("\n💾 Performing actual recalculation:");

        return DB::transaction(function () use ($rating, $tournaments, $players) {
            $updatedCount = 0;
            $bar = $this->output->createProgressBar($players->count());
            $bar->start();

            foreach ($players as $player) {
                if (empty($player->tournament_records)) {
                    // Log current values before reset
                    $this->line("\n🔄 Resetting values for player {$player->user->firstname} {$player->user->lastname} (ID: {$player->user_id}):");
                    $this->line("   Current values:");
                    $this->line("   - Rating Points: {$player->rating_points}");
                    $this->line("   - Prize Amount: {$player->total_prize_amount}");
                    $this->line("   - Killer Pool Prize: {$player->total_killer_pool_prize_amount}");

                    // Reset all values to initial state
                    $player->update([
                        'rating_points'                  => $rating->initial_rating,
                        'tournaments_played'             => 0,
                        'tournaments_won'                => 0,
                        'total_prize_amount'             => 0,
                        'total_bonus_amount'             => 0,
                        'total_achievement_amount'       => 0,
                        'total_killer_pool_prize_amount' => 0,
                        'last_tournament_at'             => null,
                    ]);

                    // Verify the reset
                    $player->refresh();
                    $this->line("   After reset:");
                    $this->line("   - Rating Points: {$player->rating_points}");
                    $this->line("   - Prize Amount: {$player->total_prize_amount}");
                    $this->line("   - Killer Pool Prize: {$player->total_killer_pool_prize_amount}");
                } else {
                    $playerData = $this->calculatePlayerPrizes($rating, $tournaments, $player->user_id);
                    // Update player prizes
                    $player->update([
                        'total_prize_amount'             => $playerData['total_prize_amount'],
                        'total_bonus_amount'             => $playerData['total_bonus_amount'],
                        'total_achievement_amount'       => $playerData['total_achievement_amount'],
                        'total_killer_pool_prize_amount' => $playerData['total_killer_pool_prize_amount'],
                    ]);
                }

                $updatedCount++;
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();

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
            if (!$this->confirm('Do you want to recalculate prizes for this rating?')) {
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
