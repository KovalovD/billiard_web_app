<?php

use App\Core\Http\Controllers\GameController;
use App\Core\Http\Controllers\LocaleController;
use App\Core\Http\Middleware\AdminMiddleware;
use App\Leagues\Http\Controllers\AdminPlayersController;
use App\Leagues\Http\Controllers\LeaguesController;
use App\Leagues\Http\Controllers\PlayersController;
use App\Matches\Http\Controllers\MatchGamesController;
use App\OfficialRatings\Http\Controllers\GameRuleController;
use App\Payment\Http\Controllers\MonoWebhookController;
use App\Players\Http\Controllers\PlayerController;
use App\User\Http\Controllers\CitiesController;
use App\User\Http\Controllers\ClubsController;
use App\User\Http\Controllers\ProfileController;
use App\User\Http\Controllers\UserStatsController;
use App\User\Http\Controllers\UserTournamentsController;
use Illuminate\Support\Facades\Route;

// Public endpoints
Route::apiResource('leagues', LeaguesController::class, ['only' => ['index', 'show']]);

Route::any('/monobank/webhook', [MonoWebhookController::class, 'handle'])->name('monobank.webhook');

Route::prefix('locale')->group(function () {
    Route::post('/set', [LocaleController::class, 'setLocale']);
    Route::get('/current', [LocaleController::class, 'getLocale']);
});
// Cities and clubs are publicly accessible
Route::get('cities', [CitiesController::class, 'index'])->name('cities.index');
Route::get('clubs', [ClubsController::class, 'index'])->name('clubs.index');
Route::get('user/tournaments/recent', [UserTournamentsController::class, 'recent'])->name('user.tournaments.recent');
Route::get('user/tournaments/upcoming',
    [UserTournamentsController::class, 'upcoming'])->name('user.tournaments.upcoming');
Route::get('leagues/{league}/players', [LeaguesController::class, 'players'])->name('leagues.players');
Route::get('leagues/{league}/games', [LeaguesController::class, 'games'])->name('leagues.games');

Route::get('/game-rules', [GameRuleController::class, 'index']);
Route::post('/game-rules', [GameRuleController::class, 'store']);
Route::get('/game-rules/{gameRule}', [GameRuleController::class, 'show']);
Route::get('/official-ratings/{officialRating}/rules', [GameRuleController::class, 'getByRating']);

Route::prefix('players')->group(function () {
    Route::get('/', [PlayerController::class, 'index'])->name('players.index');
    Route::get('/{player}', [PlayerController::class, 'show'])->name('players.show');
    Route::get('/{player1}/vs/{player2}', [PlayerController::class, 'headToHead'])->name('players.head-to-head');
});

// Admin-only endpoints
Route::middleware(['auth:sanctum', AdminMiddleware::class])->group(function () {
    Route::apiResource('leagues', LeaguesController::class,
        ['only' => ['store', 'update', 'destroy']],
    );

    Route::put('/game-rules/{gameRule}', [GameRuleController::class, 'update']);
    Route::delete('/game-rules/{gameRule}', [GameRuleController::class, 'destroy']);

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

Route::middleware('auth:sanctum')->group(function () {
    Route::group(['prefix' => 'user/tournaments'], static function () {

        Route::get('my-tournaments-and-applications', [
            UserTournamentsController::class, 'myTournamentsAndApplications',
        ])->name('user.tournaments.my-tournaments-and-applications');
    });

    Route::post('my-leagues-and-challenges',
        [LeaguesController::class, 'myLeaguesAndChallenges'])->name('my-leagues-and-challenges');
    Route::get('available-games', [GameController::class, 'availableGames'])->name('available-games');

    Route::get('games', [LeaguesController::class, 'availableGames'])->name('leagues.available-games');

    Route::group(['prefix' => 'profile'], static function () {
        Route::put('/', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('profile.update-password');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    Route::group(['prefix' => 'user'], static function () {
        Route::get('ratings', [UserStatsController::class, 'ratings'])->name('user.ratings');
        Route::get('matches', [UserStatsController::class, 'matches'])->name('user.matches');
        Route::get('stats', [UserStatsController::class, 'stats'])->name('user.stats');
        Route::get('game-type-stats', [UserStatsController::class, 'gameTypeStats'])->name('user.game-type-stats');
    });

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
