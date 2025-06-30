<?php

use App\Core\Http\Middleware\AdminMiddleware;
use App\Leagues\Http\Controllers\AdminPlayersController;
use App\Leagues\Http\Controllers\LeaguesController;
use App\Leagues\Http\Controllers\PlayersController;
use App\Matches\Http\Controllers\MatchGamesController;
use App\User\Http\Controllers\UserTournamentsController;

Route::apiResource('leagues', LeaguesController::class, ['only' => ['index', 'show']]);
Route::get('leagues/{league}/players', [LeaguesController::class, 'players'])->name('leagues.players');
Route::get('leagues/{league}/games', [LeaguesController::class, 'games'])->name('leagues.games');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('games', [LeaguesController::class, 'availableGames'])->name('leagues.available-games');
    Route::group(['prefix' => 'user/tournaments'], static function () {

        Route::get('my-tournaments-and-applications', [
            UserTournamentsController::class, 'myTournamentsAndApplications',
        ])->name('user.tournaments.my-tournaments-and-applications');
    });

    Route::post('my-leagues-and-challenges',
        [LeaguesController::class, 'myLeaguesAndChallenges'])->name('my-leagues-and-challenges');

    Route::group(['prefix' => 'leagues/{league}'], static function () {
        Route::get('load-user-rating', [LeaguesController::class, 'loadUserRating'])->name('leagues.load-user-rating');

        Route::group(['prefix' => 'players'], static function () {
            Route::post('enter', [PlayersController::class, 'enter'])->name('players.enter');
            Route::post('leave', [PlayersController::class, 'leave'])->name('players.leave');

            Route::post('{user}/send-match-game',
                [MatchGamesController::class, 'sendMatchGame'])->name('players.send-match-game');
            Route::group(['prefix' => 'match-games/{matchGame}'], static function () {
                Route::post('accept', [MatchGamesController::class, 'acceptMatch'])->name('players.accept-match');
                Route::post('decline', [MatchGamesController::class, 'declineMatch'])->name('players.decline-match');
                Route::post('send-result', [MatchGamesController::class, 'sendResult'])->name('players.send-result');
            });
        });
    });
});

Route::middleware(['auth:sanctum', AdminMiddleware::class])->group(function () {
    Route::apiResource('leagues', LeaguesController::class,
        ['only' => ['store', 'update', 'destroy']],
    );

    Route::group(['prefix' => 'leagues/{league}/admin'], static function () {
        Route::get('pending-players', [AdminPlayersController::class, 'pendingPlayers'])->name('admin.pending-players');
        Route::post('confirm-player/{rating}',
            [AdminPlayersController::class, 'confirmPlayer'])->name('admin.confirm-player');
        Route::post('reject-player/{rating}',
            [AdminPlayersController::class, 'rejectPlayer'])->name('admin.reject-player');
        Route::post('bulk-confirm', [AdminPlayersController::class, 'bulkConfirmPlayers'])->name('admin.bulk-confirm');
        Route::get('confirmed-players',
            [AdminPlayersController::class, 'confirmedPlayers'])->name('admin.confirmed-players');
        Route::post('deactivate-player/{rating}',
            [AdminPlayersController::class, 'deactivatePlayer'])->name('admin.deactivate-player');
        Route::post('bulk-deactivate',
            [AdminPlayersController::class, 'bulkDeactivatePlayers'])->name('admin.bulk-deactivate');
    });
});
