<?php

namespace App\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to ensure authenticated access to frontend routes.
 * Handles both API requests and web requests differently.
 */
class EnsureFrontendRequestsAreAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if in DEBUG mode for logging
        $shouldLog = config('app.debug', false);

        if ($shouldLog) {
            Log::debug('[Auth Middleware] Running for path: '.$request->path());
        }

        // Always use Sanctum guard to check auth status
        $isAuthenticated = Auth::guard('sanctum')->check();

        if ($shouldLog) {
            Log::debug('[Auth Middleware] Authentication status: '.($isAuthenticated ? 'Authenticated' : 'Not Authenticated'));
        }

        if (!$isAuthenticated) {
            // Handle unauthenticated access
            return $this->handleUnauthenticatedRequest($request, $shouldLog);
        }

        // User is authenticated - proceed with the request
        return $next($request);
    }

    /**
     * Handle unauthenticated requests appropriately based on request type
     *
     * @param  Request  $request
     * @param  bool  $shouldLog
     * @return Response
     */
    private function handleUnauthenticatedRequest(Request $request, bool $shouldLog): Response
    {
        // Check if this is an API request (JSON expected or under /api/)
        if ($request->expectsJson() || $request->is('api/*')) {
            if ($shouldLog) {
                Log::debug('[Auth Middleware] API request - returning 401 response');
            }

            return response()->json([
                'message' => 'Unauthenticated',
                'status'  => 'error',
                'code'    => 'unauthorized',
            ], 401);
        }

        // This is a web request - redirect to login
        $loginRoute = route('login');

        if ($shouldLog) {
            Log::debug('[Auth Middleware] Web request - redirecting to: '.$loginRoute);
        }

        return redirect()->guest($loginRoute);
    }
}
