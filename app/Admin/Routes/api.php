<?php

use App\Admin\Http\Controllers\AdminPlayersController;
use App\Admin\Http\Controllers\AdminUserSearchController;
use App\Core\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(AdminMiddleware::class)->prefix('admin')->group(function () {
    // Search users
    Route::get('/search-users', [AdminUserSearchController::class, 'searchUsers'])->name('admin.search-users');

    // Routes for adding players to leagues
    Route::post('/leagues/{league}/players/add-existing',
        [AdminPlayersController::class, 'addExistingPlayer'])->name('admin.add-existing-player');
    Route::post('/leagues/{league}/players/add-new',
        [AdminPlayersController::class, 'addNewPlayer'])->name('admin.add-new-player');

    // Routes for adding players to multiplayer games
    Route::post('/leagues/{league}/multiplayer-games/{multiplayerGame}/players/add-existing',
        [AdminPlayersController::class, 'addExistingPlayerToGame'])->name('admin.add-existing-player-to-game');
    Route::post('/leagues/{league}/multiplayer-games/{multiplayerGame}/players/add-new',
        [AdminPlayersController::class, 'addNewPlayerToGame'])->name('admin.add-new-player-to-game');
});
