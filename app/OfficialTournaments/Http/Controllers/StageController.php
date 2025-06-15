<?php

namespace App\OfficialTournaments\Http\Controllers;

use App\Core\Http\Controllers\Controller;
use App\OfficialTournaments\Http\Requests\CreateStageRequest;
use App\OfficialTournaments\Http\Requests\GenerateBracketRequest;
use App\OfficialTournaments\Http\Resources\MatchResource;
use App\OfficialTournaments\Http\Resources\StageResource;
use App\OfficialTournaments\Models\OfficialStage;
use App\OfficialTournaments\Models\OfficialTournament;
use App\OfficialTournaments\Services\Bracket\DoubleElimService;
use App\OfficialTournaments\Services\Bracket\RoundRobinService;
use App\OfficialTournaments\Services\Bracket\SingleElimService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Throwable;

class StageController extends Controller
{
    /**
     * Display a listing of tournament stages
     *
     * @param  OfficialTournament  $tournament
     * @return AnonymousResourceCollection
     */
    public function index(OfficialTournament $tournament): AnonymousResourceCollection
    {
        $stages = $tournament
            ->stages()
            ->with(['participants.user', 'participants.team'])
            ->orderBy('number')
            ->get()
        ;

        return StageResource::collection($stages);
    }

    /**
     * Store a newly created stage
     *
     * @param  CreateStageRequest  $request
     * @param  OfficialTournament  $tournament
     * @return StageResource
     */
    public function store(CreateStageRequest $request, OfficialTournament $tournament): StageResource
    {
        $data = $request->validated();
        $data['tournament_id'] = $tournament->id;

        // Auto-increment stage number
        $data['number'] = $tournament->stages()->max('number') + 1;

        $stage = OfficialStage::create($data);

        return new StageResource($stage->load(['participants.user', 'participants.team']));
    }

    /**
     * Display the specified stage
     *
     * @param  OfficialTournament  $tournament
     * @param  OfficialStage  $stage
     * @return StageResource
     */
    public function show(OfficialTournament $tournament, OfficialStage $stage): StageResource
    {
        // Ensure stage belongs to tournament
        if ($stage->tournament_id !== $tournament->id) {
            abort(404);
        }

        return new StageResource(
            $stage->load([
                'participants.user',
                'participants.team',
                'matches.matchSets',
                'matches.table',
            ]),
        );
    }

    /**
     * Update the specified stage
     *
     * @param  CreateStageRequest  $request
     * @param  OfficialTournament  $tournament
     * @param  OfficialStage  $stage
     * @return StageResource
     */
    public function update(
        CreateStageRequest $request,
        OfficialTournament $tournament,
        OfficialStage $stage,
    ): StageResource {
        if ($stage->tournament_id !== $tournament->id) {
            abort(404);
        }

        $stage->update($request->validated());

        return new StageResource($stage->load(['participants.user', 'participants.team']));
    }

    /**
     * Remove the specified stage
     *
     * @param  OfficialTournament  $tournament
     * @param  OfficialStage  $stage
     * @return JsonResponse
     */
    public function destroy(OfficialTournament $tournament, OfficialStage $stage): JsonResponse
    {
        if ($stage->tournament_id !== $tournament->id) {
            abort(404);
        }

        // Check if stage has matches
        if ($stage->matches()->exists()) {
            return response()->json([
                'message' => 'Cannot delete stage with existing matches',
            ], 422);
        }

        $stage->delete();

        return response()->json([
            'message' => 'Stage deleted successfully',
        ]);
    }

    /**
     * Generate bracket for stage
     *
     * @param  GenerateBracketRequest  $request
     * @param  OfficialTournament  $tournament
     * @param  OfficialStage  $stage
     * @return JsonResponse
     * @throws Throwable
     */
    public function generateBracket(
        GenerateBracketRequest $request,
        OfficialTournament $tournament,
        OfficialStage $stage,
    ): JsonResponse {
        if ($stage->tournament_id !== $tournament->id) {
            abort(404);
        }

        // Check if bracket already exists
        if ($stage->matches()->exists()) {
            return response()->json([
                'message' => 'Bracket already generated for this stage',
            ], 422);
        }

        // Check if stage has participants
        if (!$stage->participants()->exists()) {
            return response()->json([
                'message' => 'No participants in stage',
            ], 422);
        }

        $participants = $stage
            ->participants()
            ->orderBy('seed')
            ->get()
        ;

        switch ($stage->type) {
            case OfficialStage::TYPE_SINGLE_ELIM:
                $service = new SingleElimService();
                $matches = $service->generateBracket(
                    $stage,
                    $participants,
                    $request->input('include_third_place', false),
                );
                break;

            case OfficialStage::TYPE_DOUBLE_ELIM:
                $service = new DoubleElimService();
                $matches = $service->generateBracket($stage, $participants);
                break;

            case OfficialStage::TYPE_GROUP:
            case OfficialStage::TYPE_ROUND_ROBIN:
                $service = new RoundRobinService();
                $groupCount = $request->input('group_count', 4);

                if ($stage->type === OfficialStage::TYPE_ROUND_ROBIN) {
                    $matches = $service->generateRoundRobin($stage, $participants);
                } else {
                    $result = $service->generateGroups($stage, $participants, $groupCount);
                    $matches = $result['matches'];
                }
                break;

            default:
                return response()->json([
                    'message' => 'Unsupported stage type',
                ], 422);
        }

        return response()->json([
            'message' => 'Bracket generated successfully',
            'data'    => [
                'matches_created' => count($matches),
                'matches'         => MatchResource::collection(collect($matches)->take(10)), // Preview first 10
            ],
        ]);
    }

    /**
     * Get stage standings (for group/round robin)
     *
     * @param  OfficialTournament  $tournament
     * @param  OfficialStage  $stage
     * @return JsonResponse
     */
    public function standings(OfficialTournament $tournament, OfficialStage $stage): JsonResponse
    {
        if ($stage->tournament_id !== $tournament->id) {
            abort(404);
        }

        if (!$stage->isGroup()) {
            return response()->json([
                'message' => 'Standings only available for group stages',
            ], 422);
        }

        $service = new RoundRobinService();

        if ($stage->type === OfficialStage::TYPE_ROUND_ROBIN) {
            $standings = $service->calculateGroupStandings($stage, 'A');
        } else {
            $standings = $service->calculateAllGroupStandings($stage);
        }

        return response()->json([
            'data' => $standings,
        ]);
    }

    /**
     * Reset stage (delete all matches)
     *
     * @param  OfficialTournament  $tournament
     * @param  OfficialStage  $stage
     * @return JsonResponse
     */
    public function reset(OfficialTournament $tournament, OfficialStage $stage): JsonResponse
    {
        if ($stage->tournament_id !== $tournament->id) {
            abort(404);
        }

        // Check if any matches have been played
        if ($stage->matches()->where('status', '!=', 'pending')->exists()) {
            return response()->json([
                'message' => 'Cannot reset stage with played matches',
            ], 422);
        }

        // Delete all matches
        $stage->matches()->delete();

        return response()->json([
            'message' => 'Stage reset successfully',
        ]);
    }
}
