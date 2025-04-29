<?php

namespace App\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureFrontendRequestsAreAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        Log::info('[Middleware] Running EnsureFrontendRequestsAreAuthenticated for: ' . $request->path());

        // --- ИЗМЕНЕНИЕ ЗДЕСЬ ---
        // Используем ЯВНО гвард 'sanctum' для проверки токена, т.к. это не сессия
        $isAuthenticated = Auth::guard('sanctum')->check();
        // --- КОНЕЦ ИЗМЕНЕНИЯ ---

        Log::info('[Middleware] Auth::guard(\'sanctum\')->check() result: ' . ($isAuthenticated ? 'Authenticated' : 'NOT Authenticated'));

        if (!$isAuthenticated) {
            Log::info('[Middleware] User is NOT Authenticated.');

            // Если это API запрос (маловероятно для Inertia роутов, но на всякий случай)
            if ($request->expectsJson() || $request->is('api/*')) { // Добавим проверку на /api/
                Log::info('[Middleware] Request expects JSON or is API. Returning 401.');
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            // Иначе это веб-запрос (Inertia)
            $loginRoute = route('login');
            Log::info('[Middleware] Web request. Redirecting to login route: ' . $loginRoute);
            // Используем ->guest() для редиректа неаутентифицированных
            return redirect()->guest($loginRoute);
        }

        Log::info('[Middleware] User is Authenticated. Proceeding.');
        return $next($request);
    }
}
