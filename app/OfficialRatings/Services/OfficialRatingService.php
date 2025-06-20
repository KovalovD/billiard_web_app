<?php

namespace App\OfficialRatings\Services;

use App\Matches\Enums\GameType;
use App\OfficialRatings\Models\OfficialRating;
use App\OfficialRatings\Models\OfficialRatingPlayer;
use App\Tournaments\Models\Tournament;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class OfficialRatingService
{
    /**
     * Get all official ratings with filtering
     */
    public function getAllRatings(array $filters = []): Collection
    {
        $query = OfficialRating::query();

        if (isset($filters['game_type'])) {
            $query->where('game_type', $filters['game_type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Get active ratings
     */
    public function getActiveRatings(): Collection
    {
        return OfficialRating::where('is_active', true)
            ->orderBy('name')
            ->get()
        ;
    }

    /**
     * Get ratings by game type
     */
    public function getRatingsByGameType(GameType $gameType): Collection
    {
        return OfficialRating::where('game_type', $gameType)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
        ;
    }

    /**
     * Create new official rating
     */
    public function createRating(array $data): OfficialRating
    {
        return OfficialRating::create($data);
    }

    /**
     * Update official rating
     */
    public function updateRating(OfficialRating $rating, array $data): OfficialRating
    {
        $rating->update($data);
        return $rating->fresh();
    }

    /**
     * Delete official rating
     */
    public function deleteRating(OfficialRating $rating): void
    {
        $rating->delete();
    }

    /**
     * Get rating players with filtering
     */
    public function getRatingPlayers(OfficialRating $rating, array $filters = []): Collection
    {
        $query = $rating->players()->with('user');

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['min_tournaments'])) {
            $query->where('tournaments_played', '>=', $filters['min_tournaments']);
        }

        return $query->orderBy('position')->get();
    }

    /**
     * Get rating tournaments
     */
    public function getRatingTournaments(OfficialRating $rating): array
    {
        $tournaments = $rating
            ->tournaments()
            ->with(['city.country', 'club', 'game'])
            ->orderBy('start_date', 'desc')
            ->get()
        ;

        return $tournaments->map(function ($tournament) {
            return [
                'id'                 => $tournament->id,
                'name'               => $tournament->name,
                'start_date'         => $tournament->start_date?->format('Y-m-d'),
                'end_date'           => $tournament->end_date?->format('Y-m-d'),
                'status'             => $tournament->status,
                'city'               => $tournament->city?->name,
                'country'            => $tournament->city?->country?->name,
                'club'               => $tournament->club?->name,
                'game_name' => $tournament->game?->name,
                'players_count'      => $tournament->players_count,
                'rating_coefficient' => $tournament->pivot->rating_coefficient,
                'is_counting'        => $tournament->pivot->is_counting,
            ];
        })->toArray();
    }

    /**
     * Add tournament to rating
     * @throws Throwable
     */
    public function addTournamentToRating(
        OfficialRating $rating,
        int $tournamentId,
        float $ratingCoefficient = 1.0,
        bool $isCounting = true,
    ): void {
        $tournament = Tournament::with(['players', 'game'])->findOrFail($tournamentId);

        // Check if tournament game type matches rating game type
        if ($tournament->game->type !== $rating->game_type) {
            throw new RuntimeException('Tournament game type does not match rating game type');
        }

        // Check if tournament is already added
        if ($rating->tournaments()->where('tournament_id', $tournamentId)->exists()) {
            throw new RuntimeException('Tournament is already added to this rating');
        }

        $rating->tournaments()->attach($tournamentId, [
            'rating_coefficient' => $ratingCoefficient,
            'is_counting'        => $isCounting,
        ]);

        foreach ($tournament->players as $tournamentPlayer) {
            $this->addPlayerToRating($rating, $tournamentPlayer->user_id);
        }
    }

    /**
     * Add player to rating
     * @throws Throwable
     */
    public function addPlayerToRating(
        OfficialRating $rating,
        int $userId,
        ?int $initialRating = null,
    ): OfficialRatingPlayer {
        // Check if player already exists
        if ($rating->hasPlayer($userId)) {
            return $rating->getPlayerRating($userId);
        }

        $player = OfficialRatingPlayer::create([
            'official_rating_id'       => $rating->id,
            'user_id'                  => $userId,
            'rating_points'            => $initialRating ?? $rating->initial_rating,
            'position'                 => $rating->players()->count() + 1,
            'tournament_records'       => [],
            'total_bonus_amount'       => 0,
            'total_achievement_amount' => 0,
        ]);

        // Recalculate positions
        $this->recalculatePositions($rating);

        return $player;
    }

    /**
     * Recalculate rating positions
     * @throws Throwable
     */
    public function recalculatePositions(OfficialRating $rating): void
    {
        DB::transaction(static function () use ($rating) {
            $players = $rating
                ->players()
                ->where('is_active', true)
                ->orderBy('rating_points', 'desc')
                ->orderBy('tournaments_won', 'desc')
                ->orderBy('tournaments_played')
                ->get()
            ;

            foreach ($players as $index => $player) {
                $player->update(['position' => $index + 1]);
            }
        });
    }

    /**
     * Remove tournament from rating
     * @throws Throwable
     */
    public function removeTournamentFromRating(OfficialRating $rating, Tournament $tournament): void
    {
        if (!$rating->tournaments()->where('tournament_id', $tournament->id)->exists()) {
            throw new RuntimeException('Tournament is not associated with this rating');
        }

        DB::transaction(function () use ($rating, $tournament) {
            // Remove tournament records from all players
            $players = $rating->players()->get();
            foreach ($players as $player) {
                $player->removeTournament($tournament->id);
            }

            // Detach tournament from rating
            $rating->tournaments()->detach($tournament->id);

            // Recalculate positions
            $this->recalculatePositions($rating);
        });
    }

    /**
     * Remove player from rating
     * @throws Throwable
     */
    public function removePlayerFromRating(OfficialRating $rating, int $userId): void
    {
        $player = $rating->getPlayerRating($userId);

        if (!$player) {
            throw new RuntimeException('Player is not in this rating');
        }

        $player->delete();

        // Recalculate positions
        $this->recalculatePositions($rating);
    }

    /**
     * Update rating from tournament results using tournament records tracking
     * @throws Throwable
     */
    public function updateRatingFromTournament(OfficialRating $rating, Tournament $tournament): int
    {
        if (!$rating->tournaments()->where('tournament_id', $tournament->id)->exists()) {
            throw new RuntimeException('Tournament is not associated with this rating');
        }

        if (!$tournament->isCompleted()) {
            throw new RuntimeException('Tournament is not completed yet');
        }

        $ratingTournament = $rating->tournaments()->where('tournament_id', $tournament->id)->first();

        if (!$ratingTournament->pivot->is_counting) {
            throw new RuntimeException('Tournament is not set to count towards rating');
        }

        return DB::transaction(function () use ($rating, $tournament, $ratingTournament) {
            $tournamentPlayers = $tournament
                ->players()
                ->whereNotNull('position')
                ->with('user')
                ->get()
            ;

            $updatedCount = 0;

            foreach ($tournamentPlayers as $tournamentPlayer) {
                // Get or create rating player
                $ratingPlayer = $rating->getPlayerRating($tournamentPlayer->user_id);

                if (!$ratingPlayer) {
                    $ratingPlayer = $this->addPlayerToRating(
                        $rating,
                        $tournamentPlayer->user_id,
                        $rating->initial_rating,
                    );
                }

                // Calculate rating points based on position and coefficient
                $basePoints = $tournamentPlayer->rating_points;
                $adjustedPoints = (int) ($basePoints * $ratingTournament->pivot->rating_coefficient);

                // Update player using the new tournament record system with prize, bonus and achievement amounts
                $won = $tournamentPlayer->position === 1;
                $ratingPlayer->addTournament(
                    $tournament->id,
                    $adjustedPoints,
                    $tournament->end_date,
                    $won,
                    $tournament->game->is_multiplayer ? 0 : (float) $tournamentPlayer->prize_amount,
                    (float) $tournamentPlayer->bonus_amount,
                    (float) $tournamentPlayer->achievement_amount,
                    $tournament->game->is_multiplayer ? (float) $tournamentPlayer->prize_amount : 0,
                );

                $updatedCount++;
            }

            // Recalculate positions
            $this->recalculatePositions($rating);

            return $updatedCount;
        });
    }

    /**
     * Recalculate all players from their tournament records (data integrity check)
     * @throws Throwable
     */
    public function recalculateAllPlayersFromRecords(OfficialRating $rating): int
    {
        return DB::transaction(function () use ($rating) {
            $players = $rating->players()->get();
            $updatedCount = 0;

            foreach ($players as $player) {
                $player->recalculateFromRecords();
                $updatedCount++;
            }

            // Recalculate positions
            $this->recalculatePositions($rating);

            return $updatedCount;
        });
    }

    /**
     * Get rating integrity report (useful for debugging)
     */
    public function getRatingIntegrityReport(OfficialRating $rating): array
    {
        $players = $rating->players()->get();
        $issues = [];

        foreach ($players as $player) {
            $calculatedPoints = $rating->initial_rating + $player->getTotalPointsFromRecords();
            $calculatedPrizeAmount = $player->getTotalPrizeFromRecords();
            $calculatedBonusAmount = $player->getTotalBonusFromRecords();
            $calculatedAchievementAmount = $player->getTotalAchievementFromRecords();

            $hasIssues = false;
            $playerIssues = [];

            if ($calculatedPoints !== $player->rating_points) {
                $hasIssues = true;
                $playerIssues['rating_points'] = [
                    'current'    => $player->rating_points,
                    'calculated' => $calculatedPoints,
                    'difference' => $player->rating_points - $calculatedPoints,
                ];
            }

            if (abs($calculatedPrizeAmount - (float) $player->total_prize_amount) > 0.01) {
                $hasIssues = true;
                $playerIssues['prize_amount'] = [
                    'current'    => (float) $player->total_prize_amount,
                    'calculated' => $calculatedPrizeAmount,
                    'difference' => (float) $player->total_prize_amount - $calculatedPrizeAmount,
                ];
            }

            if (abs($calculatedBonusAmount - (float) $player->total_bonus_amount) > 0.01) {
                $hasIssues = true;
                $playerIssues['bonus_amount'] = [
                    'current'    => (float) $player->total_bonus_amount,
                    'calculated' => $calculatedBonusAmount,
                    'difference' => (float) $player->total_bonus_amount - $calculatedBonusAmount,
                ];
            }

            if (abs($calculatedAchievementAmount - (float) $player->total_achievement_amount) > 0.01) {
                $hasIssues = true;
                $playerIssues['achievement_amount'] = [
                    'current'    => (float) $player->total_achievement_amount,
                    'calculated' => $calculatedAchievementAmount,
                    'difference' => (float) $player->total_achievement_amount - $calculatedAchievementAmount,
                ];
            }

            if ($hasIssues) {
                $issues[] = [
                    'player_id'                => $player->id,
                    'player_name'              => $player->user->firstname.' '.$player->user->lastname,
                    'tournament_records_count' => count($player->tournament_records ?? []),
                    'issues' => $playerIssues,
                ];
            }
        }

        return [
            'total_players'       => $players->count(),
            'players_with_issues' => count($issues),
            'issues'              => $issues,
        ];
    }

    /**
     * Get rating delta for player since given date
     */
    public function getPlayerDeltaSinceDate(
        OfficialRating $rating,
        int $userId,
        Carbon $date,
    ): ?array {
        $players = $rating->players()->get();

        $stats = [];
        foreach ($players as $player) {
            $records = $player->tournament_records ?? [];
            $beforePoints = $rating->initial_rating;
            $beforePrizeAmount = 0;
            $beforeBonusAmount = 0;
            $beforeAchievementAmount = 0;
            $wins = 0;
            $played = 0;

            foreach ($records as $record) {
                if (Carbon::parse($record['tournament_date'])->lt($date)) {
                    $beforePoints += $record['rating_points'];
                    $beforePrizeAmount += $record['prize_amount'] ?? 0;
                    $beforeBonusAmount += $record['bonus_amount'] ?? 0;
                    $beforeAchievementAmount += $record['achievement_amount'] ?? 0;
                    $played++;
                    if (!empty($record['won'])) {
                        $wins++;
                    }
                }
            }

            $stats[] = [
                'player_id'          => $player->id,
                'user_id'            => $player->user_id,
                'points'             => $beforePoints,
                'prize_amount'       => $beforePrizeAmount,
                'bonus_amount'       => $beforeBonusAmount,
                'achievement_amount' => $beforeAchievementAmount,
                'wins'               => $wins,
                'played'             => $played,
            ];
        }

        usort($stats, static function ($a, $b) {
            if ($a['points'] !== $b['points']) {
                return $b['points'] <=> $a['points'];
            }

            if ($a['wins'] !== $b['wins']) {
                return $b['wins'] <=> $a['wins'];
            }

            return $a['played'] <=> $b['played'];
        });

        foreach ($stats as $index => &$stat) {
            $stat['position'] = $index + 1;
        }

        $playerStat = collect($stats)->firstWhere('user_id', $userId);
        $player = $players->firstWhere('user_id', $userId);

        if (!$player || !$playerStat) {
            return null;
        }

        return [
            'current_points'             => $player->rating_points,
            'points_before'              => $playerStat['points'],
            'points_delta'               => $player->rating_points - $playerStat['points'],
            'current_position'           => $player->position,
            'position_before'            => $playerStat['position'],
            'position_delta'             => $playerStat['position'] - $player->position,
            'current_prize_amount'       => (float) $player->total_prize_amount,
            'prize_amount_before'        => $playerStat['prize_amount'],
            'prize_amount_delta'         => (float) $player->total_prize_amount - $playerStat['prize_amount'],
            'current_bonus_amount'       => (float) $player->total_bonus_amount,
            'bonus_amount_before'        => $playerStat['bonus_amount'],
            'bonus_amount_delta'         => (float) $player->total_bonus_amount - $playerStat['bonus_amount'],
            'current_achievement_amount' => (float) $player->total_achievement_amount,
            'achievement_amount_before'  => $playerStat['achievement_amount'],
            'achievement_amount_delta'   => (float) $player->total_achievement_amount - $playerStat['achievement_amount'],
        ];
    }

    public function getOneYearRating(): \Illuminate\Support\Collection
    {
        $tournaments = Tournament::query()
            ->with('players.user')
            ->where('is_old', false)
            ->get()
        ;

        $players = [];
        foreach ($tournaments as $tournament) {
            foreach ($tournament->players as $player) {
                if ($tournament->game->is_multiplayer) {
                    $prize = ($players[$player->user_id]['killer_pool_amount'] ?? 0) + $player->prize_amount;
                    $oppositePrize = $players[$player->user_id]['prize_amount'] ?? 0;
                    $index = 'killer_pool_amount';
                    $oppositeIndex = 'prize_amount';
                } else {
                    $prize = ($players[$player->user_id]['prize_amount'] ?? 0) + $player->prize_amount;
                    $oppositePrize = $players[$player->user_id]['killer_pool_amount'] ?? 0;
                    $index = 'prize_amount';
                    $oppositeIndex = 'killer_pool_amount';
                }

                $players[$player->user_id] = [
                    'user'               => $player->user,
                    'rating'             => ($players[$player->user_id]['rating'] ?? 0) + $player->rating_points,
                    $index         => $prize,
                    $oppositeIndex => $oppositePrize,
                    'bonus_amount'       => ($players[$player->user_id]['bonus_amount'] ?? 0) + $player->bonus_amount,
                    'achievement_amount' => ($players[$player->user_id]['achievement_amount'] ?? 0) + $player->achievement_amount,
                ];
            }
        }

        $i = 1;
        return collect($players)->sortByDesc('rating')->map(function ($player) use (&$i) {
            $player['position'] = $i;
            $i++;
            return $player;
        })->values();
    }
}
