<?php

use App\User\Http\Controllers\ProfileController;
use App\User\Http\Controllers\UserStatsController;

Route::middleware('auth:sanctum')->group(function () {
    Route::group(['prefix' => 'profile'], static function () {
        Route::match(['put', 'post'], '/', [ProfileController::class, 'update']);
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('profile.update-password');
        Route::put('/equipment', [ProfileController::class, 'updateEquipment']);
        Route::delete('/', [ProfileController::class, 'destroy'])->name('profile.destroy');
        Route::delete('/picture', [ProfileController::class, 'deletePicture'])->name('profile.delete-picture');
    });
    Route::group(['prefix' => 'user'], static function () {
        Route::get('ratings', [UserStatsController::class, 'ratings'])->name('user.ratings');
        Route::get('matches', [UserStatsController::class, 'matches'])->name('user.matches');
        Route::get('stats', [UserStatsController::class, 'stats'])->name('user.stats');
        Route::get('game-type-stats', [UserStatsController::class, 'gameTypeStats'])->name('user.game-type-stats');
    });
});
