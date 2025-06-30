<?php

use App\Core\Http\Controllers\GameController;
use App\Core\Http\Controllers\LocaleController;
use App\Payment\Http\Controllers\MonoWebhookController;
use App\User\Http\Controllers\CitiesController;
use App\User\Http\Controllers\ClubsController;
use Illuminate\Support\Facades\Route;

Route::any('/monobank/webhook', [MonoWebhookController::class, 'handle'])->name('monobank.webhook');

Route::prefix('locale')->group(function () {
    Route::post('/set', [LocaleController::class, 'setLocale']);
    Route::get('/current', [LocaleController::class, 'getLocale']);
});

// Cities and clubs are publicly accessible
Route::get('cities', [CitiesController::class, 'index'])->name('cities.index');
Route::get('clubs', [ClubsController::class, 'index'])->name('clubs.index');

Route::get('available-games', [GameController::class, 'availableGames'])->name('available-games');
