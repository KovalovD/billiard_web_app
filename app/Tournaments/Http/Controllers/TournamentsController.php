<?php

namespace App\Tournaments\Http\Controllers;

use App\Core\Http\Resources\ClubTableResource;
use App\Tournaments\Http\Resources\TournamentBracketResource;
use App\Tournaments\Http\Resources\TournamentGroupResource;
use App\Tournaments\Http\Resources\TournamentPlayerResource;
use App\Tournaments\Http\Resources\TournamentResource;
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

    public function tables(Tournament $tournament): AnonymousResourceCollection
    {
        return ClubTableResource::collection($this->tournamentService->getTables($tournament));
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
        $tournament->load(['game', 'city.country', 'club', 'players.user', 'officialRatings']);

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

    /**
     * Get tournament brackets
     */
    public function brackets(Tournament $tournament): AnonymousResourceCollection
    {
        // Check if tournament has bracket stage
        if (!in_array($tournament->tournament_type->value, [
            'single_elimination',
            'double_elimination',
            'double_elimination_full',
            'groups_playoff',
            'team_groups_playoff',
        ])) {
            return TournamentBracketResource::collection([]);
        }

        $brackets = $tournament
            ->brackets()
            ->with(['matches.player1', 'matches.player2', 'matches.winner'])
            ->get()
        ;

        return TournamentBracketResource::collection($brackets);
    }

    /**
     * Get tournament groups
     */
    public function groups(Tournament $tournament): AnonymousResourceCollection
    {
        // Check if tournament has group stage
        if (!in_array($tournament->tournament_type->value, [
            'groups',
            'groups_playoff',
            'team_groups_playoff',
        ])) {
            return TournamentGroupResource::collection([]);
        }

        $groups = $tournament
            ->groups()
            ->with(['players.user', 'matches.player1', 'matches.player2'])
            ->orderBy('group_code')
            ->get()
        ;

        return TournamentGroupResource::collection($groups);
    }
}
