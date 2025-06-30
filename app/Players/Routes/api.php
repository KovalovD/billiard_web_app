<?php

use App\Players\Http\Controllers\PlayerController;

Route::prefix('players')->group(function () {
    Route::get('/', [PlayerController::class, 'index'])->name('players.index');
    Route::get('/{player}', [PlayerController::class, 'show'])->name('players.show');
    Route::get('/{player1}/vs/{player2}', [PlayerController::class, 'headToHead'])->name('players.head-to-head');
    Route::get('/{player}/match-stats', [PlayerController::class, 'matchStats'])->name('players.match-stats');
});
