<?php

use App\Core\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Core\Http\Controllers\Auth\RegisteredUserController;
use App\Core\Http\Controllers\ErrorController;
use App\Core\Http\Middleware\AdminMiddleware;
use App\Core\Models\User;
use App\Leagues\Models\League;
use App\Matches\Models\MultiplayerGame;
use App\OfficialRatings\Models\OfficialRating;
use App\Tournaments\Http\Controllers\AdminTournamentBracketController;
use App\Tournaments\Http\Controllers\AdminTournamentGroupsController;
use App\Tournaments\Http\Controllers\AdminTournamentMatchesController;
use App\Tournaments\Http\Controllers\AdminTournamentSeedingController;
use App\Tournaments\Models\Tournament;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// --- Guest routes ---
Route::middleware('guest')->group(function () {
    // Explicitly define the login route with leading slash
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->name('login')
    ;

    Route::get('/register', [RegisteredUserController::class, 'create'])
        ->name('register')
    ;

    // For guests, homepage shows welcome page
    Route::get('/', static function () {
        return Inertia::render('Welcome');
    })->name('home');
});

// Widget route - public access for OBS
Route::group(['prefix' => 'widgets'], static function () {
    Route::get('/table', static function () {
        return Inertia::render('Widgets/TableWidget');
    })->name('widgets.table');

    Route::get('/killer-pool', static function () {
        return Inertia::render('Widgets/KillerPoolWidget');
    })->name('widgets.killer-pool');

    Route::get('/table-match', static function () {
        return Inertia::render('Widgets/TableMatch');
    })->name('widgets.table-match');
});

Route::get('/privacy-policy', static function () {
    return Inertia::render('Legal/PrivacyPolicy');
})->name('privacy');

Route::get('/service-agreement', static function () {
    return Inertia::render('Legal/ServiceAgreement');
})->name('agreement');

// Dashboard as home for authenticated users
Route::get('/dashboard', static function () {
    return Inertia::render('Dashboard', [
        'header' => 'Dashboard',
    ]);
})->name('dashboard');

// --- Leagues ---
Route::get('/leagues', static function () {
    return Inertia::render('Leagues/Index', [
        'header' => 'Leagues',
    ]);
})->name('leagues.index.page');

Route::get('/leagues/{league:slug}', static function ($leagueSlug) {
    $league = League::where('slug', $leagueSlug)->firstOrFail();
    return Inertia::render('Leagues/Show', [
        'leagueId'   => $league->id,
        'leagueSlug' => $league->slug,
    ]);
})->name('leagues.show.page');

Route::prefix('leagues/{league:slug}/multiplayer-games')->group(function () {
    Route::get('/', static function ($leagueSlug) {
        $league = League::where('slug', $leagueSlug)->firstOrFail();
        return Inertia::render('Leagues/MultiplayerGames/Index', [
            'leagueId'   => $league->id,
            'leagueSlug' => $league->slug,
        ]);
    })->name('leagues.multiplayer-games.index');

    Route::get('/{game:slug}', static function ($leagueSlug, $gameSlug) {
        $league = League::where('slug', $leagueSlug)->firstOrFail();
        $game = MultiplayerGame::where('slug', $gameSlug)
            ->where('league_id', $league->id)
            ->firstOrFail()
        ;
        return Inertia::render('Leagues/MultiplayerGames/Show', [
            'leagueId'   => $league->id,
            'leagueSlug' => $league->slug,
            'gameId'     => $game->id,
            'gameSlug'   => $game->slug,
        ]);
    })->name('leagues.multiplayer-games.show');
});

Route::get('/tournaments', static function () {
    return Inertia::render('Tournaments/Index');
})->name('tournaments.index.page');

Route::get('/tournaments/{tournament:slug}', static function ($tournamentSlug) {
    $tournament = Tournament::where('slug', $tournamentSlug)->firstOrFail();
    return Inertia::render('Tournaments/Show', [
        'tournamentId'   => $tournament->id,
        'tournamentSlug' => $tournament->slug,
    ]);
})->name('tournaments.show.page');

// Official Ratings routes
Route::get('/official-ratings', static function () {
    return Inertia::render('OfficialRatings/Index');
})->name('official-ratings.index');

Route::get('/official-ratings/{rating:slug}', static function ($ratingSlug) {
    $rating = OfficialRating::where('slug', $ratingSlug)->firstOrFail();
    return Inertia::render('OfficialRatings/Show', [
        'ratingId'   => $rating->id,
        'ratingSlug' => $rating->slug,
    ]);
})->name('official-ratings.show');

Route::get('/players', static function () {
    return Inertia::render('Players/Index', [
        'header' => 'Players',
    ]);
})->name('players.index.page');

Route::get('/players/{player:slug}', static function ($playerSlug) {
    $player = User::where('slug', $playerSlug)->firstOrFail();
    return Inertia::render('Players/Show', [
        'playerId'   => $player->id,
        'playerSlug' => $player->slug,
    ]);
})->name('players.show.page');

// --- Authenticated routes ---
Route::middleware('auth')->group(function () {
    Route::get('/profile/edit', static function () {
        return Inertia::render('Profile/Edit', [
            'header' => 'Edit',
        ]);
    })->name('profile.edit');

    Route::get('/profile/stats', static function () {
        return Inertia::render('Profile/Stats', [
            'header' => 'Statistics',
        ]);
    })->name('profile.stats');
    // --- Admin routes ---
    Route::middleware(AdminMiddleware::class)->prefix('admin')->group(function () {
        Route::group(['prefix' => 'leagues'], static function () {
            Route::get('{league:slug}/confirmed-players', static function ($leagueSlug) {
                $league = League::where('slug', $leagueSlug)->firstOrFail();
                return Inertia::render('Admin/ConfirmedPlayers', [
                    'leagueId'   => $league->id,
                    'leagueSlug' => $league->slug,
                ]);
            })->name('admin.leagues.confirmed-players');

            Route::get('create', static function () {
                return Inertia::render('Leagues/Create', [
                    'header' => 'Create League',
                ]);
            })->name('leagues.create');

            Route::get('{league:slug}/edit', static function ($leagueSlug) {
                $league = League::where('slug', $leagueSlug)->firstOrFail();
                return Inertia::render('Leagues/Edit', [
                    'leagueId'   => $league->id,
                    'leagueSlug' => $league->slug,
                    'header'   => 'Edit League',
                ]);
            })->name('leagues.edit');

            Route::get('{league:slug}/pending-players', static function ($leagueSlug) {
                $league = League::where('slug', $leagueSlug)->firstOrFail();
                return Inertia::render('Admin/PendingPlayers', [
                    'leagueId'   => $league->id,
                    'leagueSlug' => $league->slug,
                ]);
            })->name('admin.leagues.pending-players');
        });

        Route::group(['prefix' => 'tournaments'], static function () {
            Route::get('/{tournament:slug}/seeding', [AdminTournamentSeedingController::class, 'index'])
                ->name('admin.tournaments.seeding')
            ;
            Route::get('/{tournament:slug}/groups', [AdminTournamentGroupsController::class, 'index'])
                ->name('admin.tournaments.groups')
            ;
            Route::get('/{tournament:slug}/bracket', [AdminTournamentBracketController::class, 'index'])
                ->name('admin.tournaments.bracket')
            ;
            Route::get('/{tournament:slug}/matches', [AdminTournamentMatchesController::class, 'index'])
                ->name('admin.tournaments.matches')
            ;

            // Admin Tournament routes
            Route::get('/create', static function () {
                return Inertia::render('Admin/Tournaments/Create');
            })->name('admin.tournaments.create');

            Route::get('/{tournament:slug}/edit', static function ($tournamentSlug) {
                $tournament = Tournament::where('slug', $tournamentSlug)->firstOrFail();
                return Inertia::render('Admin/Tournaments/Edit', [
                    'tournamentId'   => $tournament->id,
                    'tournamentSlug' => $tournament->slug,
                ]);
            })->name('admin.tournaments.edit');

            Route::get('/{tournament:slug}/players', static function ($tournamentSlug) {
                $tournament = Tournament::where('slug', $tournamentSlug)->firstOrFail();
                return Inertia::render('Admin/Tournaments/Players', [
                    'tournamentId'   => $tournament->id,
                    'tournamentSlug' => $tournament->slug,
                ]);
            })->name('admin.tournaments.players');

            Route::get('/{tournament:slug}/results', static function ($tournamentSlug) {
                $tournament = Tournament::where('slug', $tournamentSlug)->firstOrFail();
                return Inertia::render('Admin/Tournaments/Results', [
                    'tournamentId'   => $tournament->id,
                    'tournamentSlug' => $tournament->slug,
                ]);
            })->name('admin.tournaments.results');
        });

        Route::group(['prefix' => 'official-ratings'], static function () {
            // Admin Official Ratings routes
            Route::get('/create', static function () {
                return Inertia::render('Admin/OfficialRatings/Create');
            })->name('admin.official-ratings.create');

            Route::get('/{rating:slug}/edit', static function ($ratingSlug) {
                $rating = OfficialRating::where('slug', $ratingSlug)->firstOrFail();
                return Inertia::render('Admin/OfficialRatings/Edit', [
                    'ratingId'   => $rating->id,
                    'ratingSlug' => $rating->slug,
                ]);
            })->name('admin.official-ratings.edit');

            Route::get('/{rating:slug}/manage', static function ($ratingSlug) {
                $rating = OfficialRating::where('slug', $ratingSlug)->firstOrFail();
                return Inertia::render('Admin/OfficialRatings/Manage', [
                    'ratingId'   => $rating->id,
                    'ratingSlug' => $rating->slug,
                ]);
            })->name('admin.official-ratings.manage');

            Route::get('/{rating:slug}/tournaments', static function ($ratingSlug) {
                $rating = OfficialRating::where('slug', $ratingSlug)->firstOrFail();
                return Inertia::render('Admin/OfficialRatings/Tournaments', [
                    'ratingId'   => $rating->id,
                    'ratingSlug' => $rating->slug,
                ]);
            })->name('admin.official-ratings.tournaments');

            Route::get('/{rating:slug}/players', static function ($ratingSlug) {
                $rating = OfficialRating::where('slug', $ratingSlug)->firstOrFail();
                return Inertia::render('Admin/OfficialRatings/Players', [
                    'ratingId'   => $rating->id,
                    'ratingSlug' => $rating->slug,
                ]);
            })->name('admin.official-ratings.players');
        });
    });
});


// --- Error routes ---
// These can be used programmatically by redirecting to them by name
Route::get('/404', [ErrorController::class, 'notFound'])->name('error.404');
Route::get('/403', [ErrorController::class, 'forbidden'])->name('error.403');
Route::get('/500', [ErrorController::class, 'serverError'])->name('error.500');

// Custom error route that accepts status parameter
Route::get('/error/{status}', [ErrorController::class, 'show'])
    ->where('status', '[0-9]+')
    ->name('error.custom')
;

// Fallback route for handling 404s
Route::fallback([ErrorController::class, 'notFound']);
