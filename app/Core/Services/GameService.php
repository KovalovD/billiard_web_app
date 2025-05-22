<?php

namespace App\Core\Services;

use App\Core\Models\Game;
use Illuminate\Database\Eloquent\Collection;

class GameService
{
    /**
     * Get all available games
     */
    public function getAllGames(): Collection
    {
        return Game::orderBy('name')->get();
    }

    /**
     * Get games by type
     */
    public function getGamesByType(string $type): Collection
    {
        return Game::where('type', $type)->orderBy('name')->get();
    }

    /**
     * Get multiplayer games only
     */
    public function getMultiplayerGames(): Collection
    {
        return Game::where('is_multiplayer', true)->orderBy('name')->get();
    }

    /**
     * Get single player games only
     */
    public function getSinglePlayerGames(): Collection
    {
        return Game::where('is_multiplayer', false)->orderBy('name')->get();
    }

    /**
     * Create a new game
     */
    public function createGame(array $data): Game
    {
        return Game::create($data);
    }

    /**
     * Update an existing game
     */
    public function updateGame(Game $game, array $data): Game
    {
        $game->update($data);
        return $game->fresh();
    }

    /**
     * Delete a game
     */
    public function deleteGame(Game $game): bool
    {
        return $game->delete();
    }
}
