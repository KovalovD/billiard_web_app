<?php

namespace App\Widgets\Http\Controllers;

use App\Core\Models\ClubTable;
use App\Tournaments\Models\Tournament;
use App\Tournaments\Models\TournamentMatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TableMatchController
{
    /**
     * Get current match data for table
     */
    public function getCurrentMatch(Tournament $tournament, ClubTable $table): JsonResponse
    {
        $activeMatch = TournamentMatch::where('tournament_id', $tournament->id)
            ->where('club_table_id', $table->id)
            ->whereIn('status', ['in_progress', 'verification'])
            ->with(['player1', 'player2', 'tournament.game'])
            ->first()
        ;

        if (!$activeMatch) {
            return response()->json([
                'active_match' => null,
                'tournament'   => [
                    'id'        => $tournament->id,
                    'name'      => $tournament->name,
                    'races_to'  => $tournament->races_to,
                    'game_type' => $tournament->game->type->value ?? 'pool',
                ],
                'table'        => [
                    'id'   => $table->id,
                    'name' => $table->name,
                ],
            ]);
        }

        return response()->json([
            'active_match' => [
                'id'            => $activeMatch->id,
                'match_code'    => $activeMatch->match_code,
                'player1'       => $activeMatch->player1 ? [
                    'id'        => $activeMatch->player1->id,
                    'firstname' => $activeMatch->player1->firstname,
                    'lastname'  => $activeMatch->player1->lastname,
                    'full_name' => $activeMatch->player1->firstname.' '.$activeMatch->player1->lastname,
                ] : null,
                'player2'       => $activeMatch->player2 ? [
                    'id'        => $activeMatch->player2->id,
                    'firstname' => $activeMatch->player2->firstname,
                    'lastname'  => $activeMatch->player2->lastname,
                    'full_name' => $activeMatch->player2->firstname.' '.$activeMatch->player2->lastname,
                ] : null,
                'player1_score' => $activeMatch->player1_score ?? 0,
                'player2_score' => $activeMatch->player2_score ?? 0,
                'frame_scores'  => $activeMatch->frame_scores ?? [],
                'status'        => $activeMatch->status,
                'races_to'      => $activeMatch->races_to ?? $tournament->races_to,
            ],
            'tournament'   => [
                'id'        => $tournament->id,
                'name'      => $tournament->name,
                'races_to'  => $tournament->races_to,
                'game_type' => $tournament->game->type->value ?? 'pool',
            ],
            'table'        => [
                'id'   => $table->id,
                'name' => $table->name,
            ],
        ]);
    }

    /**
     * Update match score (players can only update scores)
     */
    public function updateScore(Request $request, Tournament $tournament, ClubTable $table): JsonResponse
    {
        $gameType = $tournament->game->type->value ?? 'pool';

        if ($gameType === 'pool') {
            return $this->updatePoolScore($request, $tournament, $table);
        }

        return $this->updateFrameScore($request, $tournament, $table);
    }

    /**
     * Update pool match score
     */
    private function updatePoolScore(Request $request, Tournament $tournament, ClubTable $table): JsonResponse
    {
        $request->validate([
            'player' => 'required|in:player1,player2',
            'action' => 'required|in:increment,decrement',
        ]);

        $activeMatch = TournamentMatch::where('tournament_id', $tournament->id)
            ->where('club_table_id', $table->id)
            ->whereIn('status', ['in_progress'])
            ->first()
        ;

        if (!$activeMatch) {
            return response()->json(['error' => 'No active match found'], 404);
        }

        $scoreField = $request->player.'_score';
        $currentScore = $activeMatch->$scoreField ?? 0;
        $racesTo = $activeMatch->races_to ?? $tournament->races_to;

        if ($request->action === 'increment') {
            $newScore = min($currentScore + 1, $racesTo);
        } else {
            $newScore = max($currentScore - 1, 0);
        }

        $activeMatch->$scoreField = $newScore;

        // Check if match should move to verification
        if ($newScore >= $racesTo) {
            $activeMatch->status = 'verification';
        }

        $activeMatch->save();

        return response()->json([
            'success'       => true,
            'player1_score' => $activeMatch->player1_score,
            'player2_score' => $activeMatch->player2_score,
            'frame_scores'  => [],
            'status'        => $activeMatch->status,
        ]);
    }

    /**
     * Update frame-based match score (pyramid/snooker)
     */
    private function updateFrameScore(Request $request, Tournament $tournament, ClubTable $table): JsonResponse
    {
        $gameType = $tournament->game->type->value;

        $request->validate([
            'frame_index'   => 'required|integer|min:0',
            'player1_score' => 'required|integer|min:0',
            'player2_score' => 'required|integer|min:0',
            'finish_frame'  => 'boolean',
        ]);

        $activeMatch = TournamentMatch::where('tournament_id', $tournament->id)
            ->where('club_table_id', $table->id)
            ->whereIn('status', ['in_progress'])
            ->first()
        ;

        if (!$activeMatch) {
            return response()->json(['error' => 'No active match found'], 404);
        }

        // Validate frame scores based on game type
        if ($gameType === 'pyramid') {
            if ($request->player1_score > 8 || $request->player2_score > 8) {
                return response()->json(['error' => 'Pyramid frame score cannot exceed 8'], 422);
            }
            if ($request->finish_frame &&
                !($request->player1_score === 8 && $request->player2_score < 8) &&
                !($request->player2_score === 8 && $request->player1_score < 8)) {
                return response()->json(['error' => 'In pyramid, one player must reach 8 balls to win the frame'], 422);
            }
        } elseif ($gameType === 'snooker') {
            if ($request->player1_score > 147 || $request->player2_score > 147) {
                return response()->json(['error' => 'Snooker frame score cannot exceed 147'], 422);
            }
            if ($request->finish_frame && $request->player1_score === $request->player2_score) {
                return response()->json(['error' => 'Frame cannot end in a tie'], 422);
            }
        }

        $frameScores = $activeMatch->frame_scores ?? [];
        $frameIndex = $request->frame_index;

        // Initialize frame if it doesn't exist
        if (!isset($frameScores[$frameIndex])) {
            $frameScores[$frameIndex] = [
                'player1' => 0,
                'player2' => 0,
                'finished' => false,
            ];
        }

        // Update frame scores
        $frameScores[$frameIndex]['player1'] = $request->player1_score;
        $frameScores[$frameIndex]['player2'] = $request->player2_score;

        if ($request->finish_frame) {
            $frameScores[$frameIndex]['finished'] = true;

            // Calculate match scores from frames
            $player1Frames = 0;
            $player2Frames = 0;

            foreach ($frameScores as $frame) {
                if (!($frame['finished'] ?? false)) {
                    continue;
                }

                if ($gameType === 'pyramid') {
                    if ($frame['player1'] === 8) {
                        $player1Frames++;
                    } elseif ($frame['player2'] === 8) {
                        $player2Frames++;
                    }
                } elseif ($gameType === 'snooker') {
                    if ($frame['player1'] > $frame['player2']) {
                        $player1Frames++;
                    } else {
                        $player2Frames++;
                    }
                }
            }

            $activeMatch->player1_score = $player1Frames;
            $activeMatch->player2_score = $player2Frames;

            // Check if match should move to verification
            $racesTo = $activeMatch->races_to ?? $tournament->races_to;
            if ($player1Frames >= $racesTo || $player2Frames >= $racesTo) {
                $activeMatch->status = 'verification';
            }
        }

        $activeMatch->frame_scores = $frameScores;
        $activeMatch->save();

        return response()->json([
            'success'       => true,
            'player1_score' => $activeMatch->player1_score,
            'player2_score' => $activeMatch->player2_score,
            'frame_scores'  => $activeMatch->frame_scores,
            'status'        => $activeMatch->status,
        ]);
    }
}
