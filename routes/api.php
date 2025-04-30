<?php

use App\Core\Http\Middleware\AdminMiddleware;
use App\Leagues\Http\Controllers\LeaguesController;
use App\Leagues\Http\Controllers\PlayersController;
use Illuminate\Support\Facades\Route;

// Public endpoints
Route::apiResource('leagues', LeaguesController::class, ['only' => ['index', 'show']]);

// League players public endpoint
Route::get('leagues/{league}/players', [LeaguesController::class, 'players']);

// Admin-only endpoints
Route::middleware(['auth:sanctum', AdminMiddleware::class])->group(function() {
    Route::apiResource('leagues', LeaguesController::class,
        ['only' => ['store', 'update', 'destroy']]);
});

// Authenticated player actions
Route::middleware('auth:sanctum')->group(function() {
    Route::prefix('leagues/{league}/players')->group(function() {
        Route::post('enter', [PlayersController::class, 'enter']);
        Route::post('leave', [PlayersController::class, 'leave']);
        Route::post('{user}/send-match-game', [PlayersController::class, 'sendMatchGame']);

        Route::prefix('match-games/{matchGame}')->group(function() {
            Route::post('accept', [PlayersController::class, 'acceptMatch']);
            Route::post('decline', [PlayersController::class, 'declineMatch']);
            Route::post('send-result', [PlayersController::class, 'sendResult']);
        });
    });
});

// Endpoint for getting all games (for league creation/editing)
// TODO: Implement this endpoint when you create a GamesController
// Route::get('games', [GamesController::class, 'index']);
