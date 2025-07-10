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
    Route::apiResource('countries', AdminCountriesController::class)->names([
        'index'   => 'admin.countries.index',
        'show'    => 'admin.countries.show',
        'store'   => 'admin.countries.store',
        'update'  => 'admin.countries.update',
        'destroy' => 'admin.countries.destroy',
    ]);

// City management
    Route::apiResource('cities', AdminCitiesController::class)->names([
        'index'   => 'admin.cities.index',
        'show'    => 'admin.cities.show',
        'store'   => 'admin.cities.store',
        'update'  => 'admin.cities.update',
        'destroy' => 'admin.cities.destroy',
    ]);

// Club management
    Route::apiResource('clubs', AdminClubsController::class)->names([
        'index'   => 'admin.clubs.index',
        'show'    => 'admin.clubs.show',
        'store'   => 'admin.clubs.store',
        'update'  => 'admin.clubs.update',
        'destroy' => 'admin.clubs.destroy',
    ]);

    Route::apiResource('tables', AdminClubTablesController::class)->names([
        'index'   => 'admin.club.tables.index',
        'show'    => 'admin.club.tables.show',
        'store'   => 'admin.club.tables.store',
        'update'  => 'admin.club.tables.update',
        'destroy' => 'admin.club.tables.destroy',
    ]);


// Game management
    Route::apiResource('games', AdminGamesController::class)->names([
        'index'   => 'admin.games.index',
        'show'    => 'admin.games.show',
        'store'   => 'admin.games.store',
        'update'  => 'admin.games.update',
        'destroy' => 'admin.games.destroy',
    ]);

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
