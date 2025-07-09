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
     * Get all tables for a club
     * @admin
     */
    public function index(Request $request, Club $club): AnonymousResourceCollection
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
     * Get a single table
     * @admin
     */
    public function show(Club $club, ClubTable $table): ClubTableResource
    {
        // Ensure table belongs to club
        if ($table->club_id !== $club->id) {
            abort(404);
        }

        return new ClubTableResource($table);
    }

    /**
     * Create a new table
     * @admin
     */
    public function store(ClubTableRequest $request, Club $club): JsonResponse
    {
        $data = $request->validated();
        $data['club_id'] = $club->id;

        // Auto-increment sort order if not provided
        if (!isset($data['sort_order'])) {
            $data['sort_order'] = $club->tables()->max('sort_order') + 1 ?? 1;
        }

        $table = ClubTable::create($data);

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
    public function destroy(Club $club, ClubTable $table): JsonResponse
    {
        // Ensure table belongs to club
        if ($table->club_id !== $club->id) {
            abort(404);
        }

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
     * Reorder tables
     * @admin
     */
    public function reorder(Request $request, Club $club): JsonResponse
    {
        $validated = $request->validate([
            'tables'              => 'required|array',
            'tables.*.id'         => 'required|exists:club_tables,id',
            'tables.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['tables'] as $tableData) {
            ClubTable::where('id', $tableData['id'])
                ->where('club_id', $club->id)
                ->update(['sort_order' => $tableData['sort_order']])
            ;
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
    public function update(ClubTableRequest $request, Club $club, ClubTable $table): JsonResponse
    {
        // Ensure table belongs to club
        if ($table->club_id !== $club->id) {
            abort(404);
        }

        $table->update($request->validated());

        return response()->json([
            'success' => true,
            'table'   => new ClubTableResource($table),
            'message' => 'Table updated successfully',
        ]);
    }
}
