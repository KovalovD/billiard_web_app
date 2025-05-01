<?php

use App\Core\Http\Middleware\AdminMiddleware;
use App\Leagues\Http\Controllers\LeaguesController;
use App\Leagues\Http\Controllers\PlayersController;
use App\Matches\Http\Controllers\MatchGamesController;
use Illuminate\Support\Facades\Route;

// Public endpoints
Route::apiResource('leagues', LeaguesController::class, ['only' => ['index', 'show']]);

// Admin-only endpoints
Route::middleware(['auth:sanctum', AdminMiddleware::class])->group(function () {
    Route::apiResource('leagues', LeaguesController::class,
        ['only' => ['store', 'update', 'destroy']]);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('my-leagues-and-challenges', [LeaguesController::class, 'myLeaguesAndChallenges']);

    Route::prefix('leagues/{league}')->group(function () {
        Route::get('players', [LeaguesController::class, 'players']);
        Route::get('games', [LeaguesController::class, 'games']);


        Route::prefix('players')->group(function () {
            Route::post('enter', [PlayersController::class, 'enter']);
            Route::post('leave', [PlayersController::class, 'leave']);

            Route::post('{user}/send-match-game', [MatchGamesController::class, 'sendMatchGame']);
            Route::prefix('match-games/{matchGame}')->group(function () {
                Route::post('accept', [MatchGamesController::class, 'acceptMatch']);
                Route::post('decline', [MatchGamesController::class, 'declineMatch']);
                Route::post('send-result', [MatchGamesController::class, 'sendResult']);
            });
        });
    });
});
