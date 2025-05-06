<?php

use App\Core\Http\Middleware\AdminMiddleware;
use App\Leagues\Http\Controllers\AdminPlayersController;
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

    Route::group(['prefix' => 'leagues/{league}/admin'], static function () {
        Route::get('pending-players', [AdminPlayersController::class, 'pendingPlayers']);
        Route::post('confirm-player/{rating}', [AdminPlayersController::class, 'confirmPlayer']);
        Route::post('reject-player/{rating}', [AdminPlayersController::class, 'rejectPlayer']);
        Route::post('bulk-confirm', [AdminPlayersController::class, 'bulkConfirmPlayers']);
        Route::get('confirmed-players', [AdminPlayersController::class, 'confirmedPlayers']);
        Route::post('deactivate-player/{rating}', [AdminPlayersController::class, 'deactivatePlayer']);
        Route::post('bulk-deactivate', [AdminPlayersController::class, 'bulkDeactivatePlayers']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('my-leagues-and-challenges', [LeaguesController::class, 'myLeaguesAndChallenges']);
    Route::get('games', [LeaguesController::class, 'availableGames']);

    Route::group(['prefix' => 'profile'], static function () {
        Route::put('/', [ProfileController::class, 'update']);
        Route::put('/password', [ProfileController::class, 'updatePassword']);
        Route::delete('/', [ProfileController::class, 'destroy']);
    });

    Route::group(['prefix' => 'user'], static function () {
        Route::get('ratings', [UserStatsController::class, 'ratings']);
        Route::get('matches', [UserStatsController::class, 'matches']);
        Route::get('stats', [UserStatsController::class, 'stats']);
        Route::get('game-type-stats', [UserStatsController::class, 'gameTypeStats']);
    });

    Route::group(['prefix' => 'leagues/{league}'], static function () {
        Route::get('players', [LeaguesController::class, 'players']);
        Route::get('games', [LeaguesController::class, 'games']);
        Route::get('load-user-rating', [LeaguesController::class, 'loadUserRating']);

        Route::group(['prefix' => 'players'], static function () {
            Route::post('enter', [PlayersController::class, 'enter']);
            Route::post('leave', [PlayersController::class, 'leave']);

            Route::post('{user}/send-match-game', [MatchGamesController::class, 'sendMatchGame']);
            Route::group(['prefix' => 'match-games/{matchGame}'], static function () {
                Route::post('accept', [MatchGamesController::class, 'acceptMatch']);
                Route::post('decline', [MatchGamesController::class, 'declineMatch']);
                Route::post('send-result', [MatchGamesController::class, 'sendResult']);
            });
        });
    });
});
