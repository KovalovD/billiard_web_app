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
            ->with(['player1', 'player2', 'tournament'])
            ->first()
        ;

        if (!$activeMatch) {
            return response()->json([
                'active_match' => null,
                'tournament'   => [
                    'id'       => $tournament->id,
                    'name'     => $tournament->name,
                    'races_to' => $tournament->races_to,
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
                'status'        => $activeMatch->status,
                'races_to'      => $tournament->races_to,
            ],
            'tournament'   => [
                'id'       => $tournament->id,
                'name'     => $tournament->name,
                'races_to' => $tournament->races_to,
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
        $racesTo = $tournament->races_to;

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
            'status'        => $activeMatch->status,
        ]);
    }
}
