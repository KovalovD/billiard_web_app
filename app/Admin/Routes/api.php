<?php

use App\Admin\Http\Controllers\AdminPlayersController;
use App\Core\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(AdminMiddleware::class)->prefix('admin')->group(function () {
    // Routes for adding players to leagues
    Route::post('/leagues/{league}/add-player', [AdminPlayersController::class, 'addPlayerToLeague']);

    // Routes for adding players to multiplayer games
    Route::post('/leagues/{league}/multiplayer-games/{multiplayerGame}/add-player',
        [AdminPlayersController::class, 'addPlayerToGame']);
});
