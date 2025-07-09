<?php

namespace App\Admin\Http\Controllers;

use App\Admin\Http\Requests\GameRequest;
use App\Admin\Http\Resources\GameResource;
use App\Core\Models\Game;
use App\Leagues\Models\League;
use App\Tournaments\Models\Tournament;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminGamesController
{
    /**
     * Get all games
     * @admin
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Game::query();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->has('type')) {
            $query->where('type', $request->get('type'));
        }

        if ($request->has('is_multiplayer')) {
            $query->where('is_multiplayer', $request->boolean('is_multiplayer'));
        }

        $games = $query->orderBy('name')->paginate($request->get('per_page', 50));

        return GameResource::collection($games);
    }

    /**
     * Get a single game
     * @admin
     */
    public function show(Game $game): GameResource
    {
        return new GameResource($game);
    }

    /**
     * Create a new game
     * @admin
     */
    public function store(GameRequest $request): JsonResponse
    {
        $game = Game::create($request->validated());

        return response()->json([
            'success' => true,
            'game'    => new GameResource($game),
            'message' => 'Game created successfully',
        ], 201);
    }

    /**
     * Update a game
     * @admin
     */
    public function update(GameRequest $request, Game $game): JsonResponse
    {
        $game->update($request->validated());

        return response()->json([
            'success' => true,
            'game'    => new GameResource($game),
            'message' => 'Game updated successfully',
        ]);
    }

    /**
     * Delete a game
     * @admin
     */
    public function destroy(Game $game): JsonResponse
    {
        // Check if game is used in leagues
        if (League::where('game_id', $game->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete game used in leagues',
            ], 422);
        }

        // Check if game is used in tournaments
        if (Tournament::where('game_id', $game->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete game used in tournaments',
            ], 422);
        }

        $game->delete();

        return response()->json([
            'success' => true,
            'message' => 'Game deleted successfully',
        ]);
    }
}
