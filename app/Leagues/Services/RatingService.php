<?php

namespace App\Leagues\Services;

use App\Core\Models\User;
use App\Leagues\Enums\RatingType;
use App\Leagues\Models\League;
use App\Leagues\Models\Rating;
use App\Leagues\Strategies\Rating\EloRatingStrategy;
use App\Leagues\Strategies\Rating\KillerPoolRatingStrategy;
use App\Leagues\Strategies\Rating\RatingStrategy;
use App\Matches\Models\MatchGame;
use App\Matches\Models\MultiplayerGame;
use DB;
use Illuminate\Database\Eloquent\Collection;
use LogicException;
use Throwable;

class RatingService
{
    /**
     * Get active ratings with users and ongoing matches for a league
     *
     * @param  League  $league
     * @return Collection<Rating>
     */
    public function getRatingsWithUsers(League $league): Collection
    {
        return Rating::query()
            ->where('league_id', $league->id)
            ->where('is_active', true)
            ->with([
                'user',
                'ongoingMatchesAsFirstPlayer'  => function ($query) use ($league) {
                    $query->where('league_id', $league->id);
                },
                'ongoingMatchesAsSecondPlayer' => function ($query) use ($league) {
                    $query->where('league_id', $league->id);
                },
            ])
            ->orderBy('position')
            ->get()
        ;
    }

    /**
     * Add player to league with optimized performance
     * Sets is_active = true but is_confirmed = false by default
     * Requires admin confirmation
     *
     * @param  League  $league
     * @param  User  $user
     * @return bool
     * @throws Throwable
     */
    public function addPlayer(League $league, User $user): bool
    {
        // Check max players limit if set
        if ($league->max_players !== 0) {
            // Count only confirmed players for the max players limit
            $playersCount = Rating::where('league_id', $league->id)
                ->where('is_active', true)
                ->where('is_confirmed', true)
                ->count()
            ;

            if ($playersCount >= $league->max_players) {
                return false;
            }
        }

        return DB::transaction(function () use ($league, $user) {
            $existingRating = Rating::where('league_id', $league->id)
                ->where('user_id', $user->id)
                ->first()
            ;

            if ($existingRating) {
                $existingRating->update([
                    'is_active' => true,
                    // Don't change is_confirmed status if rejoining
                ]);
            } else {
                // Get position with a single query
                $position = $this->getPositionByRatingOrder($league);

                Rating::create([
                    'league_id' => $league->id,
                    'user_id'   => $user->id,
                    'rating'    => $league->start_rating,
                    'position'  => $position + 1,
                    'is_active' => true,
                    'is_confirmed' => false, // Requires admin confirmation
                ]);
            }

            $this->rearrangePositions($league->id);
            return true;
        });
    }

    /**
     * Rearrange positions with optimized query
     *
     * @param  int  $leagueId
     * @throws Throwable
     */
    public function rearrangePositions(int $leagueId): void
    {
        // Get ordered rating IDs with a single optimized query
        $orderedIds = Rating::query()
            ->where('ratings.league_id', $leagueId)
            ->join('users', 'users.id', '=', 'ratings.user_id')
            ->leftJoin('match_games as mg', function ($join) use ($leagueId) {
                $join->on(function ($q) {
                    $q
                        ->on('mg.first_rating_id', '=', 'ratings.id')
                        ->orOn('mg.second_rating_id', '=', 'ratings.id')
                    ;
                })->where('mg.league_id', '=', $leagueId);
            })
            ->groupBy('ratings.id', 'ratings.rating', 'users.firstname', 'users.lastname')
            ->select([
                'ratings.id',
                'ratings.rating',
                DB::raw('COUNT(CASE WHEN mg.winner_rating_id = ratings.id THEN 1 END) AS wins_count'),
                DB::raw('COALESCE(SUM(CASE
                    WHEN mg.first_rating_id = ratings.id THEN mg.first_user_score
                    WHEN mg.second_rating_id = ratings.id THEN mg.second_user_score
                    ELSE 0
                END), 0) AS frames_won'),
                DB::raw('COALESCE(SUM(CASE
                    WHEN mg.first_rating_id = ratings.id THEN mg.second_user_score
                    WHEN mg.second_rating_id = ratings.id THEN mg.first_user_score
                    ELSE 0
                END), 0) AS frames_lost'),
                DB::raw('COUNT(mg.id) AS matches_count'),
                DB::raw('COALESCE(SUM(CASE
                    WHEN mg.first_rating_id = ratings.id THEN mg.first_user_score
                    WHEN mg.second_rating_id = ratings.id THEN mg.second_user_score
                    ELSE 0
                END), 0) - COALESCE(SUM(CASE
                    WHEN mg.first_rating_id = ratings.id THEN mg.second_user_score
                    WHEN mg.second_rating_id = ratings.id THEN mg.first_user_score
                    ELSE 0
                END), 0) AS frame_diff'),
            ])
            ->orderByRaw('ratings.rating DESC')
            ->orderByRaw('COUNT(CASE WHEN mg.winner_rating_id = ratings.id THEN 1 END) DESC')
            ->orderByRaw('(COALESCE(SUM(CASE
                WHEN mg.first_rating_id = ratings.id THEN mg.first_user_score
                WHEN mg.second_rating_id = ratings.id THEN mg.second_user_score
                ELSE 0
            END), 0) - COALESCE(SUM(CASE
                WHEN mg.first_rating_id = ratings.id THEN mg.second_user_score
                WHEN mg.second_rating_id = ratings.id THEN mg.first_user_score
                ELSE 0
            END), 0)) DESC')
            ->orderByRaw('COALESCE(SUM(CASE
                WHEN mg.first_rating_id = ratings.id THEN mg.first_user_score
                WHEN mg.second_rating_id = ratings.id THEN mg.second_user_score
                ELSE 0
            END), 0) DESC')
            ->orderByRaw('COUNT(mg.id) DESC')
            ->orderByRaw('COALESCE(SUM(CASE
                WHEN mg.first_rating_id = ratings.id THEN mg.second_user_score
                WHEN mg.second_rating_id = ratings.id THEN mg.first_user_score
                ELSE 0
            END), 0) ASC')
            ->orderBy('users.lastname')
            ->orderBy('users.firstname')
            ->pluck('ratings.id')
            ->toArray()
        ;

        // Update positions in batches
        $this->batchUpdatePositions($orderedIds);
    }

    /**
     * Update positions in batches for better performance
     *
     * @param  array  $orderedIds
     * @throws Throwable
     */
    private function batchUpdatePositions(array $orderedIds): void
    {
        if (empty($orderedIds)) {
            return;
        }

        DB::transaction(static function () use ($orderedIds) {
            // Build a bulk update query
            $cases = [];
            $params = [];

            foreach ($orderedIds as $index => $ratingId) {
                $cases[] = "WHEN id = ? THEN ?";
                $params[] = $ratingId;
                $params[] = $index + 1;
            }

            $sql = "UPDATE ratings SET position = CASE ".implode(' ', $cases)." END WHERE id IN (".
                str_repeat('?,', count($orderedIds) - 1)."?)";

            $params = array_merge($params, $orderedIds);

            DB::update($sql, $params);
        });
    }

    /**
     * Get position by rating order with optimized query
     *
     * @param  League  $league
     * @return int
     */
    private function getPositionByRatingOrder(League $league): int
    {
        return Rating::query()
            ->where('league_id', $league->id)
            ->where('rating', '>', $league->start_rating)
            ->min('position') ?? 1;
    }

    /**
     * Disable player with optimized transaction
     *
     * @param  League  $league
     * @param  User  $user
     * @throws Throwable
     */
    public function disablePlayer(League $league, User $user): void
    {
        DB::transaction(function () use ($league, $user) {
            $updated = Rating::where('league_id', $league->id)
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->update(['is_active' => false])
            ;

            if ($updated > 0) {
                $this->rearrangePositions($league->id);
            }
        });
    }

    /**
     * Update ratings with optimized operations
     *
     * @param  MatchGame  $matchGame
     * @param  int  $winnerUserId
     * @return array
     * @throws Throwable
     */
    public function updateRatings(MatchGame $matchGame, int $winnerUserId): array
    {
        return DB::transaction(function () use ($matchGame, $winnerUserId) {
            $league = $matchGame->league;
            $ratings = $matchGame->getRatings();

            $strategy = $this->resolveStrategy($league->rating_type);

            $result = $strategy->calculate(
                $ratings,
                $winnerUserId,
                $league->rating_change_for_winners_rule,
                $league->rating_change_for_losers_rule,
            );

            // Batch update ratings
            $updates = [];
            foreach ($ratings as $rating) {
                $updates[] = [
                    'id'     => $rating->id,
                    'rating' => $result[$rating->id],
                ];
            }

            // Perform batch update
            foreach ($updates as $update) {
                Rating::where('id', $update['id'])->update(['rating' => $update['rating']]);
            }

            $this->rearrangePositions($league->id);

            return $result;
        });
    }

    /**
     * Get rating strategy with caching
     *
     * @param  RatingType  $type
     * @return RatingStrategy
     */
    private function resolveStrategy(RatingType $type): RatingStrategy
    {
        // In a real application, you might want to cache strategies
        return match ($type) {
            RatingType::Elo => new EloRatingStrategy(),
            RatingType::KillerPool => new KillerPoolRatingStrategy(),
        };
    }

    public function applyRatingPointsForMultiplayerGame(MultiplayerGame $game): void
    {
        $game->load('players', 'league');

        $league = $game->league;

        // Verify league has KillerPool rating type
        if ($league->rating_type !== RatingType::KillerPool) {
            throw new LogicException("League rating type must be KillerPool");
        }

        $strategy = $this->resolveStrategy($league->rating_type);

        $ratings = Rating::query()
            ->where('league_id', $league->id)
            ->whereIn('user_id', $game->players->pluck('user_id'))
            ->get()
            ->toArray()
        ;

        $result = $strategy->calculate($ratings, 0, $game->players->pluck('rating_points', 'user_id')->toArray(), []);

        // Batch update ratings
        $updates = [];
        foreach ($ratings as $rating) {
            $updates[] = [
                'id'     => $rating['id'],
                'rating' => $result[$rating['id']],
            ];
        }

        // Perform batch update
        foreach ($updates as $update) {
            Rating::where('id', $update['id'])->update(['rating' => $update['rating']]);
        }
    }

    /**
     * Get active rating for user in league with single query
     *
     * @param  User  $user
     * @param  League  $league
     * @return Rating|null
     */
    public function getActiveRatingForUserLeague(User $user, League $league): ?Rating
    {
        return Rating::query()
            ->where('user_id', $user->id)
            ->where('league_id', $league->id)
            ->where('is_active', true)
            ->first()
        ;
    }
}
