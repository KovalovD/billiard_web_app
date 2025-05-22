<?php

use App\Core\Http\Middleware\AdminMiddleware;
use App\OfficialRatings\Http\Controllers\AdminOfficialRatingsController;
use App\OfficialRatings\Http\Controllers\OfficialRatingsController;
use Illuminate\Support\Facades\Route;

// Public official ratings routes
Route::group(['prefix' => 'official-ratings'], static function () {
    Route::get('/', [OfficialRatingsController::class, 'index']);
    Route::get('/active', [OfficialRatingsController::class, 'active']);
    Route::get('/{officialRating}', [OfficialRatingsController::class, 'show']);
    Route::get('/{officialRating}/players', [OfficialRatingsController::class, 'players']);
    Route::get('/{officialRating}/tournaments', [OfficialRatingsController::class, 'tournaments']);
    Route::get('/{officialRating}/top-players', [OfficialRatingsController::class, 'topPlayers']);
    Route::get('/{officialRating}/players/{userId}', [OfficialRatingsController::class, 'playerRating']);
});

// Admin official ratings routes
Route::middleware(['auth:sanctum', AdminMiddleware::class])
    ->prefix('admin/official-ratings')
    ->group(function () {
        Route::post('/', [AdminOfficialRatingsController::class, 'store']);
        Route::put('/{officialRating}', [AdminOfficialRatingsController::class, 'update']);
        Route::delete('/{officialRating}', [AdminOfficialRatingsController::class, 'destroy']);

        // Tournament management
        Route::post('/{officialRating}/tournaments', [AdminOfficialRatingsController::class, 'addTournament']);
        Route::delete('/{officialRating}/tournaments/{tournament}',
            [AdminOfficialRatingsController::class, 'removeTournament']);

        // Player management
        Route::post('/{officialRating}/players', [AdminOfficialRatingsController::class, 'addPlayer']);
        Route::delete('/{officialRating}/players/{userId}', [AdminOfficialRatingsController::class, 'removePlayer']);

        // Rating management
        Route::post('/{officialRating}/recalculate', [AdminOfficialRatingsController::class, 'recalculatePositions']);
        Route::post('/{officialRating}/update-from-tournament/{tournament}',
            [AdminOfficialRatingsController::class, 'updateFromTournament']);
    })
;
