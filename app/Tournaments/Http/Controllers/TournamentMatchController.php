<?php

namespace App\Tournaments\Http\Controllers;

use App\Tournaments\Http\Requests\EnterMatchResultRequest;
use App\Tournaments\Http\Requests\RescheduleMatchRequest;
use App\Tournaments\Http\Requests\UpdateMatchRequest;
use App\Tournaments\Http\Resources\TournamentMatchResource;
use App\Tournaments\Models\Tournament;
use App\Tournaments\Models\TournamentMatch;
use App\Tournaments\Services\TournamentManagementService;
use DateMalformedStringException;
use DateTime;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use RuntimeException;

/**
 * @group Tournament Matches
 */
readonly class TournamentMatchController
{
    public function __construct(
        private TournamentManagementService $managementService,
    ) {
    }

    /**
     * Get tournament matches
     */
    public function index(Request $request, Tournament $tournament): AnonymousResourceCollection
    {
        $query = $tournament->matches()->with([
            'participant1',
            'participant2',
            'group',
            'club',
        ]);

        // Filter by match type
        if ($request->has('match_type')) {
            $query->where('match_type', $request->match_type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by round
        if ($request->has('round')) {
            $query->where('round_number', $request->round);
        }

        // Filter by group
        if ($request->has('group_id')) {
            $query->where('group_id', $request->group_id);
        }

        // Order by schedule, then by round and match number
        $matches = $query
            ->orderBy('scheduled_at')
            ->orderBy('round_number')
            ->orderBy('match_number')
            ->get()
        ;

        return TournamentMatchResource::collection($matches);
    }

    /**
     * Get specific match
     */
    public function show(Tournament $tournament, TournamentMatch $match): TournamentMatchResource
    {
        if ($match->tournament_id !== $tournament->id) {
            abort(404, 'Match not found in this tournament');
        }

        $match->load([
            'participant1',
            'participant2',
            'group',
            'club',
        ]);

        return new TournamentMatchResource($match);
    }

    /**
     * Update match details
     * @admin
     */
    public function update(UpdateMatchRequest $request, Tournament $tournament, TournamentMatch $match): JsonResponse
    {
        if ($match->tournament_id !== $tournament->id) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found in this tournament',
            ], 404);
        }

        if ($match->isCompleted()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update completed match',
            ], 400);
        }

        $match->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Match updated successfully',
            'match'   => new TournamentMatchResource($match->fresh()),
        ]);
    }

    /**
     * Enter match result
     * @admin
     */
    public function enterResult(
        EnterMatchResultRequest $request,
        Tournament $tournament,
        TournamentMatch $match,
    ): JsonResponse {
        if ($match->tournament_id !== $tournament->id) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found in this tournament',
            ], 404);
        }

        try {
            $this->managementService->enterMatchResult($match, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Match result entered successfully',
                'match'   => new TournamentMatchResource($match->fresh()),
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Start match
     * @admin
     */
    public function start(Tournament $tournament, TournamentMatch $match): JsonResponse
    {
        if ($match->tournament_id !== $tournament->id) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found in this tournament',
            ], 404);
        }

        if (!$match->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'Match is not in pending status',
            ], 400);
        }

        $match->start();

        return response()->json([
            'success' => true,
            'message' => 'Match started successfully',
            'match'   => new TournamentMatchResource($match->fresh()),
        ]);
    }

    /**
     * Cancel match
     * @admin
     */
    public function cancel(Request $request, Tournament $tournament, TournamentMatch $match): JsonResponse
    {
        if ($match->tournament_id !== $tournament->id) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found in this tournament',
            ], 404);
        }

        if ($match->isCompleted()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel completed match',
            ], 400);
        }

        $reason = $request->input('reason');
        $match->cancel($reason);

        return response()->json([
            'success' => true,
            'message' => 'Match cancelled successfully',
            'match'   => new TournamentMatchResource($match->fresh()),
        ]);
    }

    /**
     * Reschedule match
     * @admin
     */
    public function reschedule(
        RescheduleMatchRequest $request,
        Tournament $tournament,
        TournamentMatch $match,
    ): JsonResponse {
        if ($match->tournament_id !== $tournament->id) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found in this tournament',
            ], 404);
        }

        try {
            $newTime = new DateTime($request->validated('scheduled_at'));

            $this->managementService->rescheduleMatch(
                $match,
                $newTime,
                $request->validated('table_number'),
                $request->validated('club_id'),
            );

            return response()->json([
                'success' => true,
                'message' => 'Match rescheduled successfully',
                'match'   => new TournamentMatchResource($match->fresh()),
            ]);
        } catch (RuntimeException|DateMalformedStringException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get match schedule overview
     */
    public function schedule(Tournament $tournament): JsonResponse
    {
        $matches = $tournament
            ->matches()
            ->with(['participant1', 'participant2', 'group', 'club'])
            ->orderBy('scheduled_at')
            ->get()
        ;

        $schedule = [
            'tournament'        => [
                'id'     => $tournament->id,
                'name'   => $tournament->name,
                'status' => $tournament->status,
            ],
            'matches_by_status' => [
                'pending'     => $matches->where('status', 'pending')->count(),
                'in_progress' => $matches->where('status', 'in_progress')->count(),
                'completed'   => $matches->where('status', 'completed')->count(),
                'cancelled'   => $matches->where('status', 'cancelled')->count(),
            ],
            'matches_by_type'   => [
                'group'   => $matches->where('match_type', 'group')->count(),
                'bracket' => $matches->where('match_type', 'bracket')->count(),
                'final'   => $matches->where('match_type', 'final')->count(),
            ],
            'upcoming_matches'  => TournamentMatchResource::collection(
                $matches
                    ->whereIn('status', ['pending', 'in_progress'])
                    ->take(10),
            ),
            'recent_results'    => TournamentMatchResource::collection(
                $matches
                    ->where('status', 'completed')
                    ->sortByDesc('completed_at')
                    ->take(10),
            ),
        ];

        return response()->json($schedule);
    }

    /**
     * Get matches by round
     */
    public function byRound(Tournament $tournament, int $round): AnonymousResourceCollection
    {
        $matches = $tournament
            ->matches()
            ->where('round_number', $round)
            ->with(['participant1', 'participant2', 'group', 'club'])
            ->orderBy('match_number')
            ->get()
        ;

        return TournamentMatchResource::collection($matches);
    }

    /**
     * Get group matches
     */
    public function groupMatches(Tournament $tournament, int $groupId): AnonymousResourceCollection
    {
        $matches = $tournament
            ->matches()
            ->where('group_id', $groupId)
            ->with(['participant1', 'participant2', 'group', 'club'])
            ->orderBy('match_number')
            ->get()
        ;

        return TournamentMatchResource::collection($matches);
    }

    /**
     * Bulk update match schedules
     * @admin
     */
    public function bulkReschedule(Request $request, Tournament $tournament): JsonResponse
    {
        $validated = $request->validate([
            'matches'                => ['required', 'array'],
            'matches.*.match_id'     => ['required', 'integer', 'exists:tournament_matches,id'],
            'matches.*.scheduled_at' => ['required', 'date'],
            'matches.*.table_number' => ['nullable', 'integer', 'min:1'],
            'matches.*.club_id'      => ['nullable', 'integer', 'exists:clubs,id'],
        ]);

        $updatedCount = 0;
        $errors = [];

        foreach ($validated['matches'] as $matchData) {
            try {
                $match = TournamentMatch::find($matchData['match_id']);

                if (!$match || $match->tournament_id !== $tournament->id) {
                    $errors[] = "Match {$matchData['match_id']} not found in tournament";
                    continue;
                }

                if ($match->isCompleted()) {
                    $errors[] = "Match {$matchData['match_id']} is already completed";
                    continue;
                }

                $newTime = new DateTime($matchData['scheduled_at']);

                $this->managementService->rescheduleMatch(
                    $match,
                    $newTime,
                    $matchData['table_number'] ?? null,
                    $matchData['club_id'] ?? null,
                );

                $updatedCount++;
            } catch (Exception $e) {
                $errors[] = "Match {$matchData['match_id']}: ".$e->getMessage();
            }
        }

        return response()->json([
            'success'       => $updatedCount > 0,
            'updated_count' => $updatedCount,
            'errors'        => $errors,
            'message'       => $updatedCount > 0
                ? "Successfully updated $updatedCount matches"
                : 'No matches were updated',
        ]);
    }
}
