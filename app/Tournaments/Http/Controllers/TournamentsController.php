<?php

namespace App\Tournaments\Http\Controllers;

use App\Tournaments\Http\Resources\TournamentResource;
use App\Tournaments\Http\Resources\TournamentPlayerResource;
use App\Tournaments\Models\Tournament;
use App\Tournaments\Services\TournamentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Tournaments
 */
readonly class TournamentsController
{
    public function __construct(
        private TournamentService $tournamentService,
    ) {
    }

    /**
     * Get all tournaments
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $tournaments = $this->tournamentService->getAllTournaments($request->query());

        return TournamentResource::collection($tournaments);
    }

    /**
     * Get tournament by id
     */
    public function show(Tournament $tournament): TournamentResource
    {
        $tournament->load(['game', 'city.country', 'club', 'players.user']);

        return new TournamentResource($tournament);
    }

    /**
     * Get tournament players
     */
    public function players(Tournament $tournament): AnonymousResourceCollection
    {
        $players = $this->tournamentService->getTournamentPlayers($tournament);

        return TournamentPlayerResource::collection($players);
    }

    /**
     * Get tournament results
     */
    public function results(Tournament $tournament): JsonResponse
    {
        if (!$tournament->isCompleted()) {
            return response()->json([
                'message' => 'Tournament is not completed yet',
            ], 400);
        }

        $results = $this->tournamentService->getTournamentResults($tournament);

        return response()->json($results);
    }

    /**
     * Get upcoming tournaments
     */
    public function upcoming(): AnonymousResourceCollection
    {
        $tournaments = $this->tournamentService->getUpcomingTournaments();

        return TournamentResource::collection($tournaments);
    }

    /**
     * Get active tournaments
     */
    public function active(): AnonymousResourceCollection
    {
        $tournaments = $this->tournamentService->getActiveTournaments();

        return TournamentResource::collection($tournaments);
    }

    /**
     * Get completed tournaments
     */
    public function completed(): AnonymousResourceCollection
    {
        $tournaments = $this->tournamentService->getCompletedTournaments();

        return TournamentResource::collection($tournaments);
    }
}
