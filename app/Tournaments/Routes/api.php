<?php

use App\Core\Http\Middleware\AdminMiddleware;
use App\Tournaments\Http\Controllers\AdminTournamentsController;
use App\Tournaments\Http\Controllers\TournamentsController;
use Illuminate\Support\Facades\Route;

// Public tournament routes
Route::group(['prefix' => 'tournaments'], function () {
    Route::get('/', [TournamentsController::class, 'index']);
    Route::get('/upcoming', [TournamentsController::class, 'upcoming']);
    Route::get('/active', [TournamentsController::class, 'active']);
    Route::get('/completed', [TournamentsController::class, 'completed']);
    Route::get('/{tournament}', [TournamentsController::class, 'show']);
    Route::get('/{tournament}/players', [TournamentsController::class, 'players']);
    Route::get('/{tournament}/results', [TournamentsController::class, 'results']);
});

// Admin tournament routes
Route::middleware(['auth:sanctum', AdminMiddleware::class])
    ->prefix('admin/tournaments')
    ->group(function () {
        Route::post('/', [AdminTournamentsController::class, 'store']);
        Route::put('/{tournament}', [AdminTournamentsController::class, 'update']);
        Route::delete('/{tournament}', [AdminTournamentsController::class, 'destroy']);

        // Player management
        Route::post('/{tournament}/players', [AdminTournamentsController::class, 'addPlayer']);
        Route::post('/{tournament}/players/new', [AdminTournamentsController::class, 'addNewPlayer']);
        Route::delete('/{tournament}/players/{player}', [AdminTournamentsController::class, 'removePlayer']);
        Route::put('/{tournament}/players/{player}', [AdminTournamentsController::class, 'updatePlayer']);

        // Tournament management
        Route::post('/{tournament}/results', [AdminTournamentsController::class, 'setResults']);
        Route::post('/{tournament}/status', [AdminTournamentsController::class, 'changeStatus']);

        // Utilities
        Route::get('/search-users', [AdminTournamentsController::class, 'searchUsers']);
    })
;
