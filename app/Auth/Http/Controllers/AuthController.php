<?php

namespace App\Auth\Http\Controllers;

use App\Auth\DataTransferObjects\LoginDTO;
use App\Auth\DataTransferObjects\LogoutDTO;
use App\Auth\Http\Requests\LoginRequest;
use App\Auth\Http\Requests\LogoutRequest;
use App\Auth\Services\AuthService;
use App\Core\Http\Resources\UserResource;
use Auth;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * @group Auth
 */
readonly class AuthController
{
    public function __construct(private AuthService $authService)
    {
    }

    /**
     * Login user
     */
    public function login(LoginRequest $request): JsonResponse
    {
        // Log the login attempt (with sanitized data)
        Log::info('Login attempt', ['email' => $request->email, 'ip' => $request->ip()]);

        try {
            $login = $this->authService->login(LoginDTO::fromRequest($request));

            Log::info('Login successful', ['user_id' => $login['user']->id]);

            return response()->json([
                'user'  => new UserResource($login['user']),
                'token' => $login['token'],
            ]);
        } catch (ValidationException $e) {
            Log::warning('Login failed - invalid credentials', ['email' => $request->email]);

            return response()->json([
                'message' => 'Invalid credentials',
                'errors' => [
                    'email' => ['These credentials do not match our records.'],
                ]
            ], 422);
        } catch (Exception $e) {
            Log::error('Login failed - unexpected error', [
                'email' => $request->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'An unexpected error occurred during login. Please try again.',
                'errors' => [
                    'server' => ['Server error occurred. Please try again later.'],
                ]
            ], 500);
        }
    }

    /**
     * Logout user
     * @authenticated
     */
    public function logout(LogoutRequest $request): JsonResponse
    {
        $user = Auth::user();

        // Since route is protected by auth middleware, user should always exist
        // But we'll check anyway as a defensive measure
        if (!$user) {
            Log::warning('Logout attempt with no authenticated user');
            return response()->json(['success' => false, 'message' => 'No authenticated user found'], 401);
        }

        Log::info('Logout', ['user_id' => $user->id, 'device' => $request->deviceName]);

        $result = $this->authService->logout($user, LogoutDTO::fromRequest($request));

        return response()->json($result);
    }

    /**
     * Get authenticated user
     * @authenticated
     */
    public function user(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            Log::warning('User endpoint accessed with no authenticated user');
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        Log::info('User info requested', ['user_id' => $user->id]);

        return response()->json(new UserResource($user));
    }
}
