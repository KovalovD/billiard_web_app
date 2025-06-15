<?php

namespace App\OfficialTournaments\Http\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Core\Models\User;
use App\OfficialTournaments\Http\Requests\AddParticipantRequest;
use App\OfficialTournaments\Http\Requests\ApplySeedingRequest;
use App\OfficialTournaments\Http\Resources\ParticipantResource;
use App\OfficialTournaments\Models\OfficialParticipant;
use App\OfficialTournaments\Models\OfficialStage;
use App\OfficialTournaments\Models\OfficialTournament;
use App\OfficialTournaments\Services\Seeding\SeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Throwable;

class ParticipantController extends Controller
{
    /**
     * Display a listing of stage participants
     *
     * @param  OfficialTournament  $tournament
     * @param  OfficialStage  $stage
     * @return AnonymousResourceCollection
     */
    public function index(OfficialTournament $tournament, OfficialStage $stage): AnonymousResourceCollection
    {
        if ($stage->tournament_id !== $tournament->id) {
            abort(404);
        }

        $participants = $stage
            ->participants()
            ->with(['user', 'team.members'])
            ->orderBy('seed')
            ->get()
        ;

        return ParticipantResource::collection($participants);
    }

    /**
     * Add participant to stage
     *
     * @param  AddParticipantRequest  $request
     * @param  OfficialTournament  $tournament
     * @param  OfficialStage  $stage
     * @return ParticipantResource|JsonResponse
     */
    public function store(
        AddParticipantRequest $request,
        OfficialTournament $tournament,
        OfficialStage $stage,
    ): ParticipantResource|JsonResponse {
        if ($stage->tournament_id !== $tournament->id) {
            abort(404);
        }

        // Check if stage has started
        if ($stage->matches()->where('status', '!=', 'pending')->exists()) {
            return response()->json([
                'message' => 'Cannot add participants after stage has started',
            ], 422);
        }

        $data = $request->validated();
        $data['stage_id'] = $stage->id;

        // Check for duplicates
        $existingQuery = OfficialParticipant::where('stage_id', $stage->id);

        if (isset($data['user_id'])) {
            if ($existingQuery->where('user_id', $data['user_id'])->exists()) {
                return response()->json([
                    'message' => 'User is already a participant in this stage',
                ], 422);
            }

            // Get user's current rating
            $user = User::find($data['user_id']);
            $data['rating_snapshot'] = $user->rating ?? 0;
        } elseif (isset($data['team_id'])) {
            if ($existingQuery->where('team_id', $data['team_id'])->exists()) {
                return response()->json([
                    'message' => 'Team is already a participant in this stage',
                ], 422);
            }
        }

        // Auto-assign seed
        if (!isset($data['seed'])) {
            $data['seed'] = $stage->participants()->max('seed') + 1;
        }

        $participant = OfficialParticipant::create($data);

        return new ParticipantResource($participant->load(['user', 'team.members']));
    }

    /**
     * Remove participant from stage
     *
     * @param  OfficialTournament  $tournament
     * @param  OfficialStage  $stage
     * @param  OfficialParticipant  $participant
     * @return JsonResponse
     */
    public function destroy(
        OfficialTournament $tournament,
        OfficialStage $stage,
        OfficialParticipant $participant,
    ): JsonResponse {
        if ($stage->tournament_id !== $tournament->id || $participant->stage_id !== $stage->id) {
            abort(404);
        }

        // Check if participant has played matches
        if ($participant->matches()->exists()) {
            return response()->json([
                'message' => 'Cannot remove participant who has played matches',
            ], 422);
        }

        $participant->delete();

        // Reorder seeds
        $this->reorderSeeds($stage);

        return response()->json([
            'message' => 'Participant removed successfully',
        ]);
    }

    /**
     * Reorder seeds after participant removal
     */
    protected function reorderSeeds(OfficialStage $stage): void
    {
        $participants = $stage
            ->participants()
            ->orderBy('seed')
            ->get()
        ;

        $seed = 1;
        foreach ($participants as $participant) {
            if ($participant->seed !== $seed) {
                $participant->update(['seed' => $seed]);
            }
            $seed++;
        }
    }

    /**
     * Update participant
     *
     * @param  Request  $request
     * @param  OfficialTournament  $tournament
     * @param  OfficialStage  $stage
     * @param  OfficialParticipant  $participant
     * @return ParticipantResource
     */
    public function update(
        Request $request,
        OfficialTournament $tournament,
        OfficialStage $stage,
        OfficialParticipant $participant,
    ): ParticipantResource {
        if ($stage->tournament_id !== $tournament->id || $participant->stage_id !== $stage->id) {
            abort(404);
        }

        $request->validate([
            'seed'            => 'sometimes|integer|min:1',
            'rating_snapshot' => 'sometimes|integer|min:0',
        ]);

        $participant->update($request->only(['seed', 'rating_snapshot']));

        return new ParticipantResource($participant->load(['user', 'team.members']));
    }

    /**
     * Apply seeding to stage
     *
     * @param  ApplySeedingRequest  $request
     * @param  OfficialTournament  $tournament
     * @param  OfficialStage  $stage
     * @return JsonResponse
     * @throws Throwable
     */
    public function applySeedingMethod(
        ApplySeedingRequest $request,
        OfficialTournament $tournament,
        OfficialStage $stage,
    ): JsonResponse {
        if ($stage->tournament_id !== $tournament->id) {
            abort(404);
        }

        // Check if stage has started
        if ($stage->matches()->exists()) {
            return response()->json([
                'message' => 'Cannot change seeding after bracket generation',
            ], 422);
        }

        $seedService = new SeedService();
        $method = $request->input('method');

        switch ($method) {
            case 'random':
                $avoidSameClub = $request->input('avoid_same_club', true);
                $seedService->applySeedingRandom($stage, $avoidSameClub);
                break;

            case 'rating':
                $groupCount = $request->input('group_count', 4);
                $seedService->applySeedingByRating($stage, $groupCount);
                break;

            case 'manual':
                $seeds = $request->input('seeds');
                $seedService->applySeedingManual(collect($seeds));
                break;

            case 'previous':
                $previousTournamentId = $request->input('previous_tournament_id');
                $seedService->applySeedingByPreviousResults($stage, $previousTournamentId);
                break;

            default:
                return response()->json([
                    'message' => 'Invalid seeding method',
                ], 422);
        }

        $participants = $stage
            ->participants()
            ->with(['user', 'team'])
            ->orderBy('seed')
            ->get()
        ;

        return response()->json([
            'message' => 'Seeding applied successfully',
            'data'    => [
                'method'       => $method,
                'participants' => ParticipantResource::collection($participants),
            ],
        ]);
    }

    /**
     * Preview seeding groups
     *
     * @param  Request  $request
     * @param  OfficialTournament  $tournament
     * @param  OfficialStage  $stage
     * @return JsonResponse
     */
    public function previewGroups(
        Request $request,
        OfficialTournament $tournament,
        OfficialStage $stage,
    ): JsonResponse {
        if ($stage->tournament_id !== $tournament->id) {
            abort(404);
        }

        $request->validate([
            'group_count' => 'required|integer|min:1|max:16',
        ]);

        $participants = $stage
            ->participants()
            ->with(['user', 'team'])
            ->orderBy('seed')
            ->get()
        ;

        $seedService = new SeedService();
        $groups = $seedService->previewGroups($participants, $request->input('group_count'));

        return response()->json([
            'data' => $groups,
        ]);
    }

    /**
     * Batch add participants
     *
     * @param  Request  $request
     * @param  OfficialTournament  $tournament
     * @param  OfficialStage  $stage
     * @return JsonResponse
     * @throws Throwable
     * @throws Throwable
     */
    public function batchAdd(
        Request $request,
        OfficialTournament $tournament,
        OfficialStage $stage,
    ): JsonResponse {
        if ($stage->tournament_id !== $tournament->id) {
            abort(404);
        }

        $request->validate([
            'user_ids'   => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $added = [];
        $skipped = [];

        DB::transaction(static function () use ($request, $stage, &$added, &$skipped) {
            $currentSeed = $stage->participants()->max('seed') ?? 0;

            foreach ($request->input('user_ids') as $userId) {
                // Check if already exists
                if ($stage->participants()->where('user_id', $userId)->exists()) {
                    $skipped[] = $userId;
                    continue;
                }

                $user = User::find($userId);
                $participant = OfficialParticipant::create([
                    'stage_id'        => $stage->id,
                    'user_id'         => $userId,
                    'seed'            => ++$currentSeed,
                    'rating_snapshot' => $user->rating ?? 0,
                ]);

                $added[] = $participant->id;
            }
        });

        return response()->json([
            'message' => 'Batch add completed',
            'data'    => [
                'added'           => count($added),
                'skipped'         => count($skipped),
                'participant_ids' => $added,
            ],
        ]);
    }
}
