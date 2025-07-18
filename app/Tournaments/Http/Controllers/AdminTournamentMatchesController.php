<?php

namespace App\Tournaments\Http\Controllers;

use App\Tournaments\Http\Resources\TournamentMatchResource;
use App\Tournaments\Models\Tournament;
use App\Tournaments\Models\TournamentMatch;
use App\Tournaments\Services\TournamentMatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Throwable;

/**
 * @group Admin Tournament Matches
 */
readonly class AdminTournamentMatchesController
{
    public function __construct(
        private TournamentMatchService $matchService,
    ) {
    }

    /**
     * Show matches management page
     * @admin
     */
    public function index(Tournament $tournament): Response
    {
        return Inertia::render('Admin/Tournaments/Matches', [
            'tournamentId' => $tournament->id,
        ]);
    }

    /**
     * Get single match details
     * @admin
     */
    public function show(Tournament $tournament, TournamentMatch $match): JsonResponse
    {
        if ($match->tournament_id !== $tournament->id) {
            return response()->json([
                'message' => 'Match does not belong to this tournament',
            ], 400);
        }

        return response()->json(new TournamentMatchResource($match->load(['player1', 'player2', 'clubTable'])));
    }

    /**
     * Start a match
     * @admin
     */
    public function startMatch(Request $request, Tournament $tournament, TournamentMatch $match): JsonResponse
    {
        if ($match->tournament_id !== $tournament->id) {
            return response()->json([
                'message' => 'Match does not belong to this tournament',
            ], 400);
        }

        $validated = $request->validate([
            'club_table_id' => ['nullable', 'integer', 'exists:club_tables,id'],
            'stream_url'    => ['nullable', 'string', 'url'],
            'admin_notes'   => ['nullable', 'string'],
        ]);

        try {
            $match = $this->matchService->startMatch($match, $validated);

            return response()->json(new TournamentMatchResource($match->load(['player1', 'player2', 'clubTable'])));
        } catch (RuntimeException|Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Finish a match
     * @admin
     */
    public function finishMatch(Request $request, Tournament $tournament, TournamentMatch $match): JsonResponse
    {
        if ($match->tournament_id !== $tournament->id) {
            return response()->json([
                'message' => 'Match does not belong to this tournament',
            ], 400);
        }

        $validated = $request->validate([
            'player1_score' => ['required', 'integer', 'min:0'],
            'player2_score' => ['required', 'integer', 'min:0'],
            'frame_scores'  => ['nullable', 'array'],
            'frame_scores.*.player1' => ['required_with:frame_scores', 'integer', 'min:0'],
            'frame_scores.*.player2' => ['required_with:frame_scores', 'integer', 'min:0'],
            'admin_notes'   => ['nullable', 'string'],
        ]);

        try {
            $result = $this->matchService->finishMatch($match, $validated);

            return response()->json([
                'match'            => new TournamentMatchResource($result['match']->load([
                    'player1', 'player2', 'clubTable',
                ])),
                'affected_matches' => $result['affected_matches'],
            ]);
        } catch (RuntimeException|Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update match details
     * @admin
     */
    public function updateMatch(Request $request, Tournament $tournament, TournamentMatch $match): JsonResponse
    {
        if ($match->tournament_id !== $tournament->id) {
            return response()->json([
                'message' => 'Match does not belong to this tournament',
            ], 400);
        }

        $validated = $request->validate([
            'player1_id'    => ['nullable', 'integer', 'exists:users,id'],
            'player2_id'    => ['nullable', 'integer', 'exists:users,id', 'different:player1_id'],
            'player1_score' => ['nullable', 'integer', 'min:0'],
            'player2_score' => ['nullable', 'integer', 'min:0'],
            'club_table_id' => ['nullable', 'integer', 'exists:club_tables,id'],
            'stream_url'    => ['nullable', 'string', 'url'],
            'status'        => ['nullable', 'string', 'in:pending,ready,in_progress,verification,completed,cancelled'],
            'scheduled_at'  => ['nullable', 'date'],
            'frame_scores'  => ['nullable', 'array'],
            'frame_scores.*.player1' => ['required_with:frame_scores', 'integer', 'min:0'],
            'frame_scores.*.player2' => ['required_with:frame_scores', 'integer', 'min:0'],
            'admin_notes'   => ['nullable', 'string'],
        ]);

        // Validate player changes
        if (isset($validated['player1_id']) || isset($validated['player2_id'])) {
            // Ensure players are registered in the tournament
            $playerIds = array_filter([
                $validated['player1_id'] ?? null,
                $validated['player2_id'] ?? null,
            ]);

            if (!empty($playerIds)) {
                $registeredCount = $tournament
                    ->players()
                    ->whereIn('user_id', $playerIds)
                    ->where('status', 'confirmed')
                    ->count()
                ;

                if ($registeredCount !== count($playerIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'All players must be registered in the tournament',
                    ], 400);
                }
            }
        }

        try {
            $result = $this->matchService->updateMatch($match, $validated);

            return response()->json([
                'match'            => new TournamentMatchResource($result['match']->load([
                    'player1', 'player2', 'clubTable',
                ])),
                'affected_matches' => $result['affected_matches'] ?? [],
            ]);
        } catch (RuntimeException|Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
