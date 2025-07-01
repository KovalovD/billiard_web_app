<?php

use App\Core\Http\Middleware\AdminMiddleware;
use App\Matches\Http\Controllers\MultiplayerGamesController;
use Illuminate\Support\Facades\Route;


Route::get('leagues/{league:slug}/multiplayer-games',
    [MultiplayerGamesController::class, 'index'])->name('multiplayer-games.index');
Route::get('leagues/{league:slug}/multiplayer-games/{multiplayerGame:slug}',
    [MultiplayerGamesController::class, 'show'])->name('multiplayer-games.show');

Route::middleware(['auth:sanctum'])->group(function () {
    // Multiplayer Games routes
    Route::group(['prefix' => 'leagues/{league:slug}/multiplayer-games'], static function () {
        Route::group(['prefix' => '{multiplayerGame:slug}'], static function () {
            Route::post('/join', [MultiplayerGamesController::class, 'join'])->name('multiplayer-games.join');
            Route::post('/leave', [MultiplayerGamesController::class, 'leave'])->name('multiplayer-games.leave');
            Route::post('/action',
                [MultiplayerGamesController::class, 'performAction'])->name('multiplayer-games.action');
            Route::post('/finish', [MultiplayerGamesController::class, 'finish'])->name('multiplayer-games.finish');
            Route::post('/set-moderator',
                [MultiplayerGamesController::class, 'setModerator'])->name('multiplayer-games.set-moderator');
            Route::get('/financial-summary', [
                MultiplayerGamesController::class, 'getFinancialSummary',
            ])->name('multiplayer-games.financial-summary');
            Route::get('/rating-summary',
                [MultiplayerGamesController::class, 'getRatingSummary'])->name('multiplayer-games.rating-summary');
        });

        // Admin-only routes
        Route::middleware(AdminMiddleware::class)->group(function () {
            Route::post('/', [MultiplayerGamesController::class, 'store'])->name('multiplayer-games.store');
            Route::post('/{multiplayerGame:slug}/start',
                [MultiplayerGamesController::class, 'start'])->name('multiplayer-games.start');
            Route::post('/{multiplayerGame:slug}/cancel',
                [MultiplayerGamesController::class, 'cancel'])->name('multiplayer-games.cancel');
            Route::delete('/{multiplayerGame:slug}/players/{user:slug}',
                [MultiplayerGamesController::class, 'removePlayer'])->name('multiplayer-games.remove-player');
        });
    });
});
