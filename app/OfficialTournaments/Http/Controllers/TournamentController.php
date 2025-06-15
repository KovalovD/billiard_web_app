<?php

namespace App\OfficialTournaments\Http\Controllers;

use App\Core\Http\Controllers\Controller;
use App\OfficialTournaments\Http\Requests\CreateTournamentRequest;
use App\OfficialTournaments\Http\Requests\UpdateTournamentRequest;
use App\OfficialTournaments\Http\Resources\TournamentResource;
use App\OfficialTournaments\Models\OfficialTournament;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TournamentController extends Controller
{
    /**
     * Display a listing of tournaments
     *
     * @param  Request  $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = OfficialTournament::query()
            ->with(['city', 'club'])
        ;

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('discipline')) {
            $query->where('discipline', $request->input('discipline'));
        }

        if ($request->has('city_id')) {
            $query->where('city_id', $request->input('city_id'));
        }

        if ($request->has('date_from')) {
            $query->where('start_at', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->where('end_at', '<=', $request->input('date_to'));
        }

        // Sorting
        $sortField = $request->input('sort', 'start_at');
        $sortDirection = $request->input('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $tournaments = $query->paginate($request->input('per_page', 20));

        return TournamentResource::collection($tournaments);
    }

    /**
     * Store a newly created tournament
     *
     * @param  CreateTournamentRequest  $request
     * @return TournamentResource
     */
    public function store(CreateTournamentRequest $request): TournamentResource
    {
        $tournament = OfficialTournament::create($request->validated());

        return new TournamentResource($tournament->load(['city', 'club']));
    }

    /**
     * Display the specified tournament
     *
     * @param  OfficialTournament  $tournament
     * @return TournamentResource
     */
    public function show(OfficialTournament $tournament): TournamentResource
    {
        return new TournamentResource(
            $tournament->load([
                'city',
                'club',
                'stages.participants.user',
                'stages.participants.team',
                'poolTables',
            ]),
        );
    }

    /**
     * Update the specified tournament
     *
     * @param  UpdateTournamentRequest  $request
     * @param  OfficialTournament  $tournament
     * @return TournamentResource
     */
    public function update(UpdateTournamentRequest $request, OfficialTournament $tournament): TournamentResource
    {
        $tournament->update($request->validated());

        return new TournamentResource($tournament->load(['city', 'club']));
    }

    /**
     * Remove the specified tournament
     *
     * @param  OfficialTournament  $tournament
     * @return JsonResponse
     */
    public function destroy(OfficialTournament $tournament): JsonResponse
    {
        // Check if tournament has started
        if ($tournament->hasStarted()) {
            return response()->json([
                'message' => 'Cannot delete tournament that has already started',
            ], 422);
        }

        $tournament->delete();

        return response()->json([
            'message' => 'Tournament deleted successfully',
        ]);
    }

    /**
     * Get tournament statistics
     *
     * @param  OfficialTournament  $tournament
     * @return JsonResponse
     */
    public function statistics(OfficialTournament $tournament): JsonResponse
    {
        $stats = [
            'total_participants' => $tournament->participants()->count(),
            'total_matches'      => $tournament->matches()->count(),
            'completed_matches'  => $tournament
                ->matches()
                ->where('status', 'finished')
                ->count(),
            'stages'             => $tournament->stages()->count(),
            'prize_pool'         => $tournament->prize_pool,
            'entry_fee'          => $tournament->entry_fee,
        ];

        return response()->json(['data' => $stats]);
    }

    /**
     * Duplicate tournament structure
     *
     * @param  OfficialTournament  $tournament
     * @return TournamentResource
     */
    public function duplicate(OfficialTournament $tournament): TournamentResource
    {
        $newTournament = $tournament->replicate();
        $newTournament->name = $tournament->name.' (Copy)';
        $newTournament->start_at = now()->addMonth();
        $newTournament->end_at = now()->addMonth()->addDays(3);
        $newTournament->save();

        // Copy stages structure
        foreach ($tournament->stages as $stage) {
            $newStage = $stage->replicate();
            $newStage->tournament_id = $newTournament->id;
            $newStage->save();
        }

        // Copy pool tables
        foreach ($tournament->poolTables as $table) {
            $newTable = $table->replicate();
            $newTable->tournament_id = $newTournament->id;
            $newTable->save();
        }

        return new TournamentResource($newTournament->load(['city', 'club', 'stages', 'poolTables']));
    }
}
