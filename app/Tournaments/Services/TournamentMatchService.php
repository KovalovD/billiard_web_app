<?php

namespace App\Tournaments\Services;

use App\Tournaments\Enums\EliminationRound;
use App\Tournaments\Enums\MatchStatus;
use App\Tournaments\Enums\TournamentType;
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
    public function finishMatch(TournamentMatch $match, array $data): TournamentMatch
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
            ]);

            // Calculate and update player positions
            $this->updatePlayerPositions($match, $winnerId, $loserId);

            // Progress winner to next match if applicable
            $this->progressWinnerToNextMatch($match);

            // Handle loser progression for double elimination
            if ($match->loser_next_match_id) {
                $this->progressLoserToNextMatch($match);
            }

            return $match->fresh(['player1', 'player2', 'winner', 'nextMatch']);
        });
    }

    /**
     * Calculate and update player positions based on match result
     */
    private function updatePlayerPositions(TournamentMatch $match, int $winnerId, int $loserId): void
    {
        $tournament = $match->tournament;
        $tournamentType = $tournament->tournament_type;

        // Update loser's position based on round
        $loserPosition = $this->calculatePositionByRound($match->round, $tournamentType);

        if ($loserPosition !== null) {
            TournamentPlayer::where('tournament_id', $tournament->id)
                ->where('user_id', $loserId)
                ->update([
                    'position'          => $loserPosition,
                    'elimination_round' => $match->round,
                ])
            ;
        }

        // If this is the finals, also set winner's position
        if ($match->round === EliminationRound::FINALS) {
            TournamentPlayer::where('tournament_id', $tournament->id)
                ->where('user_id', $winnerId)
                ->update([
                    'position'          => 1,
                    'elimination_round' => EliminationRound::FINALS,
                ])
            ;
        }

        // Handle third place match
        if ($match->round === EliminationRound::THIRD_PLACE) {
            // Winner gets 3rd place
            TournamentPlayer::where('tournament_id', $tournament->id)
                ->where('user_id', $winnerId)
                ->update([
                    'position'          => 3,
                    'elimination_round' => EliminationRound::THIRD_PLACE,
                ])
            ;

            // Loser gets 4th place
            TournamentPlayer::where('tournament_id', $tournament->id)
                ->where('user_id', $loserId)
                ->update([
                    'position'          => 4,
                    'elimination_round' => EliminationRound::THIRD_PLACE,
                ])
            ;
        }
    }

    /**
     * Calculate position based on elimination round
     */
    private function calculatePositionByRound(?EliminationRound $round, TournamentType $tournamentType): ?int
    {
        if (!$round) {
            return null;
        }

        // For single elimination
        if (in_array($tournamentType->value, TournamentType::eliminationTypes(), true)) {
            return match ($round) {
                EliminationRound::FINALS => false ? 1 : 2,
                EliminationRound::SEMIFINALS => false ? null : 3,
                EliminationRound::QUARTERFINALS => false ? null : 5,
                EliminationRound::ROUND_16 => false ? null : 9,
                EliminationRound::ROUND_32 => false ? null : 17,
                EliminationRound::ROUND_64 => false ? null : 33,
                EliminationRound::ROUND_128 => false ? null : 65,
                default => null,
            };
        }

        // For group stage matches, positions are calculated differently
        return null;
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

        // Determine which player slot to fill
        if ($nextMatch->previous_match1_id === $match->id) {
            $nextMatch->player1_id = $match->winner_id;
        } elseif ($nextMatch->previous_match2_id === $match->id) {
            $nextMatch->player2_id = $match->winner_id;
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

        // Determine which player slot to fill
        if (!$loserMatch->player1_id) {
            $loserMatch->player1_id = $loserId;
        } else {
            $loserMatch->player2_id = $loserId;
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
