<?php

namespace App\Tournaments\Services;

use App\Tournaments\Enums\EliminationRound;
use App\Tournaments\Enums\MatchStage;
use App\Tournaments\Enums\MatchStatus;
use App\Tournaments\Enums\TournamentType;
use App\Tournaments\Models\Tournament;
use App\Tournaments\Models\TournamentMatch;
use App\Tournaments\Models\TournamentPlayer;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class TournamentMatchService
{
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
            if ($match->status !== MatchStatus::IN_PROGRESS && $match->status !== MatchStatus::VERIFICATION) {
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

            $match->update([
                'player1_score' => $data['player1_score'],
                'player2_score' => $data['player2_score'],
                'winner_id'     => $winnerId,
                'status'        => MatchStatus::COMPLETED,
                'completed_at'  => now(),
                'admin_notes' => $data['admin_notes'] ?? $match->admin_notes,
            ]);

            // Track affected matches
            $affectedMatchIds = [];

            // Calculate and update player positions
            $this->updatePlayerPositions($match, $winnerId, $loserId);

            // Progress winner to next match if applicable
            if ($match->next_match_id) {
                $this->progressWinnerToNextMatch($match);
                $affectedMatchIds[] = $match->next_match_id;
            }

            // Handle loser progression for double elimination
            if ($match->loser_next_match_id) {
                $this->progressLoserToNextMatch($match);
                $affectedMatchIds[] = $match->loser_next_match_id;
            }

            return [
                'match'            => $match->fresh(['player1', 'player2', 'winner', 'nextMatch']),
                'affected_matches' => array_unique($affectedMatchIds),
            ];
        });
    }

    /**
     * Calculate and update player positions based on match result
     */
    private function updatePlayerPositions(TournamentMatch $match, int $winnerId, int $loserId): void
    {
        $tournament = $match->tournament;

        // Handle special matches first
        if ($this->handleSpecialMatchPositions($match, $winnerId, $loserId)) {
            return;
        }

        // Calculate loser's position based on tournament type
        $loserPosition = $this->calculateLoserPosition($match, $tournament);

        if ($loserPosition !== null) {
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
    private function handleSpecialMatchPositions(TournamentMatch $match, int $winnerId, int $loserId): bool
    {
        $tournament = $match->tournament;

        // Grand Finals
        if ($match->match_code === 'GF' || $match->round === EliminationRound::GRAND_FINALS) {
            TournamentPlayer::where('tournament_id', $tournament->id)
                ->where('user_id', $winnerId)
                ->update(['position' => 1])
            ;

            TournamentPlayer::where('tournament_id', $tournament->id)
                ->where('user_id', $loserId)
                ->update(['position' => 2])
            ;

            return true;
        }

        // Grand Finals Reset
        if ($match->match_code === 'GF_RESET') {
            // Winner is champion, loser is 2nd
            TournamentPlayer::where('tournament_id', $tournament->id)
                ->where('user_id', $winnerId)
                ->update(['position' => 1])
            ;

            TournamentPlayer::where('tournament_id', $tournament->id)
                ->where('user_id', $loserId)
                ->update(['position' => 2])
            ;

            return true;
        }

        // Third Place Match
        if ($match->round === EliminationRound::THIRD_PLACE || $match->stage === MatchStage::THIRD_PLACE) {
            TournamentPlayer::where('tournament_id', $tournament->id)
                ->where('user_id', $winnerId)
                ->update(['position' => 3])
            ;

            TournamentPlayer::where('tournament_id', $tournament->id)
                ->where('user_id', $loserId)
                ->update(['position' => 4])
            ;

            return true;
        }

        // Single Elimination Finals
        if ($match->round === EliminationRound::FINALS && $match->stage === MatchStage::BRACKET) {
            $isDoubleElim = in_array($tournament->tournament_type->value, [
                TournamentType::DOUBLE_ELIMINATION->value,
                TournamentType::DOUBLE_ELIMINATION_FULL->value,
            ], true);

            if (!$isDoubleElim) {
                TournamentPlayer::where('tournament_id', $tournament->id)
                    ->where('user_id', $winnerId)
                    ->update(['position' => 1])
                ;

                TournamentPlayer::where('tournament_id', $tournament->id)
                    ->where('user_id', $loserId)
                    ->update(['position' => 2])
                ;

                return true;
            }
        }

        return false;
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
            EliminationRound::SEMIFINALS => 3, // 3-4
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
        $upperRounds = (int) log($bracketSize, 2);
        $totalLowerRounds = ($upperRounds - 1) * 2;

        // Position mapping based on elimination round in lower bracket
        // The later you're eliminated in lower bracket, the higher your position
        $positionMap = $this->getLowerBracketPositionMap($bracketSize, $totalLowerRounds);

        return $positionMap[$lowerRoundNumber] ?? null;
    }

    /**
     * Get position mapping for lower bracket rounds
     */
    private function getLowerBracketPositionMap(int $bracketSize, int $totalLowerRounds): array
    {
        $map = [];
        $currentPosition = 3; // Start at 3rd place (lower bracket final loser)

        // Map positions from last round to first round
        for ($round = $totalLowerRounds; $round >= 1; $round--) {
            if ($round === $totalLowerRounds) {
                // Lower bracket finals - loser gets 3rd
                $map[$round] = 3;
            } elseif ($round === $totalLowerRounds - 1) {
                // Lower bracket semifinals - loser gets 4th
                $map[$round] = 4;
            } else {
                // Calculate positions for earlier rounds
                // Positions increase as we go back in rounds
                $isDropRound = $round % 2 === 1;

                if ($bracketSize <= 4) {
                    // For 4-player bracket
                    $map[1] = 4; // LB_R1 loser gets 4th
                    $map[2] = 3; // LB_R2 loser gets 3rd
                } elseif ($bracketSize <= 8) {
                    // For 8-player bracket
                    $positionsByRound = [
                        1 => 5, // LB_R1 losers get 5-6
                        2 => 5, // LB_R2 losers get 5-6
                        3 => 4, // LB_R3 loser gets 4th
                        4 => 3, // LB_R4 loser gets 3rd
                    ];
                    $map[$round] = $positionsByRound[$round] ?? 7;
                } elseif ($bracketSize <= 16) {
                    // For 16-player bracket
                    $positionsByRound = [
                        1 => 7, // LB_R1 losers get 7-8
                        2 => 7, // LB_R2 losers get 7-8
                        3 => 5, // LB_R3 losers get 5-6
                        4 => 5, // LB_R4 losers get 5-6
                        5 => 4, // LB_R5 loser gets 4th
                        6 => 3, // LB_R6 loser gets 3rd
                    ];
                    $map[$round] = $positionsByRound[$round] ?? 9;
                } else {
                    // For larger brackets, use a formula
                    $roundsFromEnd = $totalLowerRounds - $round;
                    if ($roundsFromEnd === 0) {
                        $map[$round] = 3;
                    } elseif ($roundsFromEnd === 1) {
                        $map[$round] = 4;
                    } elseif ($roundsFromEnd <= 3) {
                        $map[$round] = 5;
                    } elseif ($roundsFromEnd <= 5) {
                        $map[$round] = 7;
                    } elseif ($roundsFromEnd <= 7) {
                        $map[$round] = 9;
                    } elseif ($roundsFromEnd <= 9) {
                        $map[$round] = 13;
                    } else {
                        $map[$round] = 17;
                    }
                }
            }
        }

        return $map;
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
    public function updateMatch(TournamentMatch $match, array $data): TournamentMatch
    {
        return DB::transaction(function () use ($match, $data) {
            // If updating scores and match is completed, recalculate winner
            if (isset($data['player1_score'], $data['player2_score']) && $match->status === MatchStatus::COMPLETED) {
                if ($data['player1_score'] === $data['player2_score']) {
                    throw new RuntimeException('Match cannot end in a tie');
                }

                $data['winner_id'] = $data['player1_score'] > $data['player2_score']
                    ? $match->player1_id
                    : $match->player2_id;

                // If winner changed, need to update bracket progression
                if ($data['winner_id'] !== $match->winner_id) {
                    $this->revertMatchProgression($match);
                    $this->revertPlayerPositions($match);
                }
            }

            $match->update($data);

            // Re-progress if winner changed
            if (isset($data['winner_id']) && $match->status === MatchStatus::COMPLETED) {
                $loserId = $data['winner_id'] === $match->player1_id
                    ? $match->player2_id
                    : $match->player1_id;

                // Recalculate positions
                $this->updatePlayerPositions($match, $data['winner_id'], $loserId);

                $this->progressWinnerToNextMatch($match);
                if ($match->loser_next_match_id) {
                    $this->progressLoserToNextMatch($match);
                }
            }

            return $match->fresh();
        });
    }

    /**
     * Revert match progression (when result changes)
     */
    private function revertMatchProgression(TournamentMatch $match): void
    {
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
