<?php

use App\Admin\Http\Controllers\AdminCitiesController;
use App\Admin\Http\Controllers\AdminClubsController;
use App\Admin\Http\Controllers\AdminClubTablesController;
use App\Admin\Http\Controllers\AdminCountriesController;
use App\Admin\Http\Controllers\AdminGamesController;
use App\Admin\Http\Controllers\AdminPlayersController;
use App\Admin\Http\Controllers\AdminUserSearchController;
use App\Core\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(AdminMiddleware::class)->prefix('admin')->group(function () {

    // Country management
    Route::apiResource('countries', AdminCountriesController::class);

// City management
    Route::apiResource('cities', AdminCitiesController::class);

// Club management
    Route::apiResource('clubs', AdminClubsController::class);

// Club tables management
    Route::prefix('clubs/{club}/tables')->group(function () {
        Route::get('/', [AdminClubTablesController::class, 'index'])->name('admin.clubs.tables.index');
        Route::post('/', [AdminClubTablesController::class, 'store'])->name('admin.clubs.tables.store');
        Route::get('/{table}', [AdminClubTablesController::class, 'show'])->name('admin.clubs.tables.show');
        Route::put('/{table}', [AdminClubTablesController::class, 'update'])->name('admin.clubs.tables.update');
        Route::delete('/{table}', [AdminClubTablesController::class, 'destroy'])->name('admin.clubs.tables.destroy');
        Route::post('/reorder', [AdminClubTablesController::class, 'reorder'])->name('admin.clubs.tables.reorder');
    });

// Game management
    Route::apiResource('games', AdminGamesController::class);

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
