<?php

namespace App\User\Services;

use App\Core\Models\User;
use App\Leagues\Models\Rating;
use App\Matches\Enums\GameStatus;
use App\Matches\Models\MatchGame;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class UserStatsService
{
    /**
     * Get ratings for a user across all leagues
     */
    public function getUserRatings(User $user): Collection
    {
        return Rating::where('user_id', $user->id)
            ->with(['league', 'league.game']) // Eager load both league and game
            ->orderByDesc('created_at')
            ->get()
        ;
    }

    /**
     * Get match history for a user
     */
    public function getUserMatches(User $user): Collection
    {
        // Get all ratings for this user first
        $userRatingIds = Rating::where('user_id', $user->id)->pluck('id');

        if ($userRatingIds->isEmpty()) {
            return new Collection();
        }

        // Get all matches where user participated as either first or second player
        return MatchGame::where(static function ($query) use ($userRatingIds) {
            $query
                ->whereIn('first_rating_id', $userRatingIds)
                ->orWhereIn('second_rating_id', $userRatingIds)
            ;
        })
            ->with([
                'firstRating.user',
                'secondRating.user',
                'league',
                'league.game',
            ])
            ->whereIn('status', [GameStatus::MUST_BE_CONFIRMED, GameStatus::IN_PROGRESS, GameStatus::COMPLETED])
            ->orderByRaw("CASE
                WHEN status = 'must_be_confirmed' THEN 0
                WHEN status = 'in_progress' THEN 1
                ELSE 2
             END")  // Order by priority: MUST_BE_CONFIRMED first, then IN_PROGRESS, then COMPLETED
            ->orderBy('finished_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
        ;
    }

    /**
     * Get overall statistics for a user
     */
    public function getUserStats(User $user): array
    {
        // Get all ratings for this user
        $ratings = Rating::where('user_id', $user->id)->with('league')->get();

        if ($ratings->isEmpty()) {
            return [
                'total_matches'     => 0,
                'completed_matches' => 0,
                'wins'              => 0,
                'losses'            => 0,
                'win_rate'          => 0,
                'leagues_count'     => 0,
                'highest_rating'    => 0,
                'average_rating'    => 0,
            ];
        }

        // Get all user rating IDs
        $userRatingIds = $ratings->pluck('id')->toArray();

        // Count matches where user is either first or second player
        $totalMatches = MatchGame::where(static function ($query) use ($userRatingIds) {
            $query
                ->whereIn('first_rating_id', $userRatingIds)
                ->orWhereIn('second_rating_id', $userRatingIds)
            ;
        })->count();

        // Count completed matches
        $completedMatches = MatchGame::where(static function ($query) use ($userRatingIds) {
            $query
                ->whereIn('first_rating_id', $userRatingIds)
                ->orWhereIn('second_rating_id', $userRatingIds)
            ;
        })
            ->where('status', GameStatus::COMPLETED)
            ->count()
        ;

        // Count wins (where user's rating ID is the winner)
        $wins = MatchGame::whereIn('winner_rating_id', $userRatingIds)
            ->where('status', GameStatus::COMPLETED)
            ->count()
        ;

        // Calculate win rate
        $winRate = $completedMatches > 0 ? round(($wins / $completedMatches) * 100) : 0;

        // Get highest and average rating
        $highestRating = $ratings->max('rating');
        $averageRating = $ratings->avg('rating');

        return [
            'total_matches'     => $totalMatches,
            'completed_matches' => $completedMatches,
            'wins'              => $wins,
            'losses'            => $completedMatches - $wins,
            'win_rate'          => $winRate,
            'leagues_count'     => $ratings->count(),
            'highest_rating'    => (int) $highestRating,
            'average_rating'    => round($averageRating),
        ];
    }

    /**
     * Get detailed game type statistics for a user
     */
    public function getGameTypeStats(User $user): array
    {
        $userRatingIds = Rating::where('user_id', $user->id)->pluck('id');

        if ($userRatingIds->isEmpty()) {
            return [];
        }

        $matchesByGameType = MatchGame::where('status', GameStatus::COMPLETED)
            ->where(function ($query) use ($userRatingIds) {
                $query
                    ->whereIn('first_rating_id', $userRatingIds)
                    ->orWhereIn('second_rating_id', $userRatingIds)
                ;
            })
            ->join('leagues', 'match_games.league_id', '=', 'leagues.id')
            ->join('games', 'leagues.game_id', '=', 'games.id')
            ->select('games.type', DB::raw('count(*) as match_count'))
            ->groupBy('games.type')
            ->get()
            ->keyBy('type')
        ;

        $winsByGameType = MatchGame::where('status', GameStatus::COMPLETED)
            ->whereIn('winner_rating_id', $userRatingIds)
            ->join('leagues', 'match_games.league_id', '=', 'leagues.id')
            ->join('games', 'leagues.game_id', '=', 'games.id')
            ->select('games.type', DB::raw('count(*) as win_count'))
            ->groupBy('games.type')
            ->get()
            ->keyBy('type')
        ;

        $result = [];

        foreach ($matchesByGameType as $type => $data) {
            $matchCount = $data->match_count;
            $winCount = $winsByGameType->get($type)->win_count ?? 0;

            $result[$type] = [
                'matches'  => $matchCount,
                'wins'     => $winCount,
                'losses'   => $matchCount - $winCount,
                'win_rate' => $matchCount > 0 ? round(($winCount / $matchCount) * 100) : 0,
            ];
        }

        return $result;
    }
}
