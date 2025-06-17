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
        ]);

        try {
            $match = $this->matchService->startMatch($match, $validated);

            return response()->json([
                'success' => true,
                'match'   => new TournamentMatchResource($match),
                'message' => 'Match started successfully',
            ]);
        } catch (RuntimeException $e) {
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
        ]);

        try {
            $match = $this->matchService->finishMatch($match, $validated);

            return response()->json([
                'success' => true,
                'match'   => new TournamentMatchResource($match),
                'message' => 'Match completed successfully',
            ]);
        } catch (RuntimeException $e) {
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
            'player1_score' => ['nullable', 'integer', 'min:0'],
            'player2_score' => ['nullable', 'integer', 'min:0'],
            'club_table_id' => ['nullable', 'integer', 'exists:club_tables,id'],
            'stream_url'    => ['nullable', 'string', 'url'],
            'status'        => ['nullable', 'string', 'in:pending,ready,in_progress,verification,completed,cancelled'],
            'scheduled_at'  => ['nullable', 'date'],
            'admin_notes'   => ['nullable', 'string'],
        ]);

        try {
            $match = $this->matchService->updateMatch($match, $validated);

            return response()->json([
                'success' => true,
                'match'   => new TournamentMatchResource($match),
                'message' => 'Match updated successfully',
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
