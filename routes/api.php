<?php

use App\Core\Http\Middleware\AdminMiddleware;
use App\Leagues\Http\Controllers\LeaguesController;
use App\Leagues\Http\Controllers\PlayersController;

Route::apiResource('leagues', LeaguesController::class, ['only' => ['index', 'show']]);
Route::apiResource('leagues', LeaguesController::class,
    ['only' => ['store', 'update', 'destroy'], 'middleware' => 'auth','auth.admin']);
Route::group(['prefix' => 'leagues/{league}'], static function () {
    Route::get('players', [LeaguesController::class, 'players']);

    Route::group(['prefix' => 'players', 'middleware' => 'auth:sanctum'], static function () {
        Route::post('enter', [PlayersController::class, 'enter']);
        Route::post('leave', [PlayersController::class, 'leave']);
        Route::post('{user}/send-match-game', [PlayersController::class, 'sendMatchGame']);
        Route::group(['prefix' => 'match-games/{matchGame}'], static function () {
            Route::post('accept', [PlayersController::class, 'acceptMatch']);
            Route::post('decline', [PlayersController::class, 'declineMatch']);
            Route::post('send-result', [PlayersController::class, 'sendResult']);
        });
    });
});
