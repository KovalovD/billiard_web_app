<?php

namespace App\Tournaments\Http\Controllers;

use App\Tournaments\Http\Resources\TournamentBracketResource;
use App\Tournaments\Http\Resources\TournamentGroupResource;
use App\Tournaments\Http\Resources\TournamentMatchResource;
use App\Tournaments\Http\Resources\TournamentTeamResource;
use App\Tournaments\Models\Tournament;
use App\Tournaments\Models\TournamentGroup;
use App\Tournaments\Models\TournamentTeam;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Tournament Structure (Public)
 */
class TournamentStructureController
{
    /**
     * Get tournament structure overview
     */
    public function getStructure(Tournament $tournament): JsonResponse
    {
        $structure = [
            'tournament' => [
                'id'                 => $tournament->id,
                'name'               => $tournament->name,
                'format'             => $tournament->tournament_format,
                'format_display'     => $tournament->format_display,
                'is_team_tournament' => $tournament->is_team_tournament,
                'status'             => $tournament->status,
                'has_groups'         => $tournament->hasGroups(),
                'has_brackets'       => $tournament->hasBrackets(),
            ],
            'progress'   => $tournament->getProgressSummary(),
        ];

        return response()->json($structure);
    }

    /**
     * Get tournament groups with standings
     */
    public function getGroups(Tournament $tournament): AnonymousResourceCollection
    {
        if (!$tournament->hasGroups()) {
            return TournamentGroupResource::collection(collect([]));
        }

        $groups = $tournament
            ->groups()
            ->with(['players.user', 'teams'])
            ->orderBy('group_number')
            ->get()
        ;

        return TournamentGroupResource::collection($groups);
    }

    /**
     * Get specific group standings
     */
    public function getGroupStandings(Tournament $tournament, TournamentGroup $group): JsonResponse
    {
        if ($group->tournament_id !== $tournament->id) {
            abort(404, 'Group not found in this tournament');
        }

        $standings = $group->getStandings();

        return response()->json([
            'group'     => new TournamentGroupResource($group),
            'standings' => $standings,
        ]);
    }

    /**
     * Get tournament brackets
     */
    public function getBrackets(Tournament $tournament): AnonymousResourceCollection
    {
        if (!$tournament->hasBrackets()) {
            return TournamentBracketResource::collection(collect([]));
        }

        $brackets = $tournament
            ->brackets()
            ->orderBy('bracket_type')
            ->get()
        ;

        return TournamentBracketResource::collection($brackets);
    }

    /**
     * Get tournament matches
     */
    public function getMatches(Request $request, Tournament $tournament): AnonymousResourceCollection
    {
        $query = $tournament
            ->matches()
            ->with(['participant1', 'participant2', 'group', 'club'])
        ;

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('match_type')) {
            $query->where('match_type', $request->match_type);
        }

        if ($request->has('round')) {
            $query->where('round_number', $request->round);
        }

        $matches = $query
            ->orderBy('scheduled_at')
            ->orderBy('round_number')
            ->orderBy('match_number')
            ->get()
        ;

        return TournamentMatchResource::collection($matches);
    }

    /**
     * Get tournament schedule
     */
    public function getSchedule(Tournament $tournament): JsonResponse
    {
        $matches = $tournament
            ->matches()
            ->with(['participant1', 'participant2', 'group', 'club'])
            ->whereNotNull('scheduled_at')
            ->orderBy('scheduled_at')
            ->get()
        ;

        // Group matches by date
        $schedule = $matches->groupBy(function ($match) {
            return $match->scheduled_at?->format('Y-m-d');
        })->map(function ($dayMatches) {
            return TournamentMatchResource::collection($dayMatches);
        });

        return response()->json([
            'tournament' => [
                'id'     => $tournament->id,
                'name'   => $tournament->name,
                'status' => $tournament->status,
            ],
            'schedule'   => $schedule,
            'summary'    => [
                'total_matches'     => $matches->count(),
                'matches_by_status' => [
                    'pending'     => $matches->where('status', 'pending')->count(),
                    'in_progress' => $matches->where('status', 'in_progress')->count(),
                    'completed'   => $matches->where('status', 'completed')->count(),
                ],
            ],
        ]);
    }

    /**
     * Get tournament teams
     */
    public function getTeams(Tournament $tournament): AnonymousResourceCollection
    {
        if (!$tournament->is_team_tournament) {
            return TournamentTeamResource::collection(collect([]));
        }

        $teams = $tournament
            ->teams()
            ->with(['players.user', 'group'])
            ->orderBy('seed')
            ->get()
        ;

        return TournamentTeamResource::collection($teams);
    }

    /**
     * Get specific team
     */
    public function getTeam(Tournament $tournament, TournamentTeam $team): TournamentTeamResource
    {
        if ($team->tournament_id !== $tournament->id) {
            abort(404, 'Team not found in this tournament');
        }

        $team->load(['players.user', 'group']);

        return new TournamentTeamResource($team);
    }
}
