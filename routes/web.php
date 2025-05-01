<?php

use App\Core\Http\Controllers\Auth\AuthenticatedSessionController;
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
