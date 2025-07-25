<?php

use App\Widgets\Http\Controllers\KillerPoolController;
use App\Widgets\Http\Controllers\TableMatchController;
use App\Widgets\Http\Controllers\TableWidgetController;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

// Widget routes - public access for OBS
Route::prefix('widgets')
    ->withoutMiddleware([StartSession::class, EncryptCookies::class, VerifyCsrfToken::class])
    ->group(function () {
        Route::get('table/tournaments/{tournament}/tables/{table}', [TableWidgetController::class, 'getCurrentMatch']);
        Route::get('table/tournaments/{tournament}/tables/{table}/status',
            [TableWidgetController::class, 'getMatchStatus']);

        Route::get('table-match/tournaments/{tournament}/tables/{table}',
            [TableMatchController::class, 'getCurrentMatch']);
        Route::post('table-match/tournaments/{tournament}/tables/{table}/score',
            [TableMatchController::class, 'updateScore']);

        Route::prefix('killer-pool')->group(function () {
            Route::get('leagues/{league}/games/{game}', [KillerPoolController::class, 'getCurrentGame'])
                ->name('widgets.killer-pool.current-game')
            ;
            Route::get('leagues/{league}/games/{game}/status', [KillerPoolController::class, 'getGameStatus'])
                ->name('widgets.killer-pool.game-status')
            ;
        });
    })
;
