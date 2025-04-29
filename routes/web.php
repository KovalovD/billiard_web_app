<?php

use App\Auth\Http\Middleware\EnsureFrontendRequestsAreAuthenticated;
use App\Core\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Core\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
// use App\Http\Controllers\ProfileController; // Раскомментируй, если используешь

// --- Гостевые роуты ---
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');
    // POST '/login' не нужен, т.к. логин идет через API
});

// --- Аутентифицированные роуты ---
// Важно: middleware('auth') здесь может не работать с Bearer токенами из коробки.
// Тебе может понадобиться кастомный middleware, проверяющий токен через /api/auth/user,
// или использовать Sanctum SPA аутентификацию (cookie).
// Пока оставляем 'auth', предполагая, что ты настроишь проверку токена.
Route::middleware('auth')->group(function () { // Используем 'auth:sanctum' для API токенов
    Route::get('/', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/profile', function() {
        return Inertia::render('Profile/Edit');
    })->name('profile.edit');

    // --- Лиги ---
    Route::get('/leagues', function () {
        return Inertia::render('Leagues/Index');
    })->name('leagues.index');

    // Можно добавить проверку админа через Gate или кастомный мидлвер 'admin'
    Route::middleware(AdminMiddleware::class)->group(function() { // Пример использования мидлвера 'admin'
        Route::get('/leagues/create', function () {
            return Inertia::render('Leagues/Create');
        })->name('leagues.create');

        Route::get('/leagues/{league}/edit', function ($leagueId) {
            return Inertia::render('Leagues/Edit', ['leagueId' => $leagueId]);
        })->name('leagues.edit')->where('league', '[0-9]+');
    }); // Конец группы admin

    Route::get('/leagues/{league}', function ($leagueId) {
        return Inertia::render('Leagues/Show', ['leagueId' => $leagueId]);
    })->name('leagues.show')->where('league', '[0-9]+');


}); // Конец группы 'auth.frontend'

// Фолбэк для 404
Route::fallback(function() {
    return Inertia::render('Errors/404')->toResponse(request())->setStatusCode(404);
});
