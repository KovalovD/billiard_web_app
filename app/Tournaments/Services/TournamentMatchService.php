<?php

namespace App\Tournaments\Services;

use App\Tournaments\Enums\EliminationRound;
use App\Tournaments\Enums\MatchStage;
use App\Tournaments\Enums\MatchStatus;
use App\Tournaments\Enums\TournamentType;
use App\Tournaments\Models\Tournament;
use App\Tournaments\Models\TournamentMatch;
use App\Tournaments\Models\TournamentPlayer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class TournamentMatchService
{
    private const int MAX_TIEBREAKER_ROUNDS = 3;

    /**
     * Start a match
     * @throws Throwable
     */
    public function startMatch(TournamentMatch $match, array $data): TournamentMatch
    {
        return DB::transaction(static function () use ($match, $data) {
            if ($match->status !== MatchStatus::PENDING && $match->status !== MatchStatus::READY) {
                throw new RuntimeException('Match cannot be started in current status');
            }

            if (!$match->player1_id || !$match->player2_id) {
                throw new RuntimeException('Both players must be assigned before starting the match');
            }

            $match->update([
                'status'        => MatchStatus::IN_PROGRESS,
                'started_at'    => now(),
                'club_table_id' => $data['club_table_id'] ?? null,
                'stream_url'    => $data['stream_url'] ?? null,
                'admin_notes'   => $data['admin_notes'] ?? $match->admin_notes,
            ]);

            return $match->fresh(['player1', 'player2', 'clubTable']);
        });
    }

    /**
     * Finish a match
     * @throws Throwable
     */
    public function finishMatch(TournamentMatch $match, array $data): array
    {
        return DB::transaction(function () use ($match, $data) {
            if ($match->status !== MatchStatus::IN_PROGRESS && $match->status !== MatchStatus::VERIFICATION && $data['admin_notes'] !== 'Walkover') {
                throw new RuntimeException('Match must be in progress or verification to be finished');
            }

            $racesTo = $match->races_to ?? $match->tournament->races_to;

            // Validate scores
            if ($data['player1_score'] < $racesTo && $data['player2_score'] < $racesTo) {
                throw new RuntimeException("At least one player must reach $racesTo races to win");
            }

            if ($data['player1_score'] === $data['player2_score']) {
                throw new RuntimeException('Match cannot end in a tie');
            }

            // Determine winner and loser
            $winnerId = $data['player1_score'] > $data['player2_score']
                ? $match->player1_id
                : $match->player2_id;

            $loserId = $winnerId === $match->player1_id
                ? $match->player2_id
                : $match->player1_id;

            $gameType = $match->tournament->game->type->value ?? 'pool';
            if (!empty($data['frame_scores']) && $gameType !== 'pool') {
                $this->validateFrameScores($match, $data);
            }

            // Clear frame_scores for pool games
            if ($gameType === 'pool') {
                $data['frame_scores'] = null;
            }

            $match->update([
                'player1_score' => $data['player1_score'],
                'player2_score' => $data['player2_score'],
                'frame_scores'  => $data['frame_scores'] ?? null,
                'winner_id'     => $winnerId,
                'status'        => MatchStatus::COMPLETED,
                'completed_at'  => now(),
                'admin_notes'   => $data['admin_notes'] ?? $match->admin_notes,
            ]);

            // Track affected matches
            $affectedMatchIds = [];

            // Calculate and update player positions
            $this->updatePlayerPositions($match, $winnerId, $loserId);

            // Check if this is an Olympic tournament match that advances to Olympic stage
            if ($this->isOlympicAdvancingMatch($match)) {
                $olympicMatchId = $this->progressWinnerToOlympicStage($match);
                if ($olympicMatchId) {
                    $affectedMatchIds[] = $olympicMatchId;
                }
            } elseif ($match->next_match_id) {
                $this->progressWinnerToNextMatch($match);
                $affectedMatchIds[] = $match->next_match_id;
            }

            // Update standings based on match type
            if (isset($match->metadata['is_tiebreaker']) && $match->metadata['is_tiebreaker']) {
                $this->updateTiebreakerStandings($match);
            } else {
                $this->updateRoundRobinStandings($match);
            }

            // Handle loser progression for double elimination
            if ($match->loser_next_match_id) {
                $this->progressLoserToNextMatch($match);
                $affectedMatchIds[] = $match->loser_next_match_id;

                // Check for walkovers in the lower bracket after loser progression
                $walkoverMatchIds = $this->checkAndProcessLowerBracketWalkovers($match->loser_next_match_id);
                $affectedMatchIds = array_merge($affectedMatchIds, $walkoverMatchIds);
            }

            return [
                'match'            => $match->fresh(['player1', 'player2', 'winner', 'nextMatch']),
                'affected_matches' => array_unique($affectedMatchIds),
            ];
        });
    }

    /**
     * Check and process walkovers in lower bracket matches
     */
    private function checkAndProcessLowerBracketWalkovers(int $lowerMatchId): array
    {
        $affectedMatchIds = [];
        $lowerMatch = TournamentMatch::find($lowerMatchId);

        if (!$lowerMatch) {
            return $affectedMatchIds;
        }

        // Find all matches that feed into this lower bracket match
        $feederMatches = TournamentMatch::where('tournament_id', $lowerMatch->tournament_id)
            ->where('loser_next_match_id', $lowerMatchId)
            ->get();

        // If we have exactly 2 feeder matches and both are completed
        if ($feederMatches->count() === 2 &&
            $feederMatches->every(fn($m) => $m->status === MatchStatus::COMPLETED)) {

            // Check if lower bracket match has both players
            $hasPlayer1 = !is_null($lowerMatch->player1_id);
            $hasPlayer2 = !is_null($lowerMatch->player2_id);

            // If missing at least one player, it's a walkover
            if (!$hasPlayer1 || !$hasPlayer2) {
                // Determine the winner (the only player present)
                $winnerId = $hasPlayer1 ? $lowerMatch->player1_id : $lowerMatch->player2_id;

                if ($winnerId) {
                    // Process walkover
                    $walkoverResult = $this->processWalkover($lowerMatch, $winnerId);
                    $affectedMatchIds = array_merge($affectedMatchIds, $walkoverResult['affected_matches']);
                }
            }
        }

        return $affectedMatchIds;
    }

    /**
     * Process a walkover match
     */
    private function processWalkover(TournamentMatch $match, int $winnerId): array
    {
        $affectedMatchIds = [];

        // Update match as walkover
        $match->update([
            'winner_id'     => $winnerId,
            'status'        => MatchStatus::COMPLETED,
            'completed_at'  => now(),
            'admin_notes'   => 'Walkover - opponent did not show',
            'player1_score' => $match->player1_id === $winnerId ? ($match->races_to ?? $match->tournament->races_to) : 0,
            'player2_score' => $match->player2_id === $winnerId ? ($match->races_to ?? $match->tournament->races_to) : 0,
        ]);

        // Update player position for walkover
        $this->updatePlayerPositions($match, $winnerId, null);

        // Progress winner to next match
        if ($match->next_match_id) {
            $this->progressWinnerToNextMatch($match);
            $affectedMatchIds[] = $match->next_match_id;

            // Recursively check for more walkovers
            $additionalWalkovers = $this->checkAndProcessLowerBracketWalkovers($match->next_match_id);
            $affectedMatchIds = array_merge($affectedMatchIds, $additionalWalkovers);
        }

        // If this match also has a loser progression (shouldn't happen for walkover, but just in case)
        if ($match->loser_next_match_id) {
            $affectedMatchIds[] = $match->loser_next_match_id;

            // Check for walkovers in that match too
            $additionalWalkovers = $this->checkAndProcessLowerBracketWalkovers($match->loser_next_match_id);
            $affectedMatchIds = array_merge($affectedMatchIds, $additionalWalkovers);
        }

        return [
            'match' => $match,
            'affected_matches' => array_unique($affectedMatchIds),
        ];
    }

    private function validateFrameScores(TournamentMatch $match, array $data): void
    {
        $frameScores = $data['frame_scores'] ?? [];
        $gameType = $match->tournament->game->type->value ?? 'pool';

        // Skip validation for pool games
        if ($gameType === 'pool') {
            return;
        }

        $player1Frames = 0;
        $player2Frames = 0;

        foreach ($frameScores as $frame) {
            if ($gameType === 'pyramid') {
                // In pyramid, one player must reach 8
                if ($frame['player1'] === 8 && $frame['player2'] < 8) {
                    $player1Frames++;
                } elseif ($frame['player2'] === 8 && $frame['player1'] < 8) {
                    $player2Frames++;
                } else {
                    throw new RuntimeException('Invalid pyramid frame score: one player must reach 8 balls');
                }
            } elseif ($gameType === 'snooker') {
                // In snooker, max is 147
                if ($frame['player1'] > 147 || $frame['player2'] > 147) {
                    throw new RuntimeException('Invalid snooker frame score: maximum is 147');
                }
                if ($frame['player1'] > $frame['player2']) {
                    $player1Frames++;
                } else {
                    $player2Frames++;
                }
            }
        }

        // Validate frame count matches score
        if ($player1Frames !== $data['player1_score'] || $player2Frames !== $data['player2_score']) {
            throw new RuntimeException('Frame scores do not match the match score');
        }
    }

    /**
     * Calculate and update player positions based on match result
     */
    private function updatePlayerPositions(TournamentMatch $match, int $winnerId, ?int $loserId): void
    {
        $tournament = $match->tournament;

        // Don't update positions for tiebreaker matches
        if (isset($match->metadata['is_tiebreaker']) && $match->metadata['is_tiebreaker']) {
            return;
        }

        // Handle special matches first
        if ($this->handleSpecialMatchPositions($match, $winnerId, $loserId)) {
            return;
        }

        // For Olympic tournaments, handle position differently
        if ($tournament->tournament_type === TournamentType::OLYMPIC_DOUBLE_ELIMINATION) {
            $this->handleOlympicPositions($match, $winnerId, $loserId);
            return;
        }

        // Calculate loser's position based on tournament type
        $loserPosition = $this->calculateLoserPosition($match, $tournament);

        if ($loserPosition !== null && $loserId !== null) {
            TournamentPlayer::where('tournament_id', $tournament->id)
                ->where('user_id', $loserId)
                ->update([
                    'position'          => $loserPosition,
                    'elimination_round' => $match->round,
                ])
            ;
        }
    }

    /**
     * Handle positions for special matches (finals, third place, grand finals)
     */
    private function handleSpecialMatchPositions(TournamentMatch $match, int $winnerId, ?int $loserId): bool
    {
        $tournament = $match->tournament;

        // Grand Finals
        if ($match->match_code === 'GF' || $match->round === EliminationRound::GRAND_FINALS) {
            TournamentPlayer::where('tournament_id', $tournament->id)
                ->where('user_id', $winnerId)
                ->update(['position' => 1])
            ;

            if ($loserId) {
                TournamentPlayer::where('tournament_id', $tournament->id)
                    ->where('user_id', $loserId)
                    ->update(['position' => 2])
                ;
            }

            return true;
        }

        // Grand Finals Reset
        if ($match->match_code === 'GF_RESET') {
            TournamentPlayer::where('tournament_id', $tournament->id)
                ->where('user_id', $winnerId)
                ->update(['position' => 1])
            ;

            if ($loserId) {
                TournamentPlayer::where('tournament_id', $tournament->id)
                    ->where('user_id', $loserId)
                    ->update(['position' => 2])
                ;
            }

            return true;
        }

        // Third Place Match (excluding Olympic third place which is handled separately)
        if (($match->round === EliminationRound::THIRD_PLACE || $match->stage === MatchStage::THIRD_PLACE)
            && !str_starts_with($match->match_code, 'OS_')) {
            TournamentPlayer::where('tournament_id', $tournament->id)
                ->where('user_id', $winnerId)
                ->update(['position' => 3])
            ;

            if ($loserId) {
                TournamentPlayer::where('tournament_id', $tournament->id)
                    ->where('user_id', $loserId)
                    ->update(['position' => 4])
                ;
            }

            return true;
        }

        // Single Elimination Finals (excluding Olympic)
        if ($match->round === EliminationRound::FINALS && $match->stage === MatchStage::BRACKET
            && $tournament->tournament_type !== TournamentType::OLYMPIC_DOUBLE_ELIMINATION) {
            $isDoubleElim = in_array($tournament->tournament_type->value, [
                TournamentType::DOUBLE_ELIMINATION->value,
                TournamentType::DOUBLE_ELIMINATION_FULL->value,
            ], true);

            if (!$isDoubleElim) {
                TournamentPlayer::where('tournament_id', $tournament->id)
                    ->where('user_id', $winnerId)
                    ->update(['position' => 1])
                ;

                if ($loserId) {
                    TournamentPlayer::where('tournament_id', $tournament->id)
                        ->where('user_id', $loserId)
                        ->update(['position' => 2])
                    ;
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Handle positions for Olympic tournament matches
     */
    private function handleOlympicPositions(TournamentMatch $match, int $winnerId, ?int $loserId): void
    {
        $tournament = $match->tournament;

        // Check if this is an Olympic stage match
        $isOlympicStage = isset($match->metadata['olympic_stage']) && $match->metadata['olympic_stage'] === 'second';

        if ($isOlympicStage) {
            // Olympic stage finals
            if ($match->round === EliminationRound::FINALS) {
                TournamentPlayer::where('tournament_id', $tournament->id)
                    ->where('user_id', $winnerId)
                    ->update(['position' => 1])
                ;

                if ($loserId) {
                    TournamentPlayer::where('tournament_id', $tournament->id)
                        ->where('user_id', $loserId)
                        ->update(['position' => 2])
                    ;
                }
            } // Olympic stage third place
            elseif ($match->match_code === 'OS_3RD' || $match->stage === MatchStage::THIRD_PLACE) {
                TournamentPlayer::where('tournament_id', $tournament->id)
                    ->where('user_id', $winnerId)
                    ->update(['position' => 3])
                ;

                if ($loserId) {
                    TournamentPlayer::where('tournament_id', $tournament->id)
                        ->where('user_id', $loserId)
                        ->update(['position' => 4])
                    ;
                }
            } // Other Olympic stage eliminations
            else {
                $position = $this->calculateOlympicStagePosition($match);
                if ($position !== null && $loserId !== null) {
                    TournamentPlayer::where('tournament_id', $tournament->id)
                        ->where('user_id', $loserId)
                        ->update([
                            'position'          => $position,
                            'elimination_round' => $match->round,
                        ])
                    ;
                }
            }
        } else {
            // First stage eliminations
            $position = $this->calculateOlympicFirstStagePosition($match, $tournament);
            if ($position !== null && $loserId !== null) {
                TournamentPlayer::where('tournament_id', $tournament->id)
                    ->where('user_id', $loserId)
                    ->update([
                        'position'          => $position,
                        'elimination_round' => $match->round,
                    ])
                ;
            }
        }
    }

    /**
     * Calculate position for Olympic stage elimination
     */
    private function calculateOlympicStagePosition(TournamentMatch $match): ?int
    {
        // Extract round number from match code
        preg_match('/OS_R(\d+)M/', $match->match_code, $matches);
        $roundNumber = isset($matches[1]) ? (int) $matches[1] : 0;

        // Get the Olympic phase size from tournament
        $olympicPhaseSize = $match->tournament->olympic_phase_size ?? 8;
        $totalRounds = (int) log($olympicPhaseSize, 2);

        // Calculate position based on when eliminated
        $roundsFromEnd = $totalRounds - $roundNumber;

        return match ($roundsFromEnd) {
            2 => 5,  // Eliminated in quarterfinals (5-8)
            3 => 9,  // Eliminated in round of 16 (9-16)
            default => null
        };
    }

    /**
     * Calculate position for Olympic first stage elimination
     */
    private function calculateOlympicFirstStagePosition(TournamentMatch $match, Tournament $tournament): ?int
    {
        $olympicPhaseSize = $tournament->olympic_phase_size ?? 8;

        // Players eliminated in first stage are positioned after Olympic stage participants
        $basePosition = $olympicPhaseSize + 1;

        // If eliminated from lower bracket in first stage
        if ($match->bracket_side === 'lower') {
            // Get total confirmed players
            $totalPlayers = $tournament->players()->where('status', 'confirmed')->count();

            // Extract round number from match code
            preg_match('/FS_LB_R(\d+)M/', $match->match_code, $matches);
            $roundNumber = isset($matches[1]) ? (int) $matches[1] : 0;

            if ($roundNumber === 0) {
                return $basePosition;
            }

            // Calculate bracket size
            $bracketSize = 2 ** ceil(log($totalPlayers, 2));

            // Get the number of upper bracket rounds before Olympic phase
            $upperRoundsBeforeOlympic = (int) (log($bracketSize, 2) - log($olympicPhaseSize / 2, 2));

            // Get lower bracket structure
            $lowerStructure = $this->getLowerBracketStructure($bracketSize);

            // Count first stage lower bracket rounds
            $firstStageLowerRounds = 0;

            foreach ($lowerStructure as $round => $data) {
                // Check if this round is part of first stage
                $isFirstStage = false;

                if ($data['type'] === 'initial') {
                    // Initial round is always first stage
                    $isFirstStage = true;
                } elseif (isset($data['upper_round'])) {
                    // Check if the corresponding upper round is before Olympic phase
                    $isFirstStage = $data['upper_round'] <= $upperRoundsBeforeOlympic;
                } else {
                    // Regular rounds between drops - check if we're still in first stage
                    // by looking at the previous drop round
                    for ($r = $round - 1; $r >= 1; $r--) {
                        if (isset($lowerStructure[$r]['upper_round'])) {
                            $isFirstStage = $lowerStructure[$r]['upper_round'] <= $upperRoundsBeforeOlympic;
                            break;
                        }
                    }
                }

                if ($isFirstStage) {
                    $firstStageLowerRounds++;
                } else {
                    break;
                }
            }

            // Calculate position based on which round of first stage lower bracket
            if ($firstStageLowerRounds > 0) {
                // Players eliminated later get higher positions (closer to Olympic phase)
                $positionRange = $totalPlayers - $olympicPhaseSize;
                $positionIncrement = floor($positionRange / $firstStageLowerRounds);

                // Calculate position based on round number
                $roundFromStart = $roundNumber - 1;
                $position = $basePosition + ($roundFromStart * $positionIncrement);

                // Ensure position doesn't exceed total players
                return min($position, $totalPlayers);
            }

            return $basePosition;
        }

        // Upper bracket eliminations in first stage don't get final positions
        // (they drop to lower bracket)
        return null;
    }

    /**
     * Get lower bracket structure for a given bracket size
     */
    private function getLowerBracketStructure(int $bracketSize): array
    {
        return app(TournamentBracketService::class)->getLowerBracketStructure($bracketSize);
    }

    /**
     * Calculate loser's position based on match and tournament type
     */
    private function calculateLoserPosition(TournamentMatch $match, Tournament $tournament): ?int
    {
        $tournamentType = $tournament->tournament_type;

        // Single Elimination
        if ($tournamentType === TournamentType::SINGLE_ELIMINATION) {
            return $this->calculateSingleEliminationPosition($match->round);
        }

        // Double Elimination
        if (in_array($tournamentType->value, [
            TournamentType::DOUBLE_ELIMINATION->value,
            TournamentType::DOUBLE_ELIMINATION_FULL->value,
        ], true)) {
            return $this->calculateDoubleEliminationPosition($match, $tournament);
        }

        return null;
    }

    /**
     * Calculate position for single elimination
     */
    private function calculateSingleEliminationPosition(?EliminationRound $round): ?int
    {
        return match ($round) {
            EliminationRound::FINALS => 2,
            EliminationRound::SEMIFINALS => 3, // 3-4 (third place match determines exact position)
            EliminationRound::QUARTERFINALS => 5, // 5-8
            EliminationRound::ROUND_16 => 9, // 9-16
            EliminationRound::ROUND_32 => 17, // 17-32
            EliminationRound::ROUND_64 => 33, // 33-64
            EliminationRound::ROUND_128 => 65, // 65-128
            default => null,
        };
    }

    /**
     * Calculate position for double elimination
     */
    private function calculateDoubleEliminationPosition(TournamentMatch $match, Tournament $tournament): ?int
    {
        // Lower bracket eliminations
        if ($match->stage === MatchStage::LOWER_BRACKET || $match->bracket_side === 'lower') {
            return $this->calculateLowerBracketPosition($match, $tournament);
        }

        // Upper bracket eliminations (players drop to lower bracket, not eliminated)
        // In double elimination, losing in upper bracket doesn't give you a final position yet
        return null;
    }

    /**
     * Calculate position when eliminated from lower bracket
     */
    private function calculateLowerBracketPosition(TournamentMatch $match, Tournament $tournament): ?int
    {
        // Extract the round number from match_code (e.g., "LB_R1M1" -> 1)
        preg_match('/LB_R(\d+)M/', $match->match_code, $matches);
        $lowerRoundNumber = isset($matches[1]) ? (int) $matches[1] : 0;

        if ($lowerRoundNumber === 0) {
            // Fallback to round enum if can't parse
            return $match->round === EliminationRound::FINALS ? 3 : null;
        }

        // Get total confirmed players to determine bracket size
        $totalPlayers = $tournament->players()->where('status', 'confirmed')->count();
        $bracketSize = 2 ** ceil(log($totalPlayers, 2));

        // Get position mapping based on bracket size
        $positionMap = $this->getCompleteLowerBracketPositionMap($bracketSize);

        return $positionMap[$lowerRoundNumber] ?? null;
    }

    /**
     * Get complete position mapping for lower bracket rounds
     */
    private function getCompleteLowerBracketPositionMap(int $bracketSize): array
    {
        return match ($bracketSize) {
            4 => [
                1 => 4,  // LB Round 1 (initial)
                2 => 3,  // LB Round 2 (final)
            ],
            8 => [
                1 => 7,  // LB Round 1 (initial) - losers get 7-8
                2 => 6,  // LB Round 2 (drop) - losers get 6
                3 => 5,  // LB Round 3 (regular) - losers get 5
                4 => 3,  // LB Round 4 (final) - loser gets 3
            ],
            16 => [
                1 => 13, // LB Round 1 (initial) - losers get 13-16
                2 => 11, // LB Round 2 (drop) - losers get 11-12
                3 => 9,  // LB Round 3 (regular) - losers get 9-10
                4 => 7,  // LB Round 4 (drop) - losers get 7-8
                5 => 5,  // LB Round 5 (regular) - losers get 5-6
                6 => 3,  // LB Round 6 (final) - loser gets 3
            ],
            32 => [
                1 => 25, // LB Round 1 (initial) - losers get 25-32
                2 => 21, // LB Round 2 (drop) - losers get 21-24
                3 => 17, // LB Round 3 (regular) - losers get 17-20
                4 => 13, // LB Round 4 (drop) - losers get 13-16
                5 => 9,  // LB Round 5 (regular) - losers get 9-12
                6 => 7,  // LB Round 6 (drop) - losers get 7-8
                7 => 5,  // LB Round 7 (regular) - losers get 5-6
                8 => 3,  // LB Round 8 (final) - loser gets 3
            ],
            64 => [
                1  => 49, // LB Round 1 - losers get 49-64
                2  => 41, // LB Round 2 - losers get 41-48
                3  => 33, // LB Round 3 - losers get 33-40
                4  => 25, // LB Round 4 - losers get 25-32
                5  => 17, // LB Round 5 - losers get 17-24
                6  => 13, // LB Round 6 - losers get 13-16
                7  => 9,  // LB Round 7 - losers get 9-12
                8  => 7,  // LB Round 8 - losers get 7-8
                9  => 5,  // LB Round 9 - losers get 5-6
                10 => 3,  // LB Round 10 - loser gets 3
            ],
            128 => [
                1  => 97, // LB Round 1 - losers get 97-128
                2  => 81, // LB Round 2 - losers get 81-96
                3  => 65, // LB Round 3 - losers get 65-80
                4  => 49, // LB Round 4 - losers get 49-64
                5  => 33, // LB Round 5 - losers get 33-48
                6  => 25, // LB Round 6 - losers get 25-32
                7  => 17, // LB Round 7 - losers get 17-24
                8  => 13, // LB Round 8 - losers get 13-16
                9  => 9,  // LB Round 9 - losers get 9-12
                10 => 7,  // LB Round 10 - losers get 7-8
                11 => 5,  // LB Round 11 - losers get 5-6
                12 => 3,  // LB Round 12 - loser gets 3
            ],
            default => []
        };
    }

    /**
     * Check if match advances to Olympic stage
     */
    private function isOlympicAdvancingMatch(TournamentMatch $match): bool
    {
        return isset($match->metadata['advances_to_olympic']) && $match->metadata['advances_to_olympic'] === true;
    }

    /**
     * Progress winner to Olympic stage
     */
    private function progressWinnerToOlympicStage(TournamentMatch $match): ?int
    {
        if (!$match->winner_id || !isset($match->metadata['olympic_position'])) {
            return null;
        }

        $olympicPosition = $match->metadata['olympic_position'];
        $tournament = $match->tournament;

        // Find the corresponding Olympic stage match
        $olympicMatch = $this->findOlympicStageMatch($tournament, $olympicPosition);

        if (!$olympicMatch) {
            return null;
        }

        // Assign winner to the Olympic match
        if (!$olympicMatch->player1_id) {
            $olympicMatch->player1_id = $match->winner_id;
        } elseif (!$olympicMatch->player2_id) {
            $olympicMatch->player2_id = $match->winner_id;
        }

        // Update status to ready if both players are set
        if ($olympicMatch->player1_id && $olympicMatch->player2_id) {
            $olympicMatch->status = MatchStatus::READY;
        }

        $olympicMatch->save();

        return $olympicMatch->id;
    }

    /**
     * Find Olympic stage match by position
     */
    private function findOlympicStageMatch(Tournament $tournament, int $position): ?TournamentMatch
    {
        // Olympic matches are paired: positions 0,1 go to first match, 2,3 to second, etc.
        $matchIndex = floor($position / 2);
        $matchNumber = $matchIndex + 1;

        return TournamentMatch::where('tournament_id', $tournament->id)
            ->where('match_code', 'OS_R1M'.$matchNumber)
            ->whereJsonContains('metadata->olympic_stage', 'second')
            ->first()
        ;
    }

    /**
     * Progress winner to next match
     */
    private function progressWinnerToNextMatch(TournamentMatch $match): void
    {
        if (!$match->next_match_id || !$match->winner_id) {
            return;
        }

        $nextMatch = TournamentMatch::find($match->next_match_id);

        if (!$nextMatch) {
            return;
        }

        // Determine which slot the winner should go to
        if ($nextMatch->previous_match1_id === $match->id) {
            $nextMatch->player1_id = $match->winner_id;
        } elseif ($nextMatch->previous_match2_id === $match->id) {
            $nextMatch->player2_id = $match->winner_id;
        } else {
            // Fallback: fill empty slot
            if (!$nextMatch->player1_id) {
                $nextMatch->player1_id = $match->winner_id;
            } else {
                $nextMatch->player2_id = $match->winner_id;
            }
        }

        // Update status to ready if both players are set
        if ($nextMatch->player1_id && $nextMatch->player2_id) {
            $nextMatch->status = MatchStatus::READY;
        }

        $nextMatch->save();
    }

    /** 4️⃣ Update TB stats after every TB match & decide if the block is resolved
     * @throws Throwable
     */
    private function updateTiebreakerStandings(TournamentMatch $match): void
    {
        if (!($match->metadata['is_tiebreaker'] ?? false)) {
            return;
        }

        $tournament = $match->tournament;
        $winnerId = $match->winner_id;
        $loserId = $winnerId === $match->player1_id ? $match->player2_id : $match->player1_id;
        $diff = abs($match->player1_score - $match->player2_score);

        TournamentPlayer::where('tournament_id', $tournament->id)
            ->where('user_id', $winnerId)
            ->incrementEach(['tiebreaker_wins' => 1, 'tiebreaker_games_diff' => $diff])
        ;

        TournamentPlayer::where('tournament_id', $tournament->id)
            ->where('user_id', $loserId)
            ->increment('tiebreaker_games_diff', -$diff)
        ;

        // all matches for *this* TB block finished?
        $group = $match->metadata['tied_group'];
        $round = $match->metadata['tiebreaker_round'];

        $open = $tournament
            ->matches()
            ->whereJsonContains('metadata->is_tiebreaker', true)
            ->whereJsonContains('metadata->tiebreaker_round', $round)
            ->whereJsonContains('metadata->tied_group', $group)
            ->whereIn('status', [MatchStatus::PENDING, MatchStatus::READY, MatchStatus::IN_PROGRESS])
            ->exists()
        ;

        if (!$open) {
            $this->resolveTiebreakerGroup($tournament, $group, $round);
        }
    }

    /** 5️⃣ Analyse a finished TB block; spawn another round only when still 3-way-tied
     * @throws Throwable
     */
    private function resolveTiebreakerGroup(Tournament $tournament, string $group, int $round): void
    {
        $playerIds = $tournament
            ->matches()
            ->whereJsonContains('metadata->is_tiebreaker', true)
            ->whereJsonContains('metadata->tied_group', $group)
            ->pluck('player1_id')
            ->merge(
                $tournament
                    ->matches()
                    ->whereJsonContains('metadata->is_tiebreaker', true)
                    ->whereJsonContains('metadata->tied_group', $group)
                    ->pluck('player2_id'),
            )
            ->unique()
        ;

        $players = TournamentPlayer::where('tournament_id', $tournament->id)
            ->whereIn('user_id', $playerIds)
            ->get()
        ;

        $stillTied = $players
            ->groupBy(fn($p) => $p->tiebreaker_wins.'_'.$p->tiebreaker_games_diff)
            ->filter(fn($grp) => $grp->count() >= 3)
        ;

        if ($stillTied->isNotEmpty() && $round < self::MAX_TIEBREAKER_ROUNDS) {
            $this->generateTiebreakerMatches($tournament, $stillTied);
        } else {
            // either resolved or cap reached → recalc final standings
            $this->calculateRoundRobinFinalPositions($tournament);
        }
    }

    /** 3️⃣ Spawn a new TB round for every tieBlock (3+ people)
     * @throws Throwable
     */
    private function generateTiebreakerMatches(Tournament $tournament, Collection $blocks): void
    {
        DB::transaction(static function () use ($tournament, $blocks) {
            // Get max tiebreaker round using database-agnostic approach
            $existingRounds = $tournament
                ->matches()
                ->whereJsonContains('metadata->is_tiebreaker', true)
                ->get()
                ->pluck('metadata.tiebreaker_round')
                ->filter()
                ->max()
            ;

            $nextRound = ($existingRounds ?? 0) + 1;

            foreach ($blocks as $key => $players) {

                // do NOT reset TB stats if we already ran at least once for this block
                if ($nextRound === 1) {
                    foreach ($players as $p) {
                        $p->update(['tiebreaker_wins' => 0, 'tiebreaker_games_diff' => 0]);
                    }
                }

                $num = 1;
                foreach ($players as $i => $p1) {
                    foreach ($players->slice($i + 1) as $p2) {
                        TournamentMatch::create([
                            'tournament_id' => $tournament->id,
                            'match_code'    => sprintf('TB_R%d_%s_M%d', $nextRound, str_replace('_', '-', $key),
                                $num++),
                            'stage'         => MatchStage::PLAYOFF,
                            'round'         => EliminationRound::GROUPS,
                            'player1_id'    => $p1->user_id,
                            'player2_id'    => $p2->user_id,
                            'races_to'      => $tournament->races_to,
                            'status'        => MatchStatus::READY,
                            'metadata'      => [
                                'is_tiebreaker'    => true,
                                'tiebreaker_round' => $nextRound,
                                'tied_group'       => $key,
                            ],
                        ]);
                    }
                }
            }
        });
    }

    /** 2️⃣ Final-standing calculation (entry-point & fall-back after every TB resolution)
     * @throws Throwable
     */
    private function calculateRoundRobinFinalPositions(Tournament $tournament): void
    {
        // Wait if *any* TB matches are still open
        $waiting = $tournament
            ->matches()
            ->whereJsonContains('metadata->is_tiebreaker', true)
            ->whereIn('status', [MatchStatus::PENDING, MatchStatus::READY, MatchStatus::IN_PROGRESS])
            ->exists()
        ;
        if ($waiting) {
            return;
        }

        /** @var Collection<TournamentPlayer> $players */
        $players = $tournament->players()->where('status', 'confirmed')->get();

        /* -------------------------------------------------------------------
         | A.  detect 3-way-or-more ties **after** taking *existing* TB stats
         |     into account (wins & diff). Only those groups need new TB.
         *-------------------------------------------------------------------*/
        $tieBlocks = $players
            ->groupBy(fn($p) => implode('_', [
                $p->group_wins,
                $p->group_games_diff,
                $p->tiebreaker_wins,
                $p->tiebreaker_games_diff,
            ]))
            ->filter(fn($grp) => $grp->count() >= 3)
        ;

        if ($tieBlocks->isNotEmpty()) {
            $this->generateTiebreakerMatches($tournament, $tieBlocks);
            return; // standings will be recalculated after TB round finishes
        }

        /* -------------------------------------------------------------------
         | B.  order players (main wins > TB wins > TB diff > main diff > H2H
         *-------------------------------------------------------------------*/
        $ranked = $players->sort(function ($a, $b) use ($tournament) {
            return
                [$b->group_wins, $b->tiebreaker_wins, $b->tiebreaker_games_diff, $b->group_games_diff]
                <=> [$a->group_wins, $a->tiebreaker_wins, $a->tiebreaker_games_diff, $a->group_games_diff]
                    ?: $this->compareHeadToHead($a, $b, $tournament);
        })->values();

        /* -------------------------------------------------------------------
         | C.  persist final positions
         *-------------------------------------------------------------------*/
        foreach ($ranked as $idx => $pl) {
            $pl->update([
                'position'              => $idx + 1,
                'group_position'        => $idx + 1,
                'tiebreaker_wins'       => 0,
                'tiebreaker_games_diff' => 0,
            ]);
        }
    }

    private function compareHeadToHead(TournamentPlayer $a, TournamentPlayer $b, Tournament $t): int
    {
        $tb = TournamentMatch::where('tournament_id', $t->id)
            ->whereJsonContains('metadata->is_tiebreaker', true)
            ->where(function ($q) use ($a, $b) {
                $q->where(function ($s) use ($a, $b) {
                    $s->where('player1_id', $a->user_id)->where('player2_id', $b->user_id);
                })->orWhere(function ($s) use ($a, $b) {
                    $s->where('player1_id', $b->user_id)->where('player2_id', $a->user_id);
                });
            })
            ->where('status', MatchStatus::COMPLETED)
            ->orderBy('created_at', 'desc')
            ->first()
        ;
        if ($tb) {
            return $tb->winner_id === $a->user_id ? -1 : 1;
        }

        $reg = TournamentMatch::where('tournament_id', $t->id)
            ->where(function ($q) {
                $q
                    ->whereNull('metadata->is_tiebreaker')
                    ->orWhere(function ($subQ) {
                        if ($this->isPostgres()) {
                            $subQ->whereRaw("(metadata->>'is_tiebreaker')::boolean = false");
                        } else {
                            $subQ->whereRaw("JSON_EXTRACT(metadata, '$.is_tiebreaker') = false");
                        }
                    })
                ;
            })
            ->where(function ($q) use ($a, $b) {
                $q->where(function ($s) use ($a, $b) {
                    $s->where('player1_id', $a->user_id)->where('player2_id', $b->user_id);
                })->orWhere(function ($s) use ($a, $b) {
                    $s->where('player1_id', $b->user_id)->where('player2_id', $a->user_id);
                });
            })
            ->where('status', MatchStatus::COMPLETED)
            ->first()
        ;
        if (!$reg) {
            return $a->seed_number - $b->seed_number;
        }
        return $reg->winner_id === $a->user_id ? -1 : 1;
    }

    /**
     * Check if using PostgreSQL
     */
    private function isPostgres(): bool
    {
        return DB::connection()->getDriverName() === 'pgsql';
    }

    /** 1️⃣ Update normal group-stage standings after a RR match is completed
     * @throws Throwable
     */
    private function updateRoundRobinStandings(TournamentMatch $match): void
    {
        $tournament = $match->tournament;
        if ($tournament->tournament_type !== TournamentType::ROUND_ROBIN) {
            return;
        }

        $winnerId = $match->winner_id;
        $loserId = $winnerId === $match->player1_id ? $match->player2_id : $match->player1_id;
        $diff = abs($match->player1_score - $match->player2_score);

        // wins / losses / frame-diff in the **main** round-robin
        TournamentPlayer::where('tournament_id', $tournament->id)
            ->where('user_id', $winnerId)
            ->incrementEach(['group_wins' => 1, 'group_games_diff' => $diff])
        ;

        TournamentPlayer::where('tournament_id', $tournament->id)
            ->where('user_id', $loserId)
            ->incrementEach(['group_losses' => 1, 'group_games_diff' => -$diff])
        ;

        // if no more group matches → evaluate standings / kick off TB
        $open = $tournament
            ->matches()
            ->whereIn('status', [MatchStatus::PENDING, MatchStatus::READY, MatchStatus::IN_PROGRESS])
            ->where(function ($query) {
                $query
                    ->whereNull('metadata->is_tiebreaker')
                    ->orWhere(function ($q) {
                        if ($this->isPostgres()) {
                            $q->whereRaw("(metadata->>'is_tiebreaker')::boolean = false");
                        } else {
                            $q->whereRaw("JSON_EXTRACT(metadata, '$.is_tiebreaker') = false");
                        }
                    })
                ;
            })
            ->count()
        ;

        if ($open === 0) {
            $this->calculateRoundRobinFinalPositions($tournament);
        }
    }

    /**
     * Progress loser to next match (for double elimination)
     */
    private function progressLoserToNextMatch(TournamentMatch $match): void
    {
        if (!$match->loser_next_match_id || !$match->winner_id) {
            return;
        }

        $loserId = $match->winner_id === $match->player1_id
            ? $match->player2_id
            : $match->player1_id;

        $loserMatch = TournamentMatch::find($match->loser_next_match_id);

        if (!$loserMatch) {
            return;
        }

        // Use position hint if available
        $position = $match->loser_next_match_position ?? null;

        if ($position === 1) {
            $loserMatch->player1_id = $loserId;
        } elseif ($position === 2) {
            $loserMatch->player2_id = $loserId;
        } else {
            // Default behavior: fill empty slot
            if (!$loserMatch->player1_id) {
                $loserMatch->player1_id = $loserId;
            } else {
                $loserMatch->player2_id = $loserId;
            }
        }

        // Update status to ready if both players are set
        if ($loserMatch->player1_id && $loserMatch->player2_id) {
            $loserMatch->status = MatchStatus::READY;
        }

        $loserMatch->save();
    }

    /**
     * Update match details
     * @throws Throwable
     */
    public function updateMatch(TournamentMatch $match, array $data): array
    {
        return DB::transaction(function () use ($match, $data) {
            $affectedMatchIds = [];

            if (isset($data['frame_scores'])) {
                $gameType = $match->tournament->game->type->value ?? 'pool';

                // Clear frame_scores for pool games
                if ($gameType === 'pool') {
                    $data['frame_scores'] = null;
                } elseif (!empty($data['frame_scores'])) {
                    $this->validateFrameScores($match, [
                        'frame_scores' => $data['frame_scores'],
                        'player1_score' => $data['player1_score'] ?? $match->player1_score,
                        'player2_score' => $data['player2_score'] ?? $match->player2_score,
                    ]);
                }
            }

            // Handle player changes
            if (isset($data['player1_id']) || isset($data['player2_id'])) {
                // If match was already completed with different players, need to revert progression
                if ($match->status === MatchStatus::COMPLETED && $match->winner_id) {
                    $this->revertMatchProgression($match);
                    $this->revertPlayerPositions($match);
                }

                // Clear winner if players changed
                if (($data['player1_id'] ?? $match->player1_id) !== $match->player1_id ||
                    ($data['player2_id'] ?? $match->player2_id) !== $match->player2_id) {
                    $data['winner_id'] = null;
                    $data['status'] = MatchStatus::PENDING;
                    $data['player1_score'] = 0;
                    $data['player2_score'] = 0;
                    $data['started_at'] = null;
                    $data['completed_at'] = null;
                }
            }

            // Handle status change
            if (isset($data['status']) && $data['status'] !== $match->status) {
                // If changing from completed to another status, revert progression
                if ($match->status === MatchStatus::COMPLETED) {
                    $this->revertMatchProgression($match);
                    $this->revertPlayerPositions($match);
                    $data['winner_id'] = null;
                    $data['completed_at'] = null;
                }

                // If changing to completed, validate scores
                if ($data['status'] === MatchStatus::COMPLETED) {
                    $player1Score = $data['player1_score'] ?? $match->player1_score;
                    $player2Score = $data['player2_score'] ?? $match->player2_score;

                    if ($player1Score === $player2Score) {
                        throw new RuntimeException('Match cannot end in a tie');
                    }

                    $racesTo = $match->races_to ?? $match->tournament->races_to;
                    if ($player1Score < $racesTo && $player2Score < $racesTo) {
                        throw new RuntimeException("At least one player must reach $racesTo races to win");
                    }

                    $data['winner_id'] = $player1Score > $player2Score
                        ? ($data['player1_id'] ?? $match->player1_id)
                        : ($data['player2_id'] ?? $match->player2_id);
                    $data['completed_at'] = now();
                }

                // Handle status-specific updates
                switch ($data['status']) {
                    case MatchStatus::IN_PROGRESS:
                        $data['started_at'] = $data['started_at'] ?? now();
                        break;
                    case MatchStatus::READY:
                        // Check if both players are set
                        $player1Id = $data['player1_id'] ?? $match->player1_id;
                        $player2Id = $data['player2_id'] ?? $match->player2_id;
                        if (!$player1Id || !$player2Id) {
                            $data['status'] = MatchStatus::PENDING;
                        }
                        break;
                }
            }

            // If updating scores without status change
            if (isset($data['player1_score'], $data['player2_score']) &&
                !isset($data['status']) &&
                $match->status === MatchStatus::COMPLETED) {

                if ($data['player1_score'] === $data['player2_score']) {
                    throw new RuntimeException('Match cannot end in a tie');
                }

                $racesTo = $match->races_to ?? $match->tournament->races_to;
                if ($data['player1_score'] < $racesTo && $data['player2_score'] < $racesTo) {
                    // If neither player reached races_to, change status back to in_progress
                    $data['status'] = MatchStatus::IN_PROGRESS;
                    $data['winner_id'] = null;
                    $data['completed_at'] = null;
                } else {
                    $data['winner_id'] = $data['player1_score'] > $data['player2_score']
                        ? ($data['player1_id'] ?? $match->player1_id)
                        : ($data['player2_id'] ?? $match->player2_id);

                    // If winner changed, need to update bracket progression
                    if ($data['winner_id'] !== $match->winner_id) {
                        $this->revertMatchProgression($match);
                        $this->revertPlayerPositions($match);
                    }
                }
            }

            // Update the match
            $match->update($data);

            // Handle progression if match is completed
            if ($match->status === MatchStatus::COMPLETED && $match->winner_id) {
                $loserId = $match->winner_id === $match->player1_id
                    ? $match->player2_id
                    : $match->player1_id;

                // Calculate positions
                $this->updatePlayerPositions($match, $match->winner_id, $loserId);

                // Handle bracket progression
                if ($this->isOlympicAdvancingMatch($match)) {
                    $olympicMatchId = $this->progressWinnerToOlympicStage($match);
                    if ($olympicMatchId) {
                        $affectedMatchIds[] = $olympicMatchId;
                    }
                } elseif ($match->next_match_id) {
                    $this->progressWinnerToNextMatch($match);
                    $affectedMatchIds[] = $match->next_match_id;
                }

                // Handle loser progression for double elimination
                if ($match->loser_next_match_id) {
                    $this->progressLoserToNextMatch($match);
                    $affectedMatchIds[] = $match->loser_next_match_id;

                    // Check for walkovers in the lower bracket
                    $walkoverMatchIds = $this->checkAndProcessLowerBracketWalkovers($match->loser_next_match_id);
                    $affectedMatchIds = array_merge($affectedMatchIds, $walkoverMatchIds);
                }
            }

            // If both players are set and status is pending, change to ready
            if ($match->player1_id && $match->player2_id && $match->status === MatchStatus::PENDING) {
                $match->update(['status' => MatchStatus::READY]);
            }

            return [
                'match'            => $match->fresh(['player1', 'player2', 'clubTable']),
                'affected_matches' => array_unique($affectedMatchIds),
            ];
        });
    }

    /**
     * Revert match progression (when result changes)
     */
    private function revertMatchProgression(TournamentMatch $match): void
    {
        // Handle Olympic tournament reversion
        if ($this->isOlympicAdvancingMatch($match)) {
            $this->revertOlympicProgression($match);
            // Don't return here - still need to handle loser progression
        }

        // Clear winner from next match
        if ($match->next_match_id) {
            $nextMatch = TournamentMatch::find($match->next_match_id);
            if ($nextMatch) {
                if ($nextMatch->player1_id === $match->winner_id) {
                    $nextMatch->player1_id = null;
                } elseif ($nextMatch->player2_id === $match->winner_id) {
                    $nextMatch->player2_id = null;
                }
                $nextMatch->status = MatchStatus::PENDING;
                $nextMatch->save();

                // Recursively revert if next match was completed
                if ($nextMatch->winner_id) {
                    $this->revertMatchProgression($nextMatch);
                }
            }
        }

        // Clear loser from loser bracket match
        if ($match->loser_next_match_id) {
            $loserId = $match->winner_id === $match->player1_id
                ? $match->player2_id
                : $match->player1_id;

            $loserMatch = TournamentMatch::find($match->loser_next_match_id);
            if ($loserMatch) {
                if ($loserMatch->player1_id === $loserId) {
                    $loserMatch->player1_id = null;
                } elseif ($loserMatch->player2_id === $loserId) {
                    $loserMatch->player2_id = null;
                }
                $loserMatch->status = MatchStatus::PENDING;
                $loserMatch->save();

                // Recursively revert if loser match was completed
                if ($loserMatch->winner_id) {
                    $this->revertMatchProgression($loserMatch);
                }
            }
        }
    }

    /**
     * Revert Olympic stage progression
     */
    private function revertOlympicProgression(TournamentMatch $match): void
    {
        if (!$match->winner_id || !isset($match->metadata['olympic_position'])) {
            return;
        }

        $olympicPosition = $match->metadata['olympic_position'];
        $tournament = $match->tournament;

        // Find the corresponding Olympic stage match
        $olympicMatch = $this->findOlympicStageMatch($tournament, $olympicPosition);

        if ($olympicMatch) {
            // Clear the winner from the Olympic match
            if ($olympicMatch->player1_id === $match->winner_id) {
                $olympicMatch->player1_id = null;
            } elseif ($olympicMatch->player2_id === $match->winner_id) {
                $olympicMatch->player2_id = null;
            }

            $olympicMatch->status = MatchStatus::PENDING;
            $olympicMatch->save();

            // Recursively revert if Olympic match was completed
            if ($olympicMatch->winner_id) {
                $this->revertMatchProgression($olympicMatch);
            }
        }
    }

    /**
     * Revert player positions when match result changes
     */
    private function revertPlayerPositions(TournamentMatch $match): void
    {
        if (!$match->winner_id) {
            return;
        }

        $loserId = $match->winner_id === $match->player1_id
            ? $match->player2_id
            : $match->player1_id;

        // Clear positions for both players from this match
        TournamentPlayer::where('tournament_id', $match->tournament_id)
            ->whereIn('user_id', [$match->winner_id, $loserId])
            ->where('elimination_round', $match->round)
            ->update([
                'position'          => null,
                'elimination_round' => null,
            ])
        ;
    }
}
