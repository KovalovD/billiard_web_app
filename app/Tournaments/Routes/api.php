<?php

use App\Core\Http\Middleware\AdminMiddleware;
use App\Tournaments\Http\Controllers\AdminTournamentApplicationsController;
use App\Tournaments\Http\Controllers\AdminTournamentMatchesController;
use App\Tournaments\Http\Controllers\AdminTournamentsController;
use App\Tournaments\Http\Controllers\AdminTournamentSeedingController;
use App\Tournaments\Http\Controllers\TournamentApplicationController;
use App\Tournaments\Http\Controllers\TournamentsController;
use App\Tournaments\Http\Controllers\TournamentTablesController;
use Illuminate\Support\Facades\Route;

// Public tournament routes
Route::group(['prefix' => 'tournaments'], static function () {
    Route::get('/{tournament}/brackets', [TournamentsController::class, 'brackets'])->name('tournaments.brackets');
    Route::get('/{tournament}/groups', [TournamentsController::class, 'groups'])->name('tournaments.groups');
    Route::get('/{tournament}/tables', [TournamentsController::class, 'tables'])->name('tournaments.tables');

    Route::get('/', [TournamentsController::class, 'index'])->name('tournaments.index');
    Route::get('/upcoming', [TournamentsController::class, 'upcoming'])->name('tournaments.upcoming');
    Route::get('/active', [TournamentsController::class, 'active'])->name('tournaments.active');
    Route::get('/completed', [TournamentsController::class, 'completed'])->name('tournaments.completed');
    Route::get('/{tournament}', [TournamentsController::class, 'show'])->name('tournaments.show');
    Route::get('/{tournament}/players', [TournamentsController::class, 'players'])->name('tournaments.players');
    Route::get('/{tournament}/results', [TournamentsController::class, 'results'])->name('tournaments.results');

    // Tournament application routes (authenticated users)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/{tournament}/apply',
            [TournamentApplicationController::class, 'apply'])->name('tournaments.apply');
        Route::delete('/{tournament}/cancel-application',
            [TournamentApplicationController::class, 'cancel'])->name('tournaments.cancel-application');
        Route::get('/{tournament}/application-status',
            [TournamentApplicationController::class, 'status'])->name('tournaments.application-status');
    });

    Route::get('/{tournament}/matches',
        [AdminTournamentsController::class, 'getMatches'])->name('admin.tournaments.matches.api');
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

        // Application management
        Route::get('/{tournament}/applications/pending',
            [
                AdminTournamentApplicationsController::class, 'pendingApplications',
            ])->name('admin.tournaments.pending-applications');
        Route::get('/{tournament}/applications/all',
            [
                AdminTournamentApplicationsController::class, 'allApplications',
            ])->name('admin.tournaments.all-applications');
        Route::post('/{tournament}/applications/{application}/confirm',
            [
                AdminTournamentApplicationsController::class, 'confirmApplication',
            ])->name('admin.tournaments.confirm-application');
        Route::post('/{tournament}/applications/{application}/reject',
            [
                AdminTournamentApplicationsController::class, 'rejectApplication',
            ])->name('admin.tournaments.reject-application');
        Route::post('/{tournament}/applications/bulk-confirm',
            [
                AdminTournamentApplicationsController::class, 'bulkConfirmApplications',
            ])->name('admin.tournaments.bulk-confirm-applications');
        Route::post('/{tournament}/applications/bulk-reject',
            [
                AdminTournamentApplicationsController::class, 'bulkRejectApplications',
            ])->name('admin.tournaments.bulk-reject-applications');

        // Tournament management
        Route::post('/{tournament}/results',
            [AdminTournamentsController::class, 'setResults'])->name('admin.tournaments.set-results');
        Route::post('/{tournament}/status',
            [AdminTournamentsController::class, 'changeStatus'])->name('admin.tournaments.change-status');

        // Utilities
        Route::get('/search-users',
            [AdminTournamentsController::class, 'searchUsers'])->name('admin.tournaments.search-users');

        // Add to app/Tournaments/Routes/api.php in the admin section

// Seeding management
        Route::post('/{tournament}/seeding/generate',
            [AdminTournamentSeedingController::class, 'generateSeeds'])->name('admin.tournaments.seeding.generate');
        Route::post('/{tournament}/seeding/update',
            [AdminTournamentSeedingController::class, 'updateSeeds'])->name('admin.tournaments.seeding.update');
        Route::post('/{tournament}/seeding/complete',
            [AdminTournamentSeedingController::class, 'completeSeeding'])->name('admin.tournaments.seeding.complete');

// Stage management
        Route::post('/{tournament}/stage',
            [AdminTournamentsController::class, 'changeStage'])->name('admin.tournaments.change-stage');

// Bracket and group generation
        Route::post('/{tournament}/bracket/generate',
            [AdminTournamentsController::class, 'generateBracket'])->name('admin.tournaments.generate-bracket');
        Route::post('/{tournament}/groups/generate',
            [AdminTournamentsController::class, 'generateGroups'])->name('admin.tournaments.generate-groups');

        Route::get('/{tournament}/stage-transitions',
            [AdminTournamentsController::class, 'getStageTransitions'])->name('admin.tournaments.stage-transitions');


        Route::get('/{tournament}/matches',
            [AdminTournamentsController::class, 'getMatches'])->name('admin.tournaments.matches.api');
        Route::post('/{tournament}/matches/{match}/start',
            [AdminTournamentMatchesController::class, 'startMatch'])->name('admin.tournaments.start-match');
        Route::post('/{tournament}/matches/{match}/finish',
            [AdminTournamentMatchesController::class, 'finishMatch'])->name('admin.tournaments.finish-match');
        Route::put('/{tournament}/matches/{match}',
            [AdminTournamentMatchesController::class, 'updateMatch'])->name('admin.tournaments.update-match');
        Route::get('/{tournament}/matches/{match}',
            [AdminTournamentMatchesController::class, 'show'])->name('admin.tournaments.get-match');

        Route::get('{tournament}/tables', [TournamentTablesController::class, 'index']);
        Route::put('{tournament}/tables/{table}/stream-url', [TournamentTablesController::class, 'updateStreamUrl']);
        Route::get('{tournament}/tables/{table}/widget-url', [TournamentTablesController::class, 'getWidgetUrl']);
    })
;
