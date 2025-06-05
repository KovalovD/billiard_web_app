<?php

use App\Core\Http\Middleware\AdminMiddleware;
use App\OfficialRatings\Http\Controllers\AdminOfficialRatingsController;
use App\OfficialRatings\Http\Controllers\OfficialRatingsController;
use Illuminate\Support\Facades\Route;

// Public official ratings routes
Route::group(['prefix' => 'official-ratings'], static function () {
    Route::get('/', [OfficialRatingsController::class, 'index'])->name('official-ratings.index.api');
    Route::get('/active', [OfficialRatingsController::class, 'active'])->name('official-ratings.active');
    Route::get('/{officialRating}', [OfficialRatingsController::class, 'show'])->name('official-ratings.show.api');
    Route::get('/{officialRating}/players',
        [OfficialRatingsController::class, 'players'])->name('official-ratings.players');
    Route::get('/{officialRating}/tournaments',
        [OfficialRatingsController::class, 'tournaments'])->name('official-ratings.tournaments');
    Route::get('/{officialRating}/top-players',
        [OfficialRatingsController::class, 'topPlayers'])->name('official-ratings.top-players');
    Route::get('/{officialRating}/players/{userId}',
        [OfficialRatingsController::class, 'playerRating'])->name('official-ratings.player-rating');

    Route::middleware('auth:sanctum')->get('/{officialRating}/player-delta',
        [OfficialRatingsController::class, 'playerDelta'])->name('official-ratings.player-delta');
});
// Admin official ratings routes
Route::middleware(['auth:sanctum', AdminMiddleware::class])
    ->prefix('admin/official-ratings')
    ->group(function () {
        Route::post('/', [AdminOfficialRatingsController::class, 'store'])->name('admin.official-ratings.store');
        Route::put('/{officialRating}',
            [AdminOfficialRatingsController::class, 'update'])->name('admin.official-ratings.update');
        Route::delete('/{officialRating}',
            [AdminOfficialRatingsController::class, 'destroy'])->name('admin.official-ratings.destroy');
        // Tournament management
        Route::post('/{officialRating}/tournaments',
            [AdminOfficialRatingsController::class, 'addTournament'])->name('admin.official-ratings.add-tournament');
        Route::delete('/{officialRating}/tournaments/{tournament}',
            [
                AdminOfficialRatingsController::class, 'removeTournament',
            ])->name('admin.official-ratings.remove-tournament');

        // Player management
        Route::post('/{officialRating}/players',
            [AdminOfficialRatingsController::class, 'addPlayer'])->name('admin.official-ratings.add-player');
        Route::delete('/{officialRating}/players/{userId}',
            [AdminOfficialRatingsController::class, 'removePlayer'])->name('admin.official-ratings.remove-player');

        // Rating management
        Route::post('/{officialRating}/recalculate', [
            AdminOfficialRatingsController::class, 'recalculatePositions',
        ])->name('admin.official-ratings.recalculate-positions');
        Route::post('/{officialRating}/recalculate-from-records', [
            AdminOfficialRatingsController::class, 'recalculateFromRecords',
        ])->name('admin.official-ratings.recalculate-from-records');
        Route::post('/{officialRating}/update-from-tournament/{tournament}',
            [
                AdminOfficialRatingsController::class, 'updateFromTournament',
            ])->name('admin.official-ratings.update-from-tournament');

        // Data integrity
        Route::get('/{officialRating}/integrity-report',
            [AdminOfficialRatingsController::class, 'getIntegrityReport'])
            ->name('admin.official-ratings.integrity-report')
        ;
    })
;
