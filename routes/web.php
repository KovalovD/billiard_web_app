<?php

use App\Core\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Core\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// --- Guest routes ---
Route::middleware('guest')->group(function () {
    // Explicitly define the login route with leading slash
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->name('login')
    ;

    // Registration routes
    Route::get('/register', function () {
        return Inertia::render('auth/Register');
    })->name('register');

    // For guests, homepage shows welcome page
    Route::get('/', function () {
        return Inertia::render('Welcome');
    })->name('home');
});

// --- Authenticated routes ---
Route::middleware('auth')->group(function () {
    Route::get('/profile/edit', function () {
        return Inertia::render('Profile/Edit', [
            'header' => 'Edit',
        ]);
    })->name('profile.edit');

    Route::get('/profile/stats', function () {
        return Inertia::render('Profile/Stats', [
            'header' => 'Statistics',
        ]);
    })->name('profile.stats');


    // Dashboard as home for authenticated users
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard', [
            'header' => 'Dashboard',
        ]);
    })->name('dashboard');

    Route::get('/profile', function () {
        return Inertia::render('Profile/Edit', [
            'header' => 'Profile Settings',
        ]);
    })->name('profile.edit');

    // --- Leagues ---
    Route::get('/leagues', function () {
        return Inertia::render('Leagues/Index', [
            'header' => 'Leagues',
        ]);
    })->name('leagues.index');

    Route::get('/leagues/{league}', function ($leagueId) {
        return Inertia::render('Leagues/Show', [
            'leagueId' => $leagueId,
        ]);
    })->name('leagues.show')->where('league', '[0-9]+');

    // --- Admin routes ---
    Route::middleware(AdminMiddleware::class)->group(function () {
        Route::get('/leagues/create', function () {
            return Inertia::render('Leagues/Create', [
                'header' => 'Create League',
            ]);
        })->name('leagues.create');

        Route::get('/leagues/{league}/edit', function ($leagueId) {
            return Inertia::render('Leagues/Edit', [
                'leagueId' => $leagueId,
                'header'   => 'Edit League',
            ]);
        })->name('leagues.edit')->where('league', '[0-9]+');
    });
});

// Fallback for 404
Route::fallback(function () {
    return Inertia::render('Errors/404')->toResponse(request())->setStatusCode(404);
});
