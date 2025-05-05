<?php

namespace App\Auth\Http\Controllers;

use App\Auth\DataTransferObjects\LoginDTO;
use App\Auth\DataTransferObjects\LogoutDTO;
use App\Auth\DataTransferObjects\RegisterDTO;
use App\Auth\Http\Requests\LoginRequest;
use App\Auth\Http\Requests\LogoutRequest;
use App\Auth\Http\Requests\RegisterRequest;
use App\Auth\Services\AuthService;
use App\Core\Http\Resources\UserResource;
use Auth;
use Exception;
use Illuminate\Http\JsonResponse;
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
        try {
            $login = $this->authService->login(LoginDTO::fromRequest($request));

            return response()->json([
                'user'  => new UserResource($login['user']),
                'token' => $login['token'],
            ]);
        } catch (ValidationException) {
            return response()->json([
                'message' => 'Invalid credentials',
                'errors'  => [
                    'email' => ['These credentials do not match our records.'],
                ],
            ], 422);
        } catch (Exception) {
            return response()->json([
                'message' => 'An unexpected error occurred during login. Please try again.',
                'errors'  => [
                    'server' => ['Server error occurred. Please try again later.'],
                ],
            ], 500);
        }
    }

    /**
     * Register a new user
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $registration = $this->authService->register(RegisterDTO::fromRequest($request));

            return response()->json([
                'user'  => new UserResource($registration['user']),
                'token' => $registration['token'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'An unexpected error occurred during registration. Please try again.',
                'errors'  => [
                    'server' => [$e->getMessage() ?: 'Server error occurred. Please try again later.'],
                ],
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

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'No authenticated user found'], 401);
        }

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
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        return response()->json(new UserResource($user));
    }
}
