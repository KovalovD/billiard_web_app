<?php

namespace App\Tournaments\Http\Controllers;

use App\Tournaments\Http\Resources\TournamentPlayerResource;
use App\Tournaments\Models\Tournament;
use App\Tournaments\Services\TournamentSeedingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Throwable;

/**
 * @group Admin Tournament Seeding
 */
readonly class AdminTournamentSeedingController
{
    public function __construct(
        private TournamentSeedingService $seedingService,
    ) {
    }

    /**
     * Show seeding management page
     * @admin
     */
    public function index(Tournament $tournament): Response
    {
        return Inertia::render('Admin/Tournaments/Seeding', [
            'tournamentId' => $tournament->id,
        ]);
    }

    /**
     * Generate seeds for tournament players
     * @admin
     * @throws Throwable
     */
    public function generateSeeds(Request $request, Tournament $tournament): JsonResponse
    {
        $validated = $request->validate([
            'method' => ['required', 'string', 'in:random,rating,manual'],
        ]);

        try {
            $players = $this->seedingService->generateSeeds($tournament, $validated['method']);

            return response()->json([
                'success' => true,
                'players' => TournamentPlayerResource::collection($players),
                'message' => 'Seeds generated successfully',
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update player seeds manually
     * @admin
     * @throws Throwable
     */
    public function updateSeeds(Request $request, Tournament $tournament): JsonResponse
    {
        $validated = $request->validate([
            'seeds'               => ['required', 'array'],
            'seeds.*.player_id'   => ['required', 'integer', 'exists:tournament_players,id'],
            'seeds.*.seed_number' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $this->seedingService->updateSeeds($tournament, $validated['seeds']);

            return response()->json([
                'success' => true,
                'message' => 'Seeds updated successfully',
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Complete seeding phase
     * @admin
     * @throws Throwable
     */
    public function completeSeeding(Tournament $tournament): JsonResponse
    {
        try {
            $this->seedingService->completeSeedingPhase($tournament);

            return response()->json([
                'success' => true,
                'message' => 'Seeding completed successfully',
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
