<?php

use App\Core\Http\Middleware\AdminMiddleware;
use App\Matches\Http\Controllers\MultiplayerGamesController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    // Multiplayer Games routes
    Route::group(['prefix' => 'leagues/{league}/multiplayer-games'], static function () {
        Route::get('/', [MultiplayerGamesController::class, 'index']);
        Route::group(['prefix' => '{multiplayerGame}'], static function () {
            Route::get('/', [MultiplayerGamesController::class, 'show']);
            Route::post('/join', [MultiplayerGamesController::class, 'join']);
            Route::post('/leave', [MultiplayerGamesController::class, 'leave']);
            Route::post('/action', [MultiplayerGamesController::class, 'performAction']);
            Route::post('/finish', [MultiplayerGamesController::class, 'finish']);
            Route::post('/set-moderator', [MultiplayerGamesController::class, 'setModerator']);
            Route::get('/financial-summary', [MultiplayerGamesController::class, 'getFinancialSummary']);
            Route::get('/rating-summary', [MultiplayerGamesController::class, 'getRatingSummary']);
        });

        // Admin-only routes
        Route::middleware(AdminMiddleware::class)->group(function () {
            Route::post('/', [MultiplayerGamesController::class, 'store']);
            Route::post('/{multiplayerGame}/start', [MultiplayerGamesController::class, 'start']);
            Route::post('/{multiplayerGame}/cancel', [MultiplayerGamesController::class, 'cancel']);
        });
    });
});
