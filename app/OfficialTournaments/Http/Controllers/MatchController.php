<?php

namespace App\OfficialTournaments\Http\Controllers;

use App\Core\Http\Controllers\Controller;
use App\OfficialTournaments\Http\Requests\ScheduleMatchRequest;
use App\OfficialTournaments\Http\Requests\UpdateMatchScoreRequest;
use App\OfficialTournaments\Http\Resources\MatchResource;
use App\OfficialTournaments\Models\OfficialMatch;
use App\OfficialTournaments\Models\OfficialMatchSet;
use App\OfficialTournaments\Models\OfficialParticipant;
use App\OfficialTournaments\Models\OfficialPoolTable;
use App\OfficialTournaments\Models\OfficialStage;
use App\OfficialTournaments\Models\OfficialTournament;
use App\OfficialTournaments\Services\Bracket\DoubleElimService;
use App\OfficialTournaments\Services\Bracket\SingleElimService;
use App\OfficialTournaments\Services\Scheduling\SchedulerService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Throwable;

class MatchController extends Controller
{
    /**
     * Display a listing of tournament matches
     *
     * @param  Request  $request
     * @param  OfficialTournament  $tournament
     * @return AnonymousResourceCollection
     */
    public function index(Request $request, OfficialTournament $tournament): AnonymousResourceCollection
    {
        $query = $tournament
            ->matches()
            ->with(['stage', 'table', 'matchSets'])
        ;

        // Filters
        if ($request->has('stage_id')) {
            $query->where('stage_id', $request->input('stage_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('round')) {
            $query->where('round', $request->input('round'));
        }

        if ($request->has('bracket')) {
            $query->where('bracket', $request->input('bracket'));
        }

        if ($request->has('date')) {
            $date = Carbon::parse($request->input('date'));
            $query->whereDate('scheduled_at', $date);
        }

        $query
            ->orderBy('scheduled_at')
            ->orderBy('round')
        ;

        $matches = $query->paginate($request->input('per_page', 50));

        return MatchResource::collection($matches);
    }

    /**
     * Display the specified match
     *
     * @param  OfficialTournament  $tournament
     * @param  OfficialMatch  $match
     * @return MatchResource
     */
    public function show(OfficialTournament $tournament, OfficialMatch $match): MatchResource
    {
        // Ensure match belongs to tournament
        if (!$tournament->matches()->where('id', $match->id)->exists()) {
            abort(404);
        }

        return new MatchResource(
            $match->load([
                'stage',
                'table',
                'matchSets.winnerParticipant.user',
            ]),
        );
    }

    /**
     * Update match score
     *
     * @param  UpdateMatchScoreRequest  $request
     * @param  OfficialTournament  $tournament
     * @param  OfficialMatch  $match
     * @return MatchResource|JsonResponse
     * @throws Throwable
     */
    public function updateScore(
        UpdateMatchScoreRequest $request,
        OfficialTournament $tournament,
        OfficialMatch $match,
    ): MatchResource|JsonResponse {
        if (!$tournament->matches()->where('id', $match->id)->exists()) {
            abort(404);
        }

        // Check if match can be scored
        if ($match->status === OfficialMatch::STATUS_FINISHED) {
            return response()->json([
                'message' => 'Match is already finished',
            ], 422);
        }

        $p1 = $match->getParticipant1();
        $p2 = $match->getParticipant2();

        if (!$p1 || !$p2) {
            return response()->json([
                'message' => 'Match participants not set',
            ], 422);
        }

        DB::transaction(function () use ($request, $match, $p1, $p2) {
            // Update match status
            $match->status = $request->input('status', OfficialMatch::STATUS_ONGOING);

            // Delete existing sets
            $match->matchSets()->delete();

            // Create new sets
            $sets = $request->input('sets');
            foreach ($sets as $index => $setData) {
                OfficialMatchSet::create([
                    'match_id'              => $match->id,
                    'set_no'                => $index + 1,
                    'winner_participant_id' => $setData['winner_id'],
                    'score_json'            => [
                        'participant1' => $setData['score1'],
                        'participant2' => $setData['score2'],
                    ],
                ]);
            }

            // Handle match completion
            if ($match->status === OfficialMatch::STATUS_FINISHED) {
                $this->handleMatchCompletion($match, $p1, $p2);
            }

            $match->save();
        });

        return new MatchResource($match->load(['stage', 'table', 'matchSets']));
    }

    /**
     * Handle match completion and progression
     * @throws Throwable
     */
    protected function handleMatchCompletion(
        OfficialMatch $match,
        OfficialParticipant $p1,
        OfficialParticipant $p2,
    ): void {
        // Determine winner and loser
        $p1Wins = $match->matchSets->where('winner_participant_id', $p1->id)->count();
        $p2Wins = $match->matchSets->where('winner_participant_id', $p2->id)->count();

        if ($p1Wins === $p2Wins) {
            return; // Draw? Should not happen
        }

        $winner = $p1Wins > $p2Wins ? $p1 : $p2;
        $loser = $p1Wins > $p2Wins ? $p2 : $p1;

        // Handle progression based on stage type
        $stage = $match->stage;

        if ($stage->type === OfficialStage::TYPE_SINGLE_ELIM) {
            $service = new SingleElimService();
            $service->handleMatchCompletion($match, $winner, $loser);
        } elseif ($stage->type === OfficialStage::TYPE_DOUBLE_ELIM) {
            $service = new DoubleElimService();
            $service->handleMatchCompletion($match, $winner, $loser);
        }
        // Round robin doesn't need progression handling
    }

    /**
     * Schedule or reschedule match
     *
     * @param  ScheduleMatchRequest  $request
     * @param  OfficialTournament  $tournament
     * @param  OfficialMatch  $match
     * @return MatchResource|JsonResponse
     * @throws Throwable
     * @throws Throwable
     */
    public function schedule(
        ScheduleMatchRequest $request,
        OfficialTournament $tournament,
        OfficialMatch $match,
    ): MatchResource|JsonResponse {
        if (!$tournament->matches()->where('id', $match->id)->exists()) {
            abort(404);
        }

        if ($match->status === OfficialMatch::STATUS_FINISHED) {
            return response()->json([
                'message' => 'Cannot reschedule finished match',
            ], 422);
        }

        $table = OfficialPoolTable::findOrFail($request->input('table_id'));
        $startTime = Carbon::parse($request->input('scheduled_at'));

        // Check table belongs to tournament
        if ($table->tournament_id !== $tournament->id) {
            return response()->json([
                'message' => 'Invalid table',
            ], 422);
        }

        $scheduler = new SchedulerService();

        // Check for conflicts
        $conflicts = $scheduler->findScheduleConflicts($match, $startTime, $table);
        if ($conflicts->isNotEmpty()) {
            return response()->json([
                'message'   => 'Schedule conflicts detected',
                'conflicts' => $conflicts,
            ], 422);
        }

        // Schedule the match
        if ($match->scheduled_at) {
            $scheduler->rescheduleMatch($match, $table, $startTime);
        } else {
            $scheduler->scheduleMatch($match, $table, $startTime);
        }

        return new MatchResource($match->fresh(['stage', 'table', 'matchSets']));
    }

    /**
     * Set match as walkover
     *
     * @param  Request  $request
     * @param  OfficialTournament  $tournament
     * @param  OfficialMatch  $match
     * @return MatchResource|JsonResponse
     * @throws Throwable
     * @throws Throwable
     */
    public function walkover(
        Request $request,
        OfficialTournament $tournament,
        OfficialMatch $match,
    ): MatchResource|JsonResponse {
        if (!$tournament->matches()->where('id', $match->id)->exists()) {
            abort(404);
        }

        $request->validate([
            'winner_id' => 'required|exists:official_participants,id',
        ]);

        $winnerId = $request->input('winner_id');
        $p1 = $match->getParticipant1();
        $p2 = $match->getParticipant2();

        // Validate winner is in match
        if (!$p1 || !$p2 || ($winnerId !== $p1->id && $winnerId !== $p2->id)) {
            return response()->json([
                'message' => 'Invalid winner',
            ], 422);
        }

        DB::transaction(function () use ($match, $winnerId, $p1, $p2) {
            $match->status = OfficialMatch::STATUS_WALKOVER;
            $match->metadata = array_merge($match->metadata ?? [], [
                'walkover_winner_id' => $winnerId,
                'walkover_at'        => now()->toIso8601String(),
            ]);
            $match->save();

            // Handle progression
            $winner = $winnerId === $p1->id ? $p1 : $p2;
            $loser = $winnerId === $p1->id ? $p2 : $p1;
            $this->handleMatchCompletion($match, $winner, $loser);
        });

        return new MatchResource($match->load(['stage', 'table', 'matchSets']));
    }

    /**
     * Auto-schedule all pending matches
     *
     * @param  Request  $request
     * @param  OfficialTournament  $tournament
     * @return JsonResponse
     * @throws Throwable
     * @throws Throwable
     */
    public function autoSchedule(Request $request, OfficialTournament $tournament): JsonResponse
    {
        $request->validate([
            'stage_id'       => 'sometimes|exists:official_stages,id',
            'start_time'     => 'required|date',
            'match_duration' => 'sometimes|integer|min:15|max:180',
            'rest_time'      => 'sometimes|integer|min:0|max:120',
        ]);

        $startTime = Carbon::parse($request->input('start_time'));
        $options = $request->only(['match_duration', 'rest_time']);

        $scheduler = new SchedulerService();
        $scheduled = collect();

        if ($request->has('stage_id')) {
            $stage = OfficialStage::findOrFail($request->input('stage_id'));
            if ($stage->tournament_id !== $tournament->id) {
                abort(404);
            }
            $scheduled = $scheduler->autoSchedule($stage, $startTime, $options);
        } else {
            // Schedule all stages
            foreach ($tournament->stages as $stage) {
                $stageScheduled = $scheduler->autoSchedule($stage, $startTime, $options);
                $scheduled = $scheduled->merge($stageScheduled);

                // Next stage starts after this one
                if ($stageScheduled->isNotEmpty()) {
                    $lastMatch = $stageScheduled->last();
                    $startTime = Carbon::parse($lastMatch->scheduled_at)
                        ->addMinutes($options['match_duration'] ?? 45)
                        ->addHour()
                    ; // Buffer between stages
                }
            }
        }

        return response()->json([
            'message' => 'Auto-scheduling completed',
            'data'    => [
                'matches_scheduled' => $scheduled->count(),
                'matches'           => MatchResource::collection($scheduled->take(10)), // Preview
            ],
        ]);
    }

    /**
     * Get match statistics
     *
     * @param  OfficialTournament  $tournament
     * @param  OfficialMatch  $match
     * @return JsonResponse
     */
    public function statistics(OfficialTournament $tournament, OfficialMatch $match): JsonResponse
    {
        if (!$tournament->matches()->where('id', $match->id)->exists()) {
            abort(404);
        }

        $sets = $match->matchSets;
        $p1 = $match->getParticipant1();
        $p2 = $match->getParticipant2();

        $stats = [
            'match_id'     => $match->id,
            'status'       => $match->status,
            'duration'     => $match->scheduled_at && $match->updated_at ?
                $match->updated_at->diffInMinutes($match->scheduled_at) : null,
            'total_sets'   => $sets->count(),
            'participant1' => [
                'id'          => $p1?->id,
                'name'        => $p1?->getDisplayName(),
                'sets_won'    => $sets->where('winner_participant_id', $p1?->id)->count(),
                'total_racks' => $sets->sum(fn($set) => $set->score_json['participant1'] ?? 0),
            ],
            'participant2' => [
                'id'          => $p2?->id,
                'name'        => $p2?->getDisplayName(),
                'sets_won'    => $sets->where('winner_participant_id', $p2?->id)->count(),
                'total_racks' => $sets->sum(fn($set) => $set->score_json['participant2'] ?? 0),
            ],
        ];

        return response()->json(['data' => $stats]);
    }
}
