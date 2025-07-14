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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PlayerService
{
    /**
     * Get aggregated statistics for all players
     */
    public function getAggregatedStats(array $filters = []): array
    {
        $query = User::query()
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

        // Get counts
        $totalPlayers = $query->count();

        // Tournament winners
        $tournamentWinners = (clone $query)
            ->whereHas('tournamentPlayers', function (Builder $q) {
                $q->where('position', 1);
            })
            ->count()
        ;

        // Top 10 rated players
        $top10Rated = (clone $query)
            ->whereHas('officialRatingPlayers', function (Builder $q) {
                $q
                    ->where('is_active', true)
                    ->where('position', '<=', 10)
                ;
            })
            ->count()
        ;

        // Players with city
        $playersWithCity = (clone $query)
            ->whereNotNull('home_city_id')
            ->count()
        ;

        // Players with club
        $playersWithClub = (clone $query)
            ->whereNotNull('home_club_id')
            ->count()
        ;

        // Count unique cities
        $uniqueCities = (clone $query)
            ->whereNotNull('home_city_id')
            ->distinct('home_city_id')
            ->count('home_city_id')
        ;

        // Count unique clubs
        $uniqueClubs = (clone $query)
            ->whereNotNull('home_club_id')
            ->distinct('home_club_id')
            ->count('home_club_id')
        ;

        return [
            'total_players'      => $totalPlayers,
            'tournament_winners' => $tournamentWinners,
            'top_10_rated'       => $top10Rated,
            'players_with_city'  => $playersWithCity,
            'players_with_club'  => $playersWithClub,
            'unique_cities'      => $uniqueCities,
            'unique_clubs'       => $uniqueClubs,
        ];
    }

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
                    'id'                 => $player1->id,
                    'full_name'          => $player1->full_name,
                    'tournament_picture' => $player1->getPicture($player1->tournament_picture) ?: $player1->getPicture($player1->picture),

                ],
                'player2' => [
                    'id'                 => $player2->id,
                    'full_name'          => $player2->full_name,
                    'tournament_picture' => $player2->getPicture($player2->tournament_picture) ?: $player2->getPicture($player2->picture),

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

    /**
     * Get detailed tournament match statistics for a player
     */
    public function getDetailedMatchStats(User $player): array
    {
        $allMatches = $this->getAllTournamentMatches($player);

        return [
            'overall_stats'        => $this->calculateOverallMatchStats($allMatches, $player->id),
            'frame_progression'    => $this->calculateFrameProgression($allMatches, $player->id),
            'performance_by_stage' => $this->calculatePerformanceByStage($allMatches, $player->id),
            'performance_by_round' => $this->calculatePerformanceByRound($allMatches, $player->id),
            'best_opponents'       => $this->calculateBestOpponents($allMatches, $player->id),
            'worst_opponents'      => $this->calculateWorstOpponents($allMatches, $player->id),
            'score_distribution'   => $this->calculateScoreDistribution($allMatches, $player->id),
            'comeback_stats'       => $this->calculateComebackStats($allMatches, $player->id),
            'performance_trends'   => $this->calculatePerformanceTrends($allMatches, $player->id),
            'game_type_stats'      => $this->calculateGameTypeStats($allMatches, $player->id),
        ];
    }

    /**
     * Get all tournament matches for a player
     */
    private function getAllTournamentMatches(User $player): Collection|array
    {
        return TournamentMatch::where('status', MatchStatus::COMPLETED)
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
            ->get()
        ;
    }

    /**
     * Calculate overall match statistics
     */
    private function calculateOverallMatchStats($matches, int $playerId): array
    {
        $totalMatches = $matches->count();
        $wins = 0;
        $losses = 0;
        $totalFramesWon = 0;
        $totalFramesLost = 0;
        $racesToWon = [];
        $scoreMargins = [];

        foreach ($matches as $match) {
            $isPlayer1 = $match->player1_id === $playerId;
            $playerScore = $isPlayer1 ? $match->player1_score : $match->player2_score;
            $opponentScore = $isPlayer1 ? $match->player2_score : $match->player1_score;

            if ($match->winner_id === $playerId) {
                $wins++;
                $scoreMargins[] = $playerScore - $opponentScore;
            } else {
                $losses++;
                $scoreMargins[] = $opponentScore - $playerScore;
            }

            $totalFramesWon += $playerScore;
            $totalFramesLost += $opponentScore;

            // Track performance by races_to value
            $racesTo = $match->races_to ?? $match->tournament->races_to;
            if (!isset($racesToWon[$racesTo])) {
                $racesToWon[$racesTo] = ['wins' => 0, 'total' => 0];
            }
            $racesToWon[$racesTo]['total']++;
            if ($match->winner_id === $playerId) {
                $racesToWon[$racesTo]['wins']++;
            }
        }

        return [
            'total_matches'           => $totalMatches,
            'wins'                    => $wins,
            'losses'                  => $losses,
            'win_rate'                => $totalMatches > 0 ? round(($wins / $totalMatches) * 100, 2) : 0,
            'total_frames_won'        => $totalFramesWon,
            'total_frames_lost'       => $totalFramesLost,
            'frame_win_rate'          => ($totalFramesWon + $totalFramesLost) > 0
                ? round(($totalFramesWon / ($totalFramesWon + $totalFramesLost)) * 100, 2)
                : 0,
            'avg_score_margin'        => count($scoreMargins) > 0
                ? round(array_sum($scoreMargins) / count($scoreMargins), 2)
                : 0,
            'performance_by_races_to' => $racesToWon,
        ];
    }

    /**
     * Calculate frame progression statistics
     */
    private function calculateFrameProgression($matches, int $playerId): array
    {
        $frameProgression = [];

        foreach ($matches as $match) {
            $isPlayer1 = $match->player1_id === $playerId;
            $playerScore = $isPlayer1 ? $match->player1_score : $match->player2_score;
            $opponentScore = $isPlayer1 ? $match->player2_score : $match->player1_score;
            $racesTo = $match->races_to ?? $match->tournament->races_to;

            // For 14+1 games, the scores represent balls potted, not frames
            $gameType = $match->tournament->game->name ?? '';
            if (str_contains($gameType, '14+1')) {
                // For 14+1, we track it as a single frame result
                $key = '1';
                if (!isset($frameProgression[$key])) {
                    $frameProgression[$key] = ['wins' => 0, 'total' => 0];
                }
                $frameProgression[$key]['total']++;
                if ($match->winner_id === $playerId) {
                    $frameProgression[$key]['wins']++;
                }
            } else {
                // For other games, track win rate at each frame count
                for ($frame = 1; $frame <= max($playerScore, $opponentScore); $frame++) {
                    $key = (string) $frame;
                    if (!isset($frameProgression[$key])) {
                        $frameProgression[$key] = ['wins' => 0, 'total' => 0];
                    }

                    // Only count frames where the player was still in the match
                    if ($frame <= $playerScore || $frame <= $opponentScore) {
                        $frameProgression[$key]['total']++;

                        // Count as win if player was ahead or won at this frame
                        if ($frame <= $playerScore && $match->winner_id === $playerId) {
                            $frameProgression[$key]['wins']++;
                        }
                    }
                }
            }
        }

        // Calculate win rates
        $result = [];
        foreach ($frameProgression as $frame => $stats) {
            $result[] = [
                'frame'    => (int) $frame,
                'matches'  => $stats['total'],
                'wins'     => $stats['wins'],
                'win_rate' => $stats['total'] > 0
                    ? round(($stats['wins'] / $stats['total']) * 100, 2)
                    : 0,
            ];
        }

        // Sort by frame number
        usort($result, static function ($a, $b) {
            return $a['frame'] - $b['frame'];
        });

        return $result;
    }

    /**
     * Calculate performance by tournament stage
     */
    private function calculatePerformanceByStage($matches, int $playerId): array
    {
        $stageStats = [];

        foreach ($matches as $match) {
            $stage = $match->stage?->value ?? 'unknown';

            if (!isset($stageStats[$stage])) {
                $stageStats[$stage] = [
                    'wins'        => 0,
                    'total'       => 0,
                    'frames_won'  => 0,
                    'frames_lost' => 0,
                ];
            }

            $isPlayer1 = $match->player1_id === $playerId;
            $playerScore = $isPlayer1 ? $match->player1_score : $match->player2_score;
            $opponentScore = $isPlayer1 ? $match->player2_score : $match->player1_score;

            $stageStats[$stage]['total']++;
            $stageStats[$stage]['frames_won'] += $playerScore;
            $stageStats[$stage]['frames_lost'] += $opponentScore;

            if ($match->winner_id === $playerId) {
                $stageStats[$stage]['wins']++;
            }
        }

        // Calculate rates
        $result = [];
        foreach ($stageStats as $stage => $stats) {
            $result[] = [
                'stage'          => $stage,
                'matches'        => $stats['total'],
                'wins'           => $stats['wins'],
                'win_rate'       => $stats['total'] > 0
                    ? round(($stats['wins'] / $stats['total']) * 100, 2)
                    : 0,
                'frame_win_rate' => ($stats['frames_won'] + $stats['frames_lost']) > 0
                    ? round(($stats['frames_won'] / ($stats['frames_won'] + $stats['frames_lost'])) * 100, 2)
                    : 0,
            ];
        }

        return $result;
    }

    /**
     * Calculate performance by tournament round
     */
    private function calculatePerformanceByRound($matches, int $playerId): array
    {
        $roundStats = [];

        foreach ($matches as $match) {
            $round = $match->round?->value ?? 'unknown';

            if (!isset($roundStats[$round])) {
                $roundStats[$round] = [
                    'wins'             => 0,
                    'total'            => 0,
                    'avg_score_margin' => [],
                ];
            }

            $isPlayer1 = $match->player1_id === $playerId;
            $playerScore = $isPlayer1 ? $match->player1_score : $match->player2_score;
            $opponentScore = $isPlayer1 ? $match->player2_score : $match->player1_score;

            $roundStats[$round]['total']++;

            if ($match->winner_id === $playerId) {
                $roundStats[$round]['wins']++;
                $roundStats[$round]['avg_score_margin'][] = $playerScore - $opponentScore;
            } else {
                $roundStats[$round]['avg_score_margin'][] = -($opponentScore - $playerScore);
            }
        }

        // Calculate averages
        $result = [];
        foreach ($roundStats as $round => $stats) {
            $result[] = [
                'round'            => $round,
                'matches'          => $stats['total'],
                'wins'             => $stats['wins'],
                'win_rate'         => $stats['total'] > 0
                    ? round(($stats['wins'] / $stats['total']) * 100, 2)
                    : 0,
                'avg_score_margin' => count($stats['avg_score_margin']) > 0
                    ? round(array_sum($stats['avg_score_margin']) / count($stats['avg_score_margin']), 2)
                    : 0,
            ];
        }

        return $result;
    }

    /**
     * Calculate best opponents (highest win rate against)
     */
    private function calculateBestOpponents($matches, int $playerId, int $minMatches = 3): array
    {
        $opponentStats = $this->aggregateOpponentStats($matches, $playerId);

        // Filter by minimum matches and sort by win rate
        $bestOpponents = [];
        foreach ($opponentStats as $opponentId => $stats) {
            if ($stats['total'] >= $minMatches) {
                $winRate = ($stats['wins'] / $stats['total']) * 100;
                $bestOpponents[] = [
                    'opponent_id'      => $opponentId,
                    'opponent_name'    => $stats['name'],
                    'matches'          => $stats['total'],
                    'wins'             => $stats['wins'],
                    'losses'           => $stats['total'] - $stats['wins'],
                    'win_rate'         => round($winRate, 2),
                    'avg_score_margin' => round($stats['score_margin'] / $stats['total'], 2),
                ];
            }
        }

        // Sort by win rate descending
        usort($bestOpponents, static function ($a, $b) {
            return $b['win_rate'] <=> $a['win_rate'];
        });

        return array_slice($bestOpponents, 0, 10);
    }

    /**
     * Aggregate statistics by opponent
     */
    private function aggregateOpponentStats($matches, int $playerId): array
    {
        $opponentStats = [];

        foreach ($matches as $match) {
            $isPlayer1 = $match->player1_id === $playerId;
            $opponentId = $isPlayer1 ? $match->player2_id : $match->player1_id;
            $opponent = $isPlayer1 ? $match->player2 : $match->player1;

            if (!$opponentId) {
                continue;
            } // Skip BYE matches

            if (!isset($opponentStats[$opponentId])) {
                $opponentStats[$opponentId] = [
                    'name'         => $opponent ? $opponent->full_name : 'Unknown',
                    'wins'         => 0,
                    'total'        => 0,
                    'score_margin' => 0,
                ];
            }

            $playerScore = $isPlayer1 ? $match->player1_score : $match->player2_score;
            $opponentScore = $isPlayer1 ? $match->player2_score : $match->player1_score;

            $opponentStats[$opponentId]['total']++;

            if ($match->winner_id === $playerId) {
                $opponentStats[$opponentId]['wins']++;
                $opponentStats[$opponentId]['score_margin'] += ($playerScore - $opponentScore);
            } else {
                $opponentStats[$opponentId]['score_margin'] -= ($opponentScore - $playerScore);
            }
        }

        return $opponentStats;
    }

    /**
     * Calculate worst opponents (lowest win rate against)
     */
    private function calculateWorstOpponents($matches, int $playerId, int $minMatches = 3): array
    {
        $opponentStats = $this->aggregateOpponentStats($matches, $playerId);

        // Filter by minimum matches and sort by win rate
        $worstOpponents = [];
        foreach ($opponentStats as $opponentId => $stats) {
            if ($stats['total'] >= $minMatches) {
                $winRate = ($stats['wins'] / $stats['total']) * 100;
                $worstOpponents[] = [
                    'opponent_id'      => $opponentId,
                    'opponent_name'    => $stats['name'],
                    'matches'          => $stats['total'],
                    'wins'             => $stats['wins'],
                    'losses'           => $stats['total'] - $stats['wins'],
                    'win_rate'         => round($winRate, 2),
                    'avg_score_margin' => round($stats['score_margin'] / $stats['total'], 2),
                ];
            }
        }

        // Sort by win rate ascending
        usort($worstOpponents, static function ($a, $b) {
            return $a['win_rate'] <=> $b['win_rate'];
        });

        return array_slice($worstOpponents, 0, 10);
    }

    /**
     * Calculate score distribution
     */
    private function calculateScoreDistribution($matches, int $playerId): array
    {
        $distribution = [];

        foreach ($matches as $match) {
            $isPlayer1 = $match->player1_id === $playerId;
            $playerScore = $isPlayer1 ? $match->player1_score : $match->player2_score;
            $opponentScore = $isPlayer1 ? $match->player2_score : $match->player1_score;
            $racesTo = $match->races_to ?? $match->tournament->races_to;

            $key = "{$playerScore}-{$opponentScore}";
            if (!isset($distribution[$key])) {
                $distribution[$key] = [
                    'player_score'   => $playerScore,
                    'opponent_score' => $opponentScore,
                    'races_to'       => $racesTo,
                    'count'          => 0,
                    'won'            => $match->winner_id === $playerId,
                ];
            }
            $distribution[$key]['count']++;
        }

        // Convert to array and sort
        $result = array_values($distribution);
        usort($result, static function ($a, $b) {
            if ($a['races_to'] !== $b['races_to']) {
                return $a['races_to'] - $b['races_to'];
            }
            return $b['count'] - $a['count'];
        });

        return array_slice($result, 0, 20); // Top 20 most common scores
    }

    /**
     * Calculate comeback statistics
     */
    private function calculateComebackStats($matches, int $playerId): array
    {
        $comebacks = 0;
        $comebackDetails = [];
        $biggestComeback = 0;

        foreach ($matches as $match) {
            $isPlayer1 = $match->player1_id === $playerId;
            $playerScore = $isPlayer1 ? $match->player1_score : $match->player2_score;
            $opponentScore = $isPlayer1 ? $match->player2_score : $match->player1_score;
            $racesTo = $match->races_to ?? $match->tournament->races_to;

            // Consider it a comeback if player was down by 3+ frames and won
            if ($match->winner_id === $playerId) {
                // Estimate deficit (this is simplified - real tracking would need frame-by-frame data)
                $potentialDeficit = min($opponentScore, $racesTo - 3);
                if ($potentialDeficit >= 3) {
                    $comebacks++;
                    $comebackDetails[] = [
                        'match_id'          => $match->id,
                        'tournament'        => $match->tournament->name,
                        'opponent'          => $isPlayer1 ? $match->player2?->full_name : $match->player1?->full_name,
                        'score'             => "{$playerScore}-{$opponentScore}",
                        'estimated_deficit' => $potentialDeficit,
                        'date'              => $match->completed_at,
                    ];

                    if ($potentialDeficit > $biggestComeback) {
                        $biggestComeback = $potentialDeficit;
                    }
                }
            }
        }

        return [
            'total_comebacks'          => $comebacks,
            'biggest_comeback_deficit' => $biggestComeback,
            'recent_comebacks'         => array_slice($comebackDetails, 0, 5),
        ];
    }

    /**
     * Calculate performance trends over time
     */
    private function calculatePerformanceTrends($matches, int $playerId): array
    {
        $monthlyStats = [];

        foreach ($matches as $match) {
            $monthKey = $match->completed_at->format('Y-m');

            if (!isset($monthlyStats[$monthKey])) {
                $monthlyStats[$monthKey] = [
                    'wins'        => 0,
                    'total'       => 0,
                    'frames_won'  => 0,
                    'frames_lost' => 0,
                ];
            }

            $isPlayer1 = $match->player1_id === $playerId;
            $playerScore = $isPlayer1 ? $match->player1_score : $match->player2_score;
            $opponentScore = $isPlayer1 ? $match->player2_score : $match->player1_score;

            $monthlyStats[$monthKey]['total']++;
            $monthlyStats[$monthKey]['frames_won'] += $playerScore;
            $monthlyStats[$monthKey]['frames_lost'] += $opponentScore;

            if ($match->winner_id === $playerId) {
                $monthlyStats[$monthKey]['wins']++;
            }
        }

        // Calculate rates and format
        $result = [];
        foreach ($monthlyStats as $month => $stats) {
            $result[] = [
                'month'          => $month,
                'matches'        => $stats['total'],
                'wins'           => $stats['wins'],
                'win_rate'       => $stats['total'] > 0
                    ? round(($stats['wins'] / $stats['total']) * 100, 2)
                    : 0,
                'frame_win_rate' => ($stats['frames_won'] + $stats['frames_lost']) > 0
                    ? round(($stats['frames_won'] / ($stats['frames_won'] + $stats['frames_lost'])) * 100, 2)
                    : 0,
            ];
        }

        // Sort by month
        usort($result, static function ($a, $b) {
            return strcmp($a['month'], $b['month']);
        });

        // Limit to last 12 months
        return array_slice($result, -12);
    }

    /**
     * Calculate statistics by game type
     */
    private function calculateGameTypeStats($matches, int $playerId): array
    {
        $gameTypeStats = [];

        foreach ($matches as $match) {
            $gameType = $match->tournament->game->name ?? 'Unknown';

            if (!isset($gameTypeStats[$gameType])) {
                $gameTypeStats[$gameType] = [
                    'wins'        => 0,
                    'total'       => 0,
                    'frames_won'  => 0,
                    'frames_lost' => 0,
                    'tournaments' => [],
                ];
            }

            $isPlayer1 = $match->player1_id === $playerId;
            $playerScore = $isPlayer1 ? $match->player1_score : $match->player2_score;
            $opponentScore = $isPlayer1 ? $match->player2_score : $match->player1_score;

            $gameTypeStats[$gameType]['total']++;
            $gameTypeStats[$gameType]['frames_won'] += $playerScore;
            $gameTypeStats[$gameType]['frames_lost'] += $opponentScore;
            $gameTypeStats[$gameType]['tournaments'][$match->tournament_id] = true;

            if ($match->winner_id === $playerId) {
                $gameTypeStats[$gameType]['wins']++;
            }
        }

        // Calculate rates
        $result = [];
        foreach ($gameTypeStats as $gameType => $stats) {
            $result[] = [
                'game_type'          => $gameType,
                'matches'            => $stats['total'],
                'wins'               => $stats['wins'],
                'win_rate'           => $stats['total'] > 0
                    ? round(($stats['wins'] / $stats['total']) * 100, 2)
                    : 0,
                'frame_win_rate'     => ($stats['frames_won'] + $stats['frames_lost']) > 0
                    ? round(($stats['frames_won'] / ($stats['frames_won'] + $stats['frames_lost'])) * 100, 2)
                    : 0,
                'tournaments_played' => count($stats['tournaments']),
            ];
        }

        return $result;
    }
}
