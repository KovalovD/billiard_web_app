<?php

namespace App\Players\Services;

use App\Core\Models\User;
use App\Leagues\Models\Rating;
use App\Matches\Enums\GameStatus;
use App\Matches\Models\MatchGame;
use App\OfficialRatings\Models\OfficialRatingPlayer;
use App\Tournaments\Enums\MatchStatus;
use App\Tournaments\Models\TournamentMatch;
use App\Tournaments\Models\TournamentPlayer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class PlayerService
{
    /**
     * Get players with basic statistics and filtering
     */
    public function getPlayersWithStats(array $filters = []): LengthAwarePaginator|array
    {
        $query = User::query()
            ->with(['homeCity.country', 'homeClub'])
            ->where('is_active', true)
        ;

        // Apply filters
        if (!empty($filters['country_id'])) {
            $query->whereHas('homeCity', function (Builder $q) use ($filters) {
                $q->where('country_id', $filters['country_id']);
            });
        }

        if (!empty($filters['city_id'])) {
            $query->where('home_city_id', $filters['city_id']);
        }

        if (!empty($filters['club_id'])) {
            $query->where('home_club_id', $filters['club_id']);
        }

        if (!empty($filters['name'])) {
            $query->where(function (Builder $q) use ($filters) {
                $search = '%'.$filters['name'].'%';
                $q
                    ->where('firstname', 'like', $search)
                    ->orWhere('lastname', 'like', $search)
                    ->orWhereRaw("CONCAT(lastname, ' ', firstname) LIKE ?", [$search])
                    ->orWhereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", [$search])
                ;
            });
        }

        // Add basic stats using subqueries
        $query
            ->withCount(['tournamentPlayers as tournaments_count'])
            ->withCount([
                'tournamentPlayers as tournaments_won' => function (Builder $q) {
                    $q->where('position', 1);
                },
            ])
            ->addSelect([
                'league_matches_count'     => Rating::selectRaw('COUNT(DISTINCT mg.id)')
                    ->join('match_games as mg', function ($join) {
                        $join
                            ->on('ratings.id', '=', 'mg.first_rating_id')
                            ->orOn('ratings.id', '=', 'mg.second_rating_id')
                        ;
                    })
                    ->whereColumn('ratings.user_id', 'users.id')
                    ->where('mg.status', GameStatus::COMPLETED),
                'league_matches_won'       => Rating::selectRaw('COUNT(DISTINCT mg.id)')
                    ->join('match_games as mg', 'ratings.id', '=', 'mg.winner_rating_id')
                    ->whereColumn('ratings.user_id', 'users.id')
                    ->where('mg.status', GameStatus::COMPLETED),
                'official_rating_points'   => OfficialRatingPlayer::select('rating_points')
                    ->whereColumn('user_id', 'users.id')
                    ->where('is_active', true)
                    ->orderBy('rating_points', 'desc')
                    ->limit(1),
                'official_rating_position' => OfficialRatingPlayer::select('position')
                    ->whereColumn('user_id', 'users.id')
                    ->where('is_active', true)
                    ->orderBy('rating_points', 'desc')
                    ->limit(1),
            ])
        ;

        $perPage = $filters['per_page'] ?? 50;

        return $query
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->paginate($perPage)
        ;
    }

    /**
     * Get tournament statistics for a player
     */
    public function getTournamentStats(User $player): array
    {
        $tournamentPlayers = TournamentPlayer::where('user_id', $player->id)
            ->with('tournament')
            ->get()
        ;

        $totalTournaments = $tournamentPlayers->count();
        $tournamentsWon = $tournamentPlayers->where('position', 1)->count();
        $tournamentsTop3 = $tournamentPlayers->whereIn('position', [1, 2, 3])->count();
        $totalPrizeWon = $tournamentPlayers->sum('prize_amount');
        $totalRatingPoints = $tournamentPlayers->sum('rating_points');

        // Win rate by tournament type
        $winRateByType = $tournamentPlayers
            ->groupBy('tournament.tournament_type')
            ->map(function ($group) {
                $total = $group->count();
                $wins = $group->where('position', 1)->count();
                return [
                    'total'    => $total,
                    'wins'     => $wins,
                    'win_rate' => $total > 0 ? round(($wins / $total) * 100, 2) : 0,
                ];
            })
        ;

        return [
            'total_tournaments'   => $totalTournaments,
            'tournaments_won'     => $tournamentsWon,
            'tournaments_top3'    => $tournamentsTop3,
            'total_prize_won'     => $totalPrizeWon,
            'total_rating_points' => $totalRatingPoints,
            'win_rate'            => $totalTournaments > 0 ? round(($tournamentsWon / $totalTournaments) * 100, 2) : 0,
            'top3_rate'           => $totalTournaments > 0 ? round(($tournamentsTop3 / $totalTournaments) * 100, 2) : 0,
            'win_rate_by_type'    => $winRateByType,
        ];
    }

    /**
     * Get league statistics for a player
     */
    public function getLeagueStats(User $player): array
    {
        $ratings = Rating::where('user_id', $player->id)
            ->with('league.game')
            ->get()
        ;

        $leagueStats = [];

        foreach ($ratings as $rating) {
            $matches = MatchGame::where('status', GameStatus::COMPLETED)
                ->where(function ($q) use ($rating) {
                    $q
                        ->where('first_rating_id', $rating->id)
                        ->orWhere('second_rating_id', $rating->id)
                    ;
                })
                ->get()
            ;

            $wins = $matches->where('winner_rating_id', $rating->id)->count();
            $total = $matches->count();

            $leagueStats[] = [
                'league_id'      => $rating->league_id,
                'league_name'    => $rating->league->name,
                'game_name'      => $rating->league->game->name,
                'game_type'      => $rating->league->game->type,
                'rating'         => $rating->rating,
                'position'       => $rating->position,
                'matches_played' => $total,
                'matches_won'    => $wins,
                'win_rate'       => $total > 0 ? round(($wins / $total) * 100, 2) : 0,
                'is_active'      => $rating->is_active,
            ];
        }

        return $leagueStats;
    }

    /**
     * Get official ratings for a player
     */
    public function getOfficialRatings(User $player): array
    {
        $officialRatings = OfficialRatingPlayer::where('user_id', $player->id)
            ->with(['officialRating'])
            ->where('is_active', true)
            ->get()
        ;

        return $officialRatings->map(function ($ratingPlayer) {
            return [
                'official_rating_id' => $ratingPlayer->official_rating_id,
                'rating_name'        => $ratingPlayer->officialRating->name,
                'game_name'          => $ratingPlayer->officialRating->game_type->name,
                'rating_points'      => $ratingPlayer->rating_points,
                'position'           => $ratingPlayer->position,
                'division'           => $ratingPlayer->getDivision(),
                'tournaments_played' => $ratingPlayer->tournaments_played,
                'tournaments_won'    => $ratingPlayer->tournaments_won,
                'win_rate'           => $ratingPlayer->win_rate,
                'last_tournament_at' => $ratingPlayer->last_tournament_at,
                'total_prize_amount' => $ratingPlayer->total_prize_amount,
                'total_money_earned' => $ratingPlayer->total_money_earned,
            ];
        })->toArray();
    }

    /**
     * Get recent tournaments for a player
     */
    public function getRecentTournaments(User $player, int $limit = 10): array
    {
        $recentTournaments = TournamentPlayer::where('user_id', $player->id)
            ->with(['tournament.city.country', 'tournament.club', 'tournament.game'])
            ->join('tournaments', 'tournament_players.tournament_id', '=', 'tournaments.id')
            ->orderBy('tournaments.end_date', 'desc')
            ->limit($limit)
            ->get()
        ;

        return $recentTournaments->map(function ($tp) {
            return [
                'tournament_id'   => $tp->tournament->id,
                'tournament_name' => $tp->tournament->name,
                'game_name'       => $tp->tournament->game->name,
                'city'            => $tp->tournament->city?->name,
                'country'         => $tp->tournament->city?->country?->name,
                'club'            => $tp->tournament->club?->name,
                'start_date'      => $tp->tournament->start_date,
                'end_date'        => $tp->tournament->end_date,
                'position'        => $tp->position,
                'prize_amount'    => $tp->prize_amount,
                'rating_points'   => $tp->rating_points,
                'players_count'   => $tp->tournament->players_count,
            ];
        })->toArray();
    }

    /**
     * Get recent tournament matches for a player
     */
    public function getRecentMatches(User $player, int $limit = 20): array
    {
        $recentMatches = TournamentMatch::where('status', MatchStatus::COMPLETED)
            ->where(function ($q) use ($player) {
                $q
                    ->where('player1_id', $player->id)
                    ->orWhere('player2_id', $player->id)
                ;
            })
            ->with([
                'player1',
                'player2',
                'tournament.game',
                'clubTable.club',
            ])
            ->orderBy('completed_at', 'desc')
            ->limit($limit)
            ->get()
        ;

        return $recentMatches->map(function ($match) use ($player) {
            $isPlayer1 = $match->player1_id === $player->id;
            $won = $match->winner_id === $player->id;
            $opponent = $isPlayer1 ? $match->player2 : $match->player1;

            return [
                'match_id'        => $match->id,
                'tournament_name' => $match->tournament->name,
                'game_name'       => $match->tournament->game->name,
                'match_stage'     => $match->stage?->value,
                'match_round'     => $match->round?->value,
                'opponent'        => $opponent ? $opponent->full_name : 'BYE',
                'opponent_id'     => $opponent?->id,
                'score'           => $isPlayer1
                    ? $match->player1_score.':'.$match->player2_score
                    : $match->player2_score.':'.$match->player1_score,
                'won'             => $won,
                'races_to'        => $match->races_to,
                'club'            => $match->clubTable?->club?->name,
                'completed_at'    => $match->completed_at,
            ];
        })->toArray();
    }

    /**
     * Get head-to-head statistics between two players
     */
    public function getHeadToHeadStats(User $player1, User $player2): array
    {
        // Get all tournament matches between these two players
        $matches = TournamentMatch::where('status', MatchStatus::COMPLETED)
            ->where(function ($q) use ($player1, $player2) {
                $q->where(function ($q1) use ($player1, $player2) {
                    $q1
                        ->where('player1_id', $player1->id)
                        ->where('player2_id', $player2->id)
                    ;
                })->orWhere(function ($q2) use ($player1, $player2) {
                    $q2
                        ->where('player1_id', $player2->id)
                        ->where('player2_id', $player1->id)
                    ;
                });
            })
            ->with(['tournament.game', 'clubTable.club'])
            ->orderBy('completed_at', 'desc')
            ->get()
        ;

        $player1Wins = 0;
        $player2Wins = 0;
        $player1GamesWon = 0;
        $player2GamesWon = 0;
        $matchHistory = [];

        foreach ($matches as $match) {
            $player1IsFirst = $match->player1_id === $player1->id;

            if ($match->winner_id === $player1->id) {
                $player1Wins++;
            } else {
                $player2Wins++;
            }

            if ($player1IsFirst) {
                $player1GamesWon += $match->player1_score;
                $player2GamesWon += $match->player2_score;
            } else {
                $player1GamesWon += $match->player2_score;
                $player2GamesWon += $match->player1_score;
            }

            $matchHistory[] = [
                'match_id'        => $match->id,
                'tournament_name' => $match->tournament->name,
                'game_name'       => $match->tournament->game->name,
                'match_stage'     => $match->stage?->value,
                'match_round'     => $match->round?->value,
                'player1_score'   => $player1IsFirst ? $match->player1_score : $match->player2_score,
                'player2_score'   => $player1IsFirst ? $match->player2_score : $match->player1_score,
                'winner_id'       => $match->winner_id,
                'races_to'        => $match->races_to,
                'club'            => $match->clubTable?->club?->name,
                'completed_at'    => $match->completed_at,
            ];
        }

        $totalMatches = $matches->count();

        return [
            'summary'            => [
                'total_matches'     => $totalMatches,
                'player1_wins'      => $player1Wins,
                'player2_wins'      => $player2Wins,
                'player1_win_rate'  => $totalMatches > 0 ? round(($player1Wins / $totalMatches) * 100, 2) : 0,
                'player2_win_rate'  => $totalMatches > 0 ? round(($player2Wins / $totalMatches) * 100, 2) : 0,
                'player1_games_won' => $player1GamesWon,
                'player2_games_won' => $player2GamesWon,
                'games_difference'  => $player1GamesWon - $player2GamesWon,
            ],
            'players'            => [
                'player1' => [
                    'id'        => $player1->id,
                    'full_name' => $player1->full_name,
                ],
                'player2' => [
                    'id'        => $player2->id,
                    'full_name' => $player2->full_name,
                ],
            ],
            'match_history'      => $matchHistory,
            'by_tournament_type' => $this->getHeadToHeadByTournamentType($matches, $player1->id),
        ];
    }

    /**
     * Get head-to-head stats grouped by tournament type
     */
    private function getHeadToHeadByTournamentType($matches, int $player1Id): array
    {
        return $matches
            ->groupBy('tournament.tournament_type')
            ->map(function ($typeMatches, $type) use ($player1Id) {
                $total = $typeMatches->count();
                $wins = $typeMatches->where('winner_id', $player1Id)->count();

                return [
                    'tournament_type'  => $type,
                    'total_matches'    => $total,
                    'player1_wins'     => $wins,
                    'player2_wins'     => $total - $wins,
                    'player1_win_rate' => $total > 0 ? round(($wins / $total) * 100, 2) : 0,
                ];
            })
            ->toArray()
        ;
    }

    /**
     * Get player achievements
     */
    public function getAchievements(User $player): array
    {
        $achievements = [];

        // Tournament achievements
        $tournamentPlayers = TournamentPlayer::where('user_id', $player->id)->get();
        $tournamentsWon = $tournamentPlayers->where('position', 1)->count();

        if ($tournamentsWon >= 10) {
            $achievements[] = [
                'type'        => 'tournament',
                'name'        => 'Tournament Master',
                'description' => 'Won 10 or more tournaments',
                'icon'        => 'trophy',
            ];
        } elseif ($tournamentsWon >= 5) {
            $achievements[] = [
                'type'        => 'tournament',
                'name'        => 'Tournament Expert',
                'description' => 'Won 5 or more tournaments',
                'icon'        => 'trophy',
            ];
        } elseif ($tournamentsWon >= 1) {
            $achievements[] = [
                'type'        => 'tournament',
                'name'        => 'Tournament Winner',
                'description' => 'Won at least 1 tournament',
                'icon'        => 'trophy',
            ];
        }

        // Rating achievements
        $topRatings = OfficialRatingPlayer::where('user_id', $player->id)
            ->where('position', '<=', 10)
            ->count()
        ;

        if ($topRatings > 0) {
            $achievements[] = [
                'type'        => 'rating',
                'name'        => 'Top 10 Player',
                'description' => 'Reached top 10 in official rating',
                'icon'        => 'star',
            ];
        }

        // Tournament match achievements
        $totalTournamentMatches = TournamentMatch::where('status', MatchStatus::COMPLETED)
            ->where(function ($q) use ($player) {
                $q
                    ->where('player1_id', $player->id)
                    ->orWhere('player2_id', $player->id)
                ;
            })
            ->count()
        ;

        if ($totalTournamentMatches >= 1000) {
            $achievements[] = [
                'type'        => 'matches',
                'name'        => 'Tournament Veteran',
                'description' => 'Played 1000+ tournament matches',
                'icon'        => 'zap',
            ];
        } elseif ($totalTournamentMatches >= 500) {
            $achievements[] = [
                'type'        => 'matches',
                'name'        => 'Tournament Regular',
                'description' => 'Played 500+ tournament matches',
                'icon'        => 'zap',
            ];
        } elseif ($totalTournamentMatches >= 100) {
            $achievements[] = [
                'type'        => 'matches',
                'name'        => 'Tournament Competitor',
                'description' => 'Played 100+ tournament matches',
                'icon'        => 'zap',
            ];
        }

        return $achievements;
    }
}
