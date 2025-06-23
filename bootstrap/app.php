<?php

use App\Auth\Http\Middleware\EnsureFrontendRequestsAreAuthenticated;
use App\Core\Http\Middleware\AdminMiddleware;
use App\Core\Http\Middleware\HandleAppearance;
use App\Core\Http\Middleware\HandleInertiaRequests;
use App\Core\Http\Middleware\SetLocale;
use App\Core\Http\Middleware\TrustProxies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withRouting(
        api: __DIR__.'/../routes/widgets/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();
        $middleware->validateCsrfTokens(except: [
            'api/*',
            'api/widgets/*',
        ]);
        // Add TrustProxies as the first middleware
        $middleware->web(prepend: [
            TrustProxies::class,
        ]);

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);
        $middleware->alias([
            'auth.frontend' => EnsureFrontendRequestsAreAuthenticated::class,
            'auth.admin'    => AdminMiddleware::class,
        ]);
        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create()
;
