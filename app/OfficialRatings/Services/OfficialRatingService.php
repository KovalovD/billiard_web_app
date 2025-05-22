<?php

namespace App\OfficialRatings\Services;

use App\OfficialRatings\Models\OfficialRating;
use App\OfficialRatings\Models\OfficialRatingPlayer;
use App\Tournaments\Models\Tournament;
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
        $query = OfficialRating::with(['game']);

        if (isset($filters['game_id'])) {
            $query->where('game_id', $filters['game_id']);
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
        return OfficialRating::with(['game'])
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
            ->with(['city.country', 'club'])
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
        $tournament = Tournament::with('players')->findOrFail($tournamentId);

        // Check if tournament game matches rating game
        if ($tournament->game_id !== $rating->game_id) {
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
     * Remove tournament from rating
     * @throws Throwable
     */
    public function removeTournamentFromRating(OfficialRating $rating, Tournament $tournament): void
    {
        if (!$rating->tournaments()->where('tournament_id', $tournament->id)->exists()) {
            throw new RuntimeException('Tournament is not associated with this rating');
        }

        $rating->tournaments()->detach($tournament->id);
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
            throw new RuntimeException('Player is already in this rating');
        }

        $player = OfficialRatingPlayer::create([
            'official_rating_id' => $rating->id,
            'user_id'            => $userId,
            'rating_points'      => $initialRating ?? $rating->initial_rating,
            'position'           => $rating->players()->count() + 1,
        ]);

        // Recalculate positions
        $this->recalculatePositions($rating);

        return $player;
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
                ->orderBy('tournaments_played', 'asc')
                ->get()
            ;

            foreach ($players as $index => $player) {
                $player->update(['position' => $index + 1]);
            }
        });
    }

    /**
     * Update rating from tournament results
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
                $basePoints = $tournamentPlayer->rating_points * $ratingTournament->pivot->rating_coefficient;
                $adjustedPoints = (int) ($basePoints * $ratingTournament->pivot->rating_coefficient);

                // Update player stats
                $won = $tournamentPlayer->position === 1;
                $ratingPlayer->addTournament($adjustedPoints, $tournament->end_date, $won);

                $updatedCount++;
            }

            // Recalculate positions
            $this->recalculatePositions($rating);

            return $updatedCount;
        });
    }
}
