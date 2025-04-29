<?php

use App\Auth\Http\Middleware\EnsureFrontendRequestsAreAuthenticated;
use App\Core\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Core\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// --- Guest routes ---
Route::middleware('guest')->group(function () {
    // Explicitly define the login route with leading slash
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    // For guests, homepage can directly show login - no need for redirect
    // This prevents potential redirect loops
    Route::get('/', function () {
        return Inertia::render('auth/Login');
    });
});

// --- Authenticated routes ---
Route::middleware('auth')->group(function () {
    // Dashboard as home for authenticated users
    Route::get('/', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // Explicitly define dashboard route separately to avoid conflicts
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    });

    Route::get('/profile', function() {
        return Inertia::render('Profile/Edit');
    })->name('profile.edit');

    // --- Leagues ---
    Route::get('/leagues', function () {
        return Inertia::render('Leagues/Index');
    })->name('leagues.index');

    Route::middleware(AdminMiddleware::class)->group(function() {
        Route::get('/leagues/create', function () {
            return Inertia::render('Leagues/Create');
        })->name('leagues.create');

        Route::get('/leagues/{league}/edit', function ($leagueId) {
            return Inertia::render('Leagues/Edit', ['leagueId' => $leagueId]);
        })->name('leagues.edit')->where('league', '[0-9]+');
    });

    Route::get('/leagues/{league}', function ($leagueId) {
        return Inertia::render('Leagues/Show', ['leagueId' => $leagueId]);
    })->name('leagues.show')->where('league', '[0-9]+');
});

// Fallback for 404
Route::fallback(function() {
    return Inertia::render('Errors/404')->toResponse(request())->setStatusCode(404);
});
