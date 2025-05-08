<?php

use App\Core\Http\Middleware\AdminMiddleware;
use App\Matches\Http\Controllers\MultiplayerGamesController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    // Multiplayer Games routes
    Route::prefix('leagues/{league}/multiplayer-games')->group(function () {
        Route::get('/', [MultiplayerGamesController::class, 'index']);
        Route::get('/{multiplayerGame}', [MultiplayerGamesController::class, 'show']);
        Route::post('/{multiplayerGame}/join', [MultiplayerGamesController::class, 'join']);
        Route::post('/{multiplayerGame}/leave', [MultiplayerGamesController::class, 'leave']);
        Route::post('/{multiplayerGame}/action', [MultiplayerGamesController::class, 'performAction']);
        Route::post('/{multiplayerGame}/finish', [MultiplayerGamesController::class, 'finish']);
        Route::post('/{multiplayerGame}/set-moderator', [MultiplayerGamesController::class, 'setModerator']);
        Route::get('/{multiplayerGame}/financial-summary', [MultiplayerGamesController::class, 'getFinancialSummary']);
        Route::get('/{multiplayerGame}/rating-summary', [MultiplayerGamesController::class, 'getRatingSummary']);

        // Admin-only routes
        Route::middleware(AdminMiddleware::class)->group(function () {
            Route::post('/', [MultiplayerGamesController::class, 'store']);
            Route::post('/{multiplayerGame}/start', [MultiplayerGamesController::class, 'start']);
            Route::post('/{multiplayerGame}/cancel', [MultiplayerGamesController::class, 'cancel']);
        });
    });
});
