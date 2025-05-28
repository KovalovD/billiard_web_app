<?php

use App\Core\Http\Middleware\AdminMiddleware;
use App\Tournaments\Http\Controllers\AdminTournamentsController;
use App\Tournaments\Http\Controllers\TournamentsController;
use Illuminate\Support\Facades\Route;

// Public tournament routes
Route::group(['prefix' => 'tournaments'], static function () {
    Route::get('/', [TournamentsController::class, 'index'])->name('tournaments.index');
    Route::get('/upcoming', [TournamentsController::class, 'upcoming'])->name('tournaments.upcoming');
    Route::get('/active', [TournamentsController::class, 'active'])->name('tournaments.active');
    Route::get('/completed', [TournamentsController::class, 'completed'])->name('tournaments.completed');
    Route::get('/{tournament}', [TournamentsController::class, 'show'])->name('tournaments.show');
    Route::get('/{tournament}/players', [TournamentsController::class, 'players'])->name('tournaments.players');
    Route::get('/{tournament}/results', [TournamentsController::class, 'results'])->name('tournaments.results');
});

// Admin tournament routes
Route::middleware(['auth:sanctum', AdminMiddleware::class])
    ->prefix('admin/tournaments')
    ->group(function () {
        Route::post('/', [AdminTournamentsController::class, 'store'])->name('admin.tournaments.store');
        Route::put('/{tournament}', [AdminTournamentsController::class, 'update'])->name('admin.tournaments.update');
        Route::delete('/{tournament}',
            [AdminTournamentsController::class, 'destroy'])->name('admin.tournaments.destroy');

        // Player management
        Route::post('/{tournament}/players',
            [AdminTournamentsController::class, 'addPlayer'])->name('admin.tournaments.add-player');
        Route::post('/{tournament}/players/new',
            [AdminTournamentsController::class, 'addNewPlayer'])->name('admin.tournaments.add-new-player');
        Route::delete('/{tournament}/players/{player}',
            [AdminTournamentsController::class, 'removePlayer'])->name('admin.tournaments.remove-player');
        Route::put('/{tournament}/players/{player}',
            [AdminTournamentsController::class, 'updatePlayer'])->name('admin.tournaments.update-player');

        // Tournament management
        Route::post('/{tournament}/results',
            [AdminTournamentsController::class, 'setResults'])->name('admin.tournaments.set-results');
        Route::post('/{tournament}/status',
            [AdminTournamentsController::class, 'changeStatus'])->name('admin.tournaments.change-status');

        // Utilities
        Route::get('/search-users',
            [AdminTournamentsController::class, 'searchUsers'])->name('admin.tournaments.search-users');
    })
;
