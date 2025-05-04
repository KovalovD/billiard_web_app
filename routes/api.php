<?php

use App\Core\Http\Middleware\AdminMiddleware;
use App\Leagues\Http\Controllers\LeaguesController;
use App\Leagues\Http\Controllers\PlayersController;
use App\Matches\Http\Controllers\MatchGamesController;
use App\User\Http\Controllers\CitiesController;
use App\User\Http\Controllers\ClubsController;
use App\User\Http\Controllers\ProfileController;
use App\User\Http\Controllers\UserStatsController;
use Illuminate\Support\Facades\Route;

// Public endpoints
Route::apiResource('leagues', LeaguesController::class, ['only' => ['index', 'show']]);

// Cities and clubs are publicly accessible
Route::get('cities', [CitiesController::class, 'index']);
Route::get('clubs', [ClubsController::class, 'index']);

// Admin-only endpoints
Route::middleware(['auth:sanctum', AdminMiddleware::class])->group(function () {
    Route::apiResource('leagues', LeaguesController::class,
        ['only' => ['store', 'update', 'destroy']],
    );
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('my-leagues-and-challenges', [LeaguesController::class, 'myLeaguesAndChallenges']);
    Route::get('games', [LeaguesController::class, 'availableGames']);

    Route::prefix('profile')->group(function () {
        Route::put('/', [ProfileController::class, 'update']);
        Route::put('/password', [ProfileController::class, 'updatePassword']);
        Route::delete('/', [ProfileController::class, 'destroy']);
    });

    Route::prefix('user')->group(function () {
        Route::get('ratings', [UserStatsController::class, 'ratings']);
        Route::get('matches', [UserStatsController::class, 'matches']);
        Route::get('stats', [UserStatsController::class, 'stats']);
        Route::get('game-type-stats', [UserStatsController::class, 'gameTypeStats']);
    });

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
