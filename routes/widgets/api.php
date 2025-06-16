<?php

use App\Widgets\Http\Controllers\MultiplayerGamesController;
use Illuminate\Support\Facades\Route;

// Widget routes - public access for OBS
Route::prefix('widgets')->group(function () {
    Route::prefix('streaming')->group(function () {
        // Get current game data for widget
        Route::get('leagues/{league}/games/{game}', [MultiplayerGamesController::class, 'getCurrentGame'])
            ->name('widgets.streaming.current-game')
        ;

        // Get minimal game status for polling
        Route::get('leagues/{league}/games/{game}/status', [MultiplayerGamesController::class, 'getGameStatus'])
            ->name('widgets.streaming.game-status')
        ;
    });
});
