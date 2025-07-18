<?php

namespace App\Widgets\Http\Controllers;

use App\Core\Models\ClubTable;
use App\Tournaments\Models\Tournament;
use App\Tournaments\Models\TournamentMatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TableWidgetController
{
    /**
     * Get current match data for table widget
     */
    public function getCurrentMatch(Request $request, Tournament $tournament, ClubTable $table): JsonResponse
    {
        // Get active match on this table
        $activeMatch = TournamentMatch::where('tournament_id', $tournament->id)
            ->where('club_table_id', $table->id)
            ->whereIn('status', ['in_progress', 'verification'])
            ->with(['player1', 'player2', 'tournament.game'])
            ->first()
        ;

        // Get next scheduled match on this table
        $nextMatch = TournamentMatch::where('tournament_id', $tournament->id)
            ->where('club_table_id', $table->id)
            ->where('status', 'ready')
            ->with(['player1', 'player2'])
            ->orderBy('scheduled_at')
            ->first()
        ;

        // Get recent completed matches
        $recentMatches = TournamentMatch::where('tournament_id', $tournament->id)
            ->where('club_table_id', $table->id)
            ->where('status', 'completed')
            ->with(['player1', 'player2', 'winner'])
            ->orderBy('completed_at', 'desc')
            ->limit(3)
            ->get()
        ;

        $widgetData = [
            'tournament'     => [
                'id'        => $tournament->id,
                'name'      => $tournament->name,
                'races_to'  => $tournament->races_to,
                'game_name' => $tournament->game->name ?? null,
                'game_type' => $tournament->game->type->value ?? 'pool',
            ],
            'table'          => [
                'id'         => $table->id,
                'name'       => $table->name,
                'stream_url' => $table->stream_url,
            ],
            'active_match'   => $activeMatch ? [
                'id'             => $activeMatch->id,
                'match_code'     => $activeMatch->match_code,
                'player1'        => $activeMatch->player1 ? [
                    'id'        => $activeMatch->player1->id,
                    'firstname' => $activeMatch->player1->firstname,
                    'lastname'  => $activeMatch->player1->lastname,
                    'full_name' => $activeMatch->player1->firstname.' '.$activeMatch->player1->lastname,
                ] : null,
                'player2'        => $activeMatch->player2 ? [
                    'id'        => $activeMatch->player2->id,
                    'firstname' => $activeMatch->player2->firstname,
                    'lastname'  => $activeMatch->player2->lastname,
                    'full_name' => $activeMatch->player2->firstname.' '.$activeMatch->player2->lastname,
                ] : null,
                'player1_score'  => $activeMatch->player1_score,
                'player2_score'  => $activeMatch->player2_score,
                'frame_scores'   => $activeMatch->frame_scores ?? [],
                'current_frame'  => $this->getCurrentFrame($activeMatch),
                'status'         => $activeMatch->status,
                'status_display' => $activeMatch->status->value,
                'started_at'     => $activeMatch->started_at?->format('Y-m-d H:i:s'),
            ] : null,
            'next_match'     => $nextMatch ? [
                'id'           => $nextMatch->id,
                'match_code'   => $nextMatch->match_code,
                'player1_name' => $nextMatch->player1 ?
                    $nextMatch->player1->firstname.' '.$nextMatch->player1->lastname : 'TBD',
                'player2_name' => $nextMatch->player2 ?
                    $nextMatch->player2->firstname.' '.$nextMatch->player2->lastname : 'TBD',
                'scheduled_at' => $nextMatch->scheduled_at?->format('Y-m-d H:i:s'),
            ] : null,
            'recent_matches' => $recentMatches->map(function ($match) {
                return [
                    'winner_name'  => $match->winner ?
                        $match->winner->firstname.' '.$match->winner->lastname : null,
                    'score'        => $match->player1_score.' - '.$match->player2_score,
                    'completed_at' => $match->completed_at?->format('H:i'),
                ];
            }),
            'timestamp'      => now()->timestamp,
        ];

        return response()->json($widgetData);
    }

    /**
     * Get current frame info
     */
    private function getCurrentFrame(?TournamentMatch $match): ?array
    {
        if (!$match || empty($match->frame_scores)) {
            return null;
        }

        $frames = $match->frame_scores;
        $currentIndex = count($frames);

        // Find last unfinished frame
        foreach ($frames as $index => $frame) {
            if (!($frame['finished'] ?? false)) {
                $currentIndex = $index;
                break;
            }
        }

        // If all frames finished, we're on next frame
        if ($currentIndex >= count($frames)) {
            return [
                'index' => $currentIndex + 1,
                'player1_score' => 0,
                'player2_score' => 0,
            ];
        }

        return [
            'index' => $currentIndex + 1,
            'player1_score' => $frames[$currentIndex]['player1'] ?? 0,
            'player2_score' => $frames[$currentIndex]['player2'] ?? 0,
        ];
    }

    /**
     * Get minimal match status for polling
     */
    public function getMatchStatus(Tournament $tournament, ClubTable $table): JsonResponse
    {
        $activeMatch = TournamentMatch::where('tournament_id', $tournament->id)
            ->where('club_table_id', $table->id)
            ->whereIn('status', ['in_progress', 'verification'])
            ->first()
        ;

        return response()->json([
            'has_active_match' => $activeMatch !== null,
            'match_id'         => $activeMatch?->id,
            'status'           => $activeMatch?->status,
            'updated_at'       => $activeMatch?->updated_at->timestamp,
        ]);
    }
}
