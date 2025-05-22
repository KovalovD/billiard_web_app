<?php

namespace App\Core\Http\Controllers;

use App\Core\Http\Resources\GameResource;
use App\Core\Models\Game;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GameController extends Controller
{
    /**
     * Get all available games for leagues and tournaments
     */
    public function availableGames(): AnonymousResourceCollection
    {
        $games = Game::orderBy('name')->get();

        return GameResource::collection($games);
    }

    /**
     * Get all games
     */
    public function index(): AnonymousResourceCollection
    {
        $games = Game::orderBy('name')->get();

        return GameResource::collection($games);
    }

    /**
     * Get a specific game
     */
    public function show(Game $game): GameResource
    {
        return new GameResource($game);
    }
}
