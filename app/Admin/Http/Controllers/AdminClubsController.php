<?php

namespace App\Admin\Http\Controllers;

use App\Admin\Http\Requests\ClubRequest;
use App\Admin\Http\Resources\ClubResource;
use App\Core\Models\Club;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminClubsController
{
    /**
     * Get all clubs
     * @admin
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Club::with(['city.country', 'tables']);

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->has('city_id')) {
            $query->where('city_id', $request->get('city_id'));
        }

        $clubs = $query->orderBy('name')->paginate($request->get('per_page', 50));

        return ClubResource::collection($clubs);
    }

    /**
     * Get a single club
     * @admin
     */
    public function show(Club $club): ClubResource
    {
        $club->load(['city.country', 'tables']);
        return new ClubResource($club);
    }

    /**
     * Create a new club
     * @admin
     */
    public function store(ClubRequest $request): JsonResponse
    {
        $club = Club::create($request->validated());
        $club->load(['city.country', 'tables']);

        return response()->json([
            'success' => true,
            'club'    => new ClubResource($club),
            'message' => 'Club created successfully',
        ], 201);
    }

    /**
     * Update a club
     * @admin
     */
    public function update(ClubRequest $request, Club $club): JsonResponse
    {
        $club->update($request->validated());
        $club->load(['city.country', 'tables']);

        return response()->json([
            'success' => true,
            'club'    => new ClubResource($club),
            'message' => 'Club updated successfully',
        ]);
    }

    /**
     * Delete a club
     * @admin
     */
    public function destroy(Club $club): JsonResponse
    {
        // Check if club has dependencies
        if ($club->leagues()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete club with existing leagues',
            ], 422);
        }

        if ($club->tournaments()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete club with existing tournaments',
            ], 422);
        }

        if ($club->users()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete club with existing users',
            ], 422);
        }

        if ($club->matchGames()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete club with existing matches',
            ], 422);
        }

        $club->delete();

        return response()->json([
            'success' => true,
            'message' => 'Club deleted successfully',
        ]);
    }
}
