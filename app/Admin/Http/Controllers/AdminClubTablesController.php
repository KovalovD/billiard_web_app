<?php

namespace App\Admin\Http\Controllers;

use App\Admin\Http\Requests\ClubTableRequest;
use App\Core\Http\Resources\ClubTableResource;
use App\Core\Models\Club;
use App\Core\Models\ClubTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminClubTablesController
{
    /**
     * Get all tables
     * @admin
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = ClubTable::with('club.city.country');

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhereHas('club', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                ;
            });
        }

        if ($request->has('club_id')) {
            $query->where('club_id', $request->get('club_id'));
        }

        $tables = $query
            ->orderBy('club_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($request->get('per_page', 50))
        ;

        return ClubTableResource::collection($tables);
    }

    /**
     * Get a single table
     * @admin
     */
    public function show(ClubTable $table): ClubTableResource
    {
        $table->load(['club.city.country']);
        return new ClubTableResource($table);
    }

    /**
     * Create a new table
     * @admin
     */
    public function store(ClubTableRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Auto-increment sort order if not provided
        if (!isset($data['sort_order'])) {
            $maxOrder = ClubTable::where('club_id', $data['club_id'])->max('sort_order');
            $data['sort_order'] = ($maxOrder ?? 0) + 1;
        }

        $table = ClubTable::create($data);
        $table->load(['club.city.country']);

        return response()->json([
            'success' => true,
            'table'   => new ClubTableResource($table),
            'message' => 'Table created successfully',
        ], 201);
    }

    /**
     * Delete a table
     * @admin
     */
    public function destroy(ClubTable $table): JsonResponse
    {
        // Check if table is used in any active matches
        if ($table->activeMatch()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete table with active matches',
            ], 422);
        }

        if ($table->tournamentTableWidgets()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete table with tournament widgets',
            ], 422);
        }

        $table->delete();

        return response()->json([
            'success' => true,
            'message' => 'Table deleted successfully',
        ]);
    }

    /**
     * Get all tables for a specific club
     * @admin
     */
    public function clubTables(Club $club): AnonymousResourceCollection
    {
        $tables = $club
            ->tables()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
        ;

        return ClubTableResource::collection($tables);
    }

    /**
     * Reorder tables for a club
     * @admin
     */
    public function reorder(Request $request, Club $club): JsonResponse
    {
        $request->validate([
            'tables'              => 'required|array',
            'tables.*.id'         => 'required|exists:club_tables,id',
            'tables.*.sort_order' => 'required|integer|min:1',
        ]);

        foreach ($request->tables as $tableData) {
            $table = ClubTable::find($tableData['id']);

            // Ensure table belongs to club
            if ($table->club_id !== $club->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid table for this club',
                ], 422);
            }

            $table->update(['sort_order' => $tableData['sort_order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tables reordered successfully',
        ]);
    }

    /**
     * Update a table
     * @admin
     */
    public function update(ClubTableRequest $request, ClubTable $table): JsonResponse
    {
        $table->update($request->validated());
        $table->load(['club.city.country']);

        return response()->json([
            'success' => true,
            'table'   => new ClubTableResource($table),
            'message' => 'Table updated successfully',
        ]);
    }
}
