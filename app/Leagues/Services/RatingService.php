<?php

namespace App\Leagues\Services;

use App\Core\Models\User;
use App\Leagues\Enums\RatingType;
use App\Leagues\Models\League;
use App\Leagues\Models\Rating;
use App\Leagues\Strategies\Rating\EloRatingStrategy;
use App\Leagues\Strategies\Rating\RatingStrategy;
use App\Matches\Models\MatchGame;
use DB;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class RatingService
{
    /**
     * @return Collection<Rating>
     */
    public function getRatingsWithUsers(League $league): Collection
    {
        return Rating::query()
            ->where('league_id', $league->id)
            ->where('is_active', true)
            ->with('user')
            ->orderBy('position')
            ->get()
        ;
    }

    /**
     * @throws Throwable
     */
    public function addPlayer(League $league, User $user): bool
    {
        $playersCount = Rating::where('league_id', $league->id)->where('is_active', true)->count();
        if ($league->max_players !== 0 && $playersCount >= $league->max_players) {
            return false;
        }

        $existingRating = Rating::where('league_id', $league->id)->where('user_id', $user->id)->first();

        if ($existingRating) {
            $existingRating->update(['is_active' => true]);

            $this->rearrangePositions($league->id);
            return true;
        }

        $position = $this->getPositionByRatingOrder($league);

        Rating::create([
            'league_id' => $league->id,
            'user_id'   => $user->id,
            'rating'    => $league->start_rating,
            'position'  => $position + 1,
            'is_active' => true,
        ]);

        $this->rearrangePositions($league->id);

        return true;
    }

    /**
     * Пересчитывает поле `position` в таблице ratings
     * по заданным правилам:
     * 0) rating DESC
     * 1) wins_count DESC
     * 2) frame_diff DESC
     * 3) frames_won DESC
     * 4) matches_count DESC
     * 5) frames_lost ASC
     * 6) user.lastname ASC
     * 7) user.firstname ASC
     *
     * @param  int  $leagueId
     * @throws Throwable
     */
    public function rearrangePositions(int $leagueId): void
    {
        // Получаем упорядоченный список rating_id
        $orderedIds = Rating::query()
            ->where('ratings.league_id', $leagueId)
            ->join('users', 'users.id', '=', 'ratings.user_id')
            // левое соединение по всем играм, где участвует этот рейтинг
            ->leftJoin('match_games as mg', function ($join) use ($leagueId) {
                $join->on(function ($q) {
                    $q
                        ->on('mg.first_rating_id', '=', 'ratings.id')
                        ->orOn('mg.second_rating_id', '=', 'ratings.id')
                    ;
                })->where('mg.league_id', '=', $leagueId);
            })
            ->groupBy('ratings.id', 'ratings.rating', 'users.firstname', 'users.lastname')
            ->select('ratings.id')
            ->selectRaw('ratings.rating      AS rating_score')
            ->selectRaw('SUM(CASE WHEN mg.winner_rating_id = ratings.id THEN 1 ELSE 0 END) AS wins_count')
            ->selectRaw('SUM(
                CASE
                    WHEN mg.first_rating_id  = ratings.id THEN mg.first_user_score
                    WHEN mg.second_rating_id = ratings.id THEN mg.second_user_score
                    ELSE 0
                END
            ) AS frames_won')
            ->selectRaw('SUM(
                CASE
                    WHEN mg.first_rating_id  = ratings.id THEN mg.second_user_score
                    WHEN mg.second_rating_id = ratings.id THEN mg.first_user_score
                    ELSE 0
                END
            ) AS frames_lost')
            ->selectRaw('COUNT(mg.id)       AS matches_count')
            ->orderByDesc('rating_score')
            ->orderByDesc('wins_count')
            ->orderByDesc(DB::raw('frames_won - frames_lost'))
            ->orderByDesc('frames_won')
            ->orderByDesc('matches_count')
            ->orderBy('frames_lost')
            ->orderBy('users.lastname')
            ->orderBy('users.firstname')
            ->pluck('ratings.id')
            ->toArray()
        ;

        // Обновляем позиции в одной транзакции
        DB::transaction(static function () use ($orderedIds) {
            foreach ($orderedIds as $index => $ratingId) {
                Rating::where('id', $ratingId)
                    ->update(['position' => $index + 1])
                ;
            }
        });
    }

    private function getPositionByRatingOrder(League $league): int
    {
        return Rating::query()
            ->where('league_id', $league->id)
            ->where('rating', '>', $league->start_rating)
            ->orderBy('position')
            ->first()
            ?->position ?: 1;
    }

    /**
     * @throws Throwable
     */
    public function disablePlayer(League $league, User $user): void
    {
        $rating = Rating::where('league_id', $league->id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first()
        ;

        if (!$rating) {
            return;
        }

        $rating->update(['is_active' => false]);

        $this->rearrangePositions($league->id);
    }

    /**
     * @throws Throwable
     */
    public function updateRatings(MatchGame $matchGame, int $winnerUserId): array
    {
        $league = $matchGame->league;
        $ratings = $matchGame->getRatings();

        $strategy = $this->resolveStrategy($league->rating_type);

        $result = $strategy->calculate(
            $ratings,
            $winnerUserId,
            $league->rating_change_for_winners_rule,
            $league->rating_change_for_losers_rule,
        );

        foreach ($ratings as $rating) {
            $rating->rating = $result[$rating->id];
            $rating->save();
        }

        $this->rearrangePositions($league->id);

        return $result;
    }

    private function resolveStrategy(RatingType $type): RatingStrategy
    {
        return match ($type) {
            RatingType::Elo => new EloRatingStrategy(),
        };
    }

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
