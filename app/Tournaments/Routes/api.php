<?php

use App\Core\Http\Middleware\AdminMiddleware;
use App\Tournaments\Http\Controllers\AdminTournamentApplicationsController;
use App\Tournaments\Http\Controllers\AdminTournamentsController;
use App\Tournaments\Http\Controllers\TournamentApplicationController;
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

    // Tournament application routes (authenticated users)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/{tournament}/apply',
            [TournamentApplicationController::class, 'apply'])->name('tournaments.apply');
        Route::delete('/{tournament}/cancel-application',
            [TournamentApplicationController::class, 'cancel'])->name('tournaments.cancel-application');
        Route::get('/{tournament}/application-status',
            [TournamentApplicationController::class, 'status'])->name('tournaments.application-status');
    });
});

Route::prefix('tournaments/{tournament}')
    ->group(function () {

        // Public tournament structure information
        Route::get('/structure', [TournamentStructureController::class, 'getStructure'])
            ->name('tournaments.structure')
        ;
        Route::get('/groups', [TournamentStructureController::class, 'getGroups'])
            ->name('tournaments.groups')
        ;
        Route::get('/groups/{group}/standings', [TournamentStructureController::class, 'getGroupStandings'])
            ->name('tournaments.group-standings')
        ;
        Route::get('/brackets', [TournamentStructureController::class, 'getBrackets'])
            ->name('tournaments.brackets')
        ;
        Route::get('/matches', [TournamentStructureController::class, 'getMatches'])
            ->name('tournaments.matches')
        ;
        Route::get('/schedule', [TournamentStructureController::class, 'getSchedule'])
            ->name('tournaments.schedule')
        ;

        // Team information for team tournaments
        Route::get('/teams', [TournamentStructureController::class, 'getTeams'])
            ->name('tournaments.teams')
        ;
        Route::get('/teams/{team}', [TournamentStructureController::class, 'getTeam'])
            ->name('tournaments.teams.show')
        ;
    })
;

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
    })
;

Route::middleware(['auth:sanctum', AdminMiddleware::class])
    ->prefix('admin/tournaments/{tournament}')
    ->group(function () {

        // Tournament Structure Management
        Route::post('/initialize-structure', [TournamentManagementController::class, 'initializeStructure'])
            ->name('admin.tournaments.initialize-structure')
        ;
        Route::post('/reset-structure', [TournamentManagementController::class, 'resetStructure'])
            ->name('admin.tournaments.reset-structure')
        ;
        Route::get('/structure-overview', [TournamentManagementController::class, 'getStructureOverview'])
            ->name('admin.tournaments.structure-overview')
        ;

        // Group Management
        Route::get('/groups', [TournamentManagementController::class, 'getGroups'])
            ->name('admin.tournaments.groups.index')
        ;
        Route::post('/groups', [TournamentManagementController::class, 'createGroups'])
            ->name('admin.tournaments.groups.store')
        ;
        Route::post('/assign-players-to-groups', [TournamentManagementController::class, 'assignPlayersToGroups'])
            ->name('admin.tournaments.assign-players-to-groups')
        ;

        // Team Management
        Route::get('/teams', [TournamentManagementController::class, 'getTeams'])
            ->name('admin.tournaments.teams.index')
        ;
        Route::post('/teams', [TournamentManagementController::class, 'createTeam'])
            ->name('admin.tournaments.teams.store')
        ;
        Route::put('/teams/{team}', [TournamentManagementController::class, 'updateTeam'])
            ->name('admin.tournaments.teams.update')
        ;
        Route::delete('/teams/{team}', [TournamentManagementController::class, 'deleteTeam'])
            ->name('admin.tournaments.teams.destroy')
        ;

        // Bracket Management
        Route::get('/brackets', [TournamentManagementController::class, 'getBrackets'])
            ->name('admin.tournaments.brackets.index')
        ;
        Route::post('/create-bracket-matches', [TournamentManagementController::class, 'createBracketMatches'])
            ->name('admin.tournaments.create-bracket-matches')
        ;

        // Match Management
        Route::get('/matches', [TournamentMatchController::class, 'index'])
            ->name('admin.tournaments.matches.index')
        ;
        Route::get('/matches/schedule', [TournamentMatchController::class, 'schedule'])
            ->name('admin.tournaments.matches.schedule')
        ;
        Route::get('/matches/round/{round}', [TournamentMatchController::class, 'byRound'])
            ->name('admin.tournaments.matches.by-round')
        ;
        Route::get('/matches/group/{groupId}', [TournamentMatchController::class, 'groupMatches'])
            ->name('admin.tournaments.matches.group')
        ;

        Route::get('/matches/{match}', [TournamentMatchController::class, 'show'])
            ->name('admin.tournaments.matches.show')
        ;
        Route::put('/matches/{match}', [TournamentMatchController::class, 'update'])
            ->name('admin.tournaments.matches.update')
        ;
        Route::post('/matches/{match}/result', [TournamentMatchController::class, 'enterResult'])
            ->name('admin.tournaments.matches.enter-result')
        ;
        Route::post('/matches/{match}/start', [TournamentMatchController::class, 'start'])
            ->name('admin.tournaments.matches.start')
        ;
        Route::post('/matches/{match}/cancel', [TournamentMatchController::class, 'cancel'])
            ->name('admin.tournaments.matches.cancel')
        ;
        Route::post('/matches/{match}/reschedule', [TournamentMatchController::class, 'reschedule'])
            ->name('admin.tournaments.matches.reschedule')
        ;

        Route::post('/matches/bulk-reschedule', [TournamentMatchController::class, 'bulkReschedule'])
            ->name('admin.tournaments.matches.bulk-reschedule')
        ;
    })
;
