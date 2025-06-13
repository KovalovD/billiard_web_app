<?php

use App\Core\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Core\Http\Controllers\Auth\RegisteredUserController;
use App\Core\Http\Controllers\ErrorController;
use App\Core\Http\Middleware\AdminMiddleware;
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
Route::get('/widgets/streaming', static function () {
    return Inertia::render('Widgets/StreamingWidget');
})->name('widgets.streaming');

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

Route::get('/leagues/{league}', static function ($leagueId) {
    return Inertia::render('Leagues/Show', [
        'leagueId' => $leagueId,
    ]);
})->name('leagues.show.page')->where('league', '[0-9]+');

Route::prefix('leagues/{leagueId}/multiplayer-games')->group(function () {
    Route::get('/', static function ($leagueId) {
        return Inertia::render('Leagues/MultiplayerGames/Index', [
            'leagueId' => $leagueId,
        ]);
    })->name('leagues.multiplayer-games.index');

    Route::get('/{gameId}', static function ($leagueId, $gameId) {
        return Inertia::render('Leagues/MultiplayerGames/Show', [
            'leagueId' => $leagueId,
            'gameId'   => $gameId,
        ]);
    })->name('leagues.multiplayer-games.show');
});

// --- Tournament Routes ---
Route::get('/tournaments', static function () {
    return Inertia::render('Tournaments/Index');
})->name('tournaments.index.page');

Route::get('/tournaments/{tournamentId}', static function ($tournamentId) {
    return Inertia::render('Tournaments/Show', [
        'tournamentId' => $tournamentId,
    ]);
})->name('tournaments.show.page')->where('tournamentId', '[0-9]+');

// Tournament Management Routes (for authenticated users)
Route::middleware('auth')->prefix('tournaments/{tournamentId}')->group(function () {

    // Player Management
    Route::get('/players', static function ($tournamentId) {
        return Inertia::render('Tournaments/Players', [
            'tournamentId' => (int) $tournamentId,
        ]);
    })->name('tournaments.players')->where('tournamentId', '[0-9]+');

    // Bracket Editor
    Route::get('/bracket', static function ($tournamentId) {
        return Inertia::render('Tournaments/Bracket', [
            'tournamentId' => (int) $tournamentId,
        ]);
    })->name('tournaments.bracket')->where('tournamentId', '[0-9]+');

    // Group Stage Management
    Route::get('/groups', static function ($tournamentId) {
        return Inertia::render('Tournaments/Groups', [
            'tournamentId' => (int) $tournamentId,
        ]);
    })->name('tournaments.groups')->where('tournamentId', '[0-9]+');

    // Match Schedule
    Route::get('/schedule', static function ($tournamentId) {
        return Inertia::render('Tournaments/Schedule', [
            'tournamentId' => (int) $tournamentId,
        ]);
    })->name('tournaments.schedule')->where('tournamentId', '[0-9]+');

    // Results & Statistics
    Route::get('/results', static function ($tournamentId) {
        return Inertia::render('Tournaments/Results', [
            'tournamentId' => (int) $tournamentId,
        ]);
    })->name('tournaments.results')->where('tournamentId', '[0-9]+');
});

// Official Ratings routes
Route::get('/official-ratings', static function () {
    return Inertia::render('OfficialRatings/Index');
})->name('official-ratings.index');

Route::get('/official-ratings/{ratingId}', static function ($ratingId) {
    return Inertia::render('OfficialRatings/Show', [
        'ratingId' => $ratingId,
    ]);
})->name('official-ratings.show')->where('ratingId', '[0-9]+');

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
            Route::get('{league}/confirmed-players', static function ($leagueId) {
                return Inertia::render('Admin/ConfirmedPlayers', [
                    'leagueId' => $leagueId,
                ]);
            })->name('admin.leagues.confirmed-players');

            Route::get('create', static function () {
                return Inertia::render('Leagues/Create', [
                    'header' => 'Create League',
                ]);
            })->name('leagues.create');

            Route::get('{league}/edit', static function ($leagueId) {
                return Inertia::render('Leagues/Edit', [
                    'leagueId' => $leagueId,
                    'header'   => 'Edit League',
                ]);
            })->name('leagues.edit')->where('league', '[0-9]+');

            Route::get('{league}/pending-players', static function ($leagueId) {
                return Inertia::render('Admin/PendingPlayers', [
                    'leagueId' => $leagueId,
                ]);
            })->name('admin.leagues.pending-players');
        });

        // Admin Tournament routes
        Route::get('/tournaments/create', static function () {
            return Inertia::render('Admin/Tournaments/Create');
        })->name('admin.tournaments.create');

        Route::get('/tournaments/{tournamentId}/edit', static function ($tournamentId) {
            return Inertia::render('Admin/Tournaments/Edit', [
                'tournamentId' => $tournamentId,
            ]);
        })->name('admin.tournaments.edit')->where('tournamentId', '[0-9]+');

        Route::get('/tournaments/{tournamentId}/players', static function ($tournamentId) {
            return Inertia::render('Admin/Tournaments/Players', [
                'tournamentId' => $tournamentId,
            ]);
        })->name('admin.tournaments.players')->where('tournamentId', '[0-9]+');

        Route::get('/tournaments/{tournamentId}/results', static function ($tournamentId) {
            return Inertia::render('Admin/Tournaments/Results', [
                'tournamentId' => $tournamentId,
            ]);
        })->name('admin.tournaments.results')->where('tournamentId', '[0-9]+');

        Route::get('/tournaments/{tournamentId}/applications', static function ($tournamentId) {
            return Inertia::render('Admin/Tournaments/Applications', [
                'tournamentId' => $tournamentId,
            ]);
        })->name('admin.tournaments.applications');

        // Admin Official Ratings routes
        Route::get('/official-ratings/create', static function () {
            return Inertia::render('Admin/OfficialRatings/Create');
        })->name('admin.official-ratings.create');

        Route::get('/official-ratings/{ratingId}/edit', static function ($ratingId) {
            return Inertia::render('Admin/OfficialRatings/Edit', [
                'ratingId' => $ratingId,
            ]);
        })->name('admin.official-ratings.edit')->where('ratingId', '[0-9]+');

        Route::get('/official-ratings/{ratingId}/manage', static function ($ratingId) {
            return Inertia::render('Admin/OfficialRatings/Manage', [
                'ratingId' => $ratingId,
            ]);
        })->name('admin.official-ratings.manage')->where('ratingId', '[0-9]+');

        Route::get('/official-ratings/{ratingId}/tournaments', static function ($ratingId) {
            return Inertia::render('Admin/OfficialRatings/Tournaments', [
                'ratingId' => $ratingId,
            ]);
        })->name('admin.official-ratings.tournaments')->where('ratingId', '[0-9]+');

        Route::get('/official-ratings/{ratingId}/players', static function ($ratingId) {
            return Inertia::render('Admin/OfficialRatings/Players', [
                'ratingId' => $ratingId,
            ]);
        })->name('admin.official-ratings.players')->where('ratingId', '[0-9]+');
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
