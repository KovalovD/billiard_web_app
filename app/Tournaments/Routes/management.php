<?php

use App\Core\Http\Middleware\AdminMiddleware;
use App\Tournaments\Http\Controllers\TournamentManagementController;
use App\Tournaments\Http\Controllers\TournamentMatchController;
use App\Tournaments\Http\Controllers\TournamentStructureController;
use Illuminate\Support\Facades\Route;

// Tournament Management Routes (Admin only)
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

// Public Tournament Structure Routes
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

// Tournament Format and Configuration Endpoints
Route::prefix('tournament-config')
    ->group(function () {

        // Get available tournament formats and options
        Route::get('/formats', function () {
            return response()->json(\App\Tournaments\Enums\TournamentFormat::options());
        })->name('tournament-config.formats');

        Route::get('/seeding-methods', function () {
            return response()->json(\App\Tournaments\Enums\SeedingMethod::options());
        })->name('tournament-config.seeding-methods');

        Route::get('/best-of-rules', function () {
            return response()->json(\App\Tournaments\Enums\BestOfRule::options());
        })->name('tournament-config.best-of-rules');

        Route::get('/match-statuses', function () {
            return response()->json(\App\Tournaments\Enums\MatchStatus::options());
        })->name('tournament-config.match-statuses');

        Route::get('/team-roles', function () {
            return response()->json(\App\Tournaments\Enums\TeamRole::options());
        })->name('tournament-config.team-roles');
    })
;
