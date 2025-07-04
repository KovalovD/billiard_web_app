<?php

namespace App\Tournaments\Http\Controllers;

use App\Core\Http\Resources\ClubTableResource;
use App\Core\Models\ClubTable;
use App\Tournaments\Models\Tournament;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class TournamentTablesController
{
    /**
     * Get available tables for tournament
     */
    public function index(Tournament $tournament): AnonymousResourceCollection
    {
        $tables = ClubTable::where('club_id', $tournament->club_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
        ;

        return ClubTableResource::collection($tables);
    }

    /**
     * Update table stream URL
     */
    public function updateStreamUrl(Request $request, Tournament $tournament, ClubTable $table): JsonResponse
    {
        $request->validate([
            'stream_url' => 'nullable|string|url|max:255',
        ]);

        if ($table->club_id !== $tournament->club_id) {
            abort(403, 'Table does not belong to tournament club');
        }

        $table->update([
            'stream_url' => $request->input('stream_url'),
        ]);

        return response()->json([
            'message' => 'Stream URL updated successfully',
            'table'   => new ClubTableResource($table),
        ]);
    }

    /**
     * Get widget URL for table
     */
    public function getWidgetUrl(Tournament $tournament, ClubTable $table): JsonResponse
    {
        $widgetUrl = url("/widgets/table?tournament={$tournament->id}&table={$table->id}");

        return response()->json([
            'widget_url'  => $widgetUrl,
            'iframe_code' => '<iframe src="'.$widgetUrl.'" width="100%" height="400" frameborder="0"></iframe>',
        ]);
    }
}
