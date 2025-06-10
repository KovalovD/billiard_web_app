<?php

namespace App\Tournaments\Http\Controllers;

use App\Tournaments\Http\Requests\InitializeTournamentRequest;
use App\Tournaments\Http\Requests\CreateTournamentGroupRequest;
use App\Tournaments\Http\Requests\CreateTournamentTeamRequest;
use App\Tournaments\Http\Requests\AssignPlayersRequest;
use App\Tournaments\Http\Resources\TournamentGroupResource;
use App\Tournaments\Http\Resources\TournamentTeamResource;
use App\Tournaments\Http\Resources\TournamentBracketResource;
use App\Tournaments\Models\Tournament;
use App\Tournaments\Models\TournamentGroup;
use App\Tournaments\Models\TournamentTeam;
use App\Tournaments\Services\TournamentManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use RuntimeException;

/**
 * @group Admin Tournament Management
 */
readonly class TournamentManagementController
{
    public function __construct(
        private TournamentManagementService $managementService,
    ) {
    }

    /**
     * Initialize tournament structure
     * @admin
     */
    public function initializeStructure(InitializeTournamentRequest $request, Tournament $tournament): JsonResponse
    {
        try {
            $this->managementService->initializeTournamentStructure($tournament);

            return response()->json([
                'success'    => true,
                'message'    => 'Tournament structure initialized successfully',
                'tournament' => [
                    'id'             => $tournament->id,
                    'status'         => $tournament->fresh()->status,
                    'is_initialized' => true,
                ],
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Create tournament groups
     * @admin
     */
    public function createGroups(CreateTournamentGroupRequest $request, Tournament $tournament): JsonResponse
    {
        try {
            $groups = $this->managementService->createTournamentGroups(
                $tournament,
                $request->validated('groups'),
            );

            return response()->json([
                'success' => true,
                'message' => 'Tournament groups created successfully',
                'groups'  => TournamentGroupResource::collection($groups),
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get tournament groups
     * @admin
     */
    public function getGroups(Tournament $tournament): AnonymousResourceCollection
    {
        $groups = $tournament->groups()->with(['players.user', 'teams'])->get();
        return TournamentGroupResource::collection($groups);
    }

    /**
     * Create tournament team
     * @admin
     */
    public function createTeam(CreateTournamentTeamRequest $request, Tournament $tournament): JsonResponse
    {
        try {
            $team = $this->managementService->createTournamentTeam(
                $tournament,
                $request->validated(),
                $request->validated('player_ids'),
            );

            return response()->json([
                'success' => true,
                'message' => 'Tournament team created successfully',
                'team'    => new TournamentTeamResource($team->load('players.user')),
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get tournament teams
     * @admin
     */
    public function getTeams(Tournament $tournament): AnonymousResourceCollection
    {
        $teams = $tournament->teams()->with(['players.user', 'group'])->get();
        return TournamentTeamResource::collection($teams);
    }

    /**
     * Update tournament team
     * @admin
     */
    public function updateTeam(
        CreateTournamentTeamRequest $request,
        Tournament $tournament,
        TournamentTeam $team,
    ): JsonResponse {
        if ($team->tournament_id !== $tournament->id) {
            return response()->json([
                'success' => false,
                'message' => 'Team does not belong to this tournament',
            ], 400);
        }

        try {
            $team->update($request->validated());

            // Update player assignments if provided
            if ($request->has('player_ids')) {
                // Remove existing assignments
                $team->players()->update(['team_id' => null, 'team_role' => null]);

                // Assign new players
                foreach ($request->validated('player_ids') as $index => $playerId) {
                    $player = $tournament->players()->where('user_id', $playerId)->first();
                    if ($player) {
                        $role = $index === 0 ? 'captain' : 'player';
                        $player->update([
                            'team_id'   => $team->id,
                            'team_role' => $role,
                        ]);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Team updated successfully',
                'team'    => new TournamentTeamResource($team->fresh()->load('players.user')),
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete tournament team
     * @admin
     */
    public function deleteTeam(Tournament $tournament, TournamentTeam $team): JsonResponse
    {
        if ($team->tournament_id !== $tournament->id) {
            return response()->json([
                'success' => false,
                'message' => 'Team does not belong to this tournament',
            ], 400);
        }

        // Remove player assignments
        $team->players()->update(['team_id' => null, 'team_role' => null]);

        // Delete team
        $team->delete();

        return response()->json([
            'success' => true,
            'message' => 'Team deleted successfully',
        ]);
    }

    /**
     * Assign players to groups
     * @admin
     */
    public function assignPlayersToGroups(AssignPlayersRequest $request, Tournament $tournament): JsonResponse
    {
        try {
            $this->managementService->assignPlayersToGroups($tournament);

            return response()->json([
                'success' => true,
                'message' => 'Players assigned to groups successfully',
                'groups'  => TournamentGroupResource::collection(
                    $tournament->groups()->with(['players.user', 'teams'])->get(),
                ),
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Create bracket matches
     * @admin
     */
    public function createBracketMatches(Tournament $tournament): JsonResponse
    {
        try {
            $this->managementService->createBracketMatches($tournament);

            return response()->json([
                'success'  => true,
                'message'  => 'Bracket matches created successfully',
                'brackets' => TournamentBracketResource::collection(
                    $tournament->brackets()->get(),
                ),
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get tournament brackets
     * @admin
     */
    public function getBrackets(Tournament $tournament): AnonymousResourceCollection
    {
        $brackets = $tournament->brackets()->get();
        return TournamentBracketResource::collection($brackets);
    }

    /**
     * Get tournament structure overview
     * @admin
     */
    public function getStructureOverview(Tournament $tournament): JsonResponse
    {
        $overview = [
            'tournament'   => [
                'id'                 => $tournament->id,
                'name'               => $tournament->name,
                'format'             => $tournament->tournament_format,
                'format_display'     => $tournament->format_display,
                'seeding_method'     => $tournament->seeding_method,
                'is_team_tournament' => $tournament->is_team_tournament,
                'status'             => $tournament->status,
                'is_initialized'     => $tournament->isStructureInitialized(),
            ],
            'participants' => [
                'total_confirmed' => $tournament->confirmed_players_count,
                'teams_count'     => $tournament->teams()->count(),
            ],
            'structure'    => [
                'has_groups'     => $tournament->hasGroups(),
                'has_brackets'   => $tournament->hasBrackets(),
                'groups_count'   => $tournament->groups()->count(),
                'brackets_count' => $tournament->brackets()->count(),
            ],
            'progress'     => $tournament->getProgressSummary(),
        ];

        if ($tournament->hasGroups()) {
            $overview['groups'] = TournamentGroupResource::collection(
                $tournament->groups()->with(['players.user', 'teams'])->get(),
            );
        }

        if ($tournament->hasBrackets()) {
            $overview['brackets'] = TournamentBracketResource::collection(
                $tournament->brackets()->get(),
            );
        }

        if ($tournament->is_team_tournament) {
            $overview['teams'] = TournamentTeamResource::collection(
                $tournament->teams()->with(['players.user'])->get(),
            );
        }

        return response()->json($overview);
    }

    /**
     * Reset tournament structure
     * @admin
     */
    public function resetStructure(Tournament $tournament): JsonResponse
    {
        if ($tournament->status !== 'upcoming') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot reset structure of active or completed tournament',
            ], 400);
        }

        try {
            // Delete all structural elements
            $tournament->matches()->delete();
            $tournament->brackets()->delete();
            $tournament->groups()->delete();
            $tournament->teams()->delete();

            // Reset player assignments
            $tournament->players()->update([
                'seed'             => null,
                'bracket_position' => null,
                'group_id'         => null,
                'team_id'          => null,
                'team_role'        => null,
                'matches_played'   => 0,
                'matches_won'      => 0,
                'matches_lost'     => 0,
                'games_won'        => 0,
                'games_lost'       => 0,
                'win_percentage'   => 0,
                'bracket_path'     => null,
                'group_standings'  => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tournament structure reset successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset tournament structure: '.$e->getMessage(),
            ], 500);
        }
    }
}
