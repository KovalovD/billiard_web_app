<?php

use App\Core\Http\Middleware\AdminMiddleware;
use App\OfficialRatings\Http\Controllers\AdminOfficialRatingsController;
use App\OfficialRatings\Http\Controllers\GameRuleController;
use App\OfficialRatings\Http\Controllers\OfficialRatingsController;
use Illuminate\Support\Facades\Route;

Route::get('/game-rules', [GameRuleController::class, 'index']);
Route::get('/game-rules/{gameRule}', [GameRuleController::class, 'show']);
Route::get('/official-ratings/{officialRating:slug}/rules', [GameRuleController::class, 'getByRating']);

Route::middleware(['auth:sanctum', AdminMiddleware::class])->group(function () {
    Route::post('/game-rules', [GameRuleController::class, 'store']);
    Route::put('/game-rules/{gameRule}', [GameRuleController::class, 'update']);
    Route::delete('/game-rules/{gameRule}', [GameRuleController::class, 'destroy']);
});

// Public official ratings routes
Route::group(['prefix' => 'official-ratings'], static function () {
    Route::get('/', [OfficialRatingsController::class, 'index'])->name('official-ratings.index.api');
    Route::get('/active', [OfficialRatingsController::class, 'active'])->name('official-ratings.active');
    Route::get('/one-year-rating',
        [OfficialRatingsController::class, 'getOneYearRating'])->name('official-ratings.one-year-rating');

    Route::get('/{officialRating:slug}', [OfficialRatingsController::class, 'show'])->name('official-ratings.show.api');
    Route::get('/{officialRating:slug}/players',
        [OfficialRatingsController::class, 'players'])->name('official-ratings.players');
    Route::get('/{officialRating:slug}/tournaments',
        [OfficialRatingsController::class, 'tournaments'])->name('official-ratings.tournaments');
    Route::get('/{officialRating:slug}/top-players',
        [OfficialRatingsController::class, 'topPlayers'])->name('official-ratings.top-players');
    Route::get('/{officialRating:slug}/players/{userId}',
        [OfficialRatingsController::class, 'playerRating'])->name('official-ratings.player-rating');


    Route::middleware('auth:sanctum')->get('/{officialRating:slug}/player-delta',
        [OfficialRatingsController::class, 'playerDelta'])->name('official-ratings.player-delta');
});
// Admin official ratings routes
Route::middleware(['auth:sanctum', AdminMiddleware::class])
    ->prefix('admin/official-ratings')
    ->group(function () {
        Route::post('/', [AdminOfficialRatingsController::class, 'store'])->name('admin.official-ratings.store');
        Route::put('/{officialRating:slug}',
            [AdminOfficialRatingsController::class, 'update'])->name('admin.official-ratings.update');
        Route::delete('/{officialRating:slug}',
            [AdminOfficialRatingsController::class, 'destroy'])->name('admin.official-ratings.destroy');
        // Tournament management
        Route::post('/{officialRating:slug}/tournaments',
            [AdminOfficialRatingsController::class, 'addTournament'])->name('admin.official-ratings.add-tournament');
        Route::delete('/{officialRating:slug}/tournaments/{tournament:slug}',
            [
                AdminOfficialRatingsController::class, 'removeTournament',
            ])->name('admin.official-ratings.remove-tournament');

        // Player management
        Route::post('/{officialRating:slug}/players',
            [AdminOfficialRatingsController::class, 'addPlayer'])->name('admin.official-ratings.add-player');
        Route::delete('/{officialRating:slug}/players/{userId}',
            [AdminOfficialRatingsController::class, 'removePlayer'])->name('admin.official-ratings.remove-player');

        // Rating management
        Route::post('/{officialRating:slug}/recalculate', [
            AdminOfficialRatingsController::class, 'recalculatePositions',
        ])->name('admin.official-ratings.recalculate-positions');
        Route::post('/{officialRating:slug}/recalculate-from-records', [
            AdminOfficialRatingsController::class, 'recalculateFromRecords',
        ])->name('admin.official-ratings.recalculate-from-records');
        Route::post('/{officialRating:slug}/update-from-tournament/{tournament}',
            [
                AdminOfficialRatingsController::class, 'updateFromTournament',
            ])->name('admin.official-ratings.update-from-tournament');

        // Data integrity
        Route::get('/{officialRating:slug}/integrity-report',
            [AdminOfficialRatingsController::class, 'getIntegrityReport'])
            ->name('admin.official-ratings.integrity-report')
        ;
    })
;
