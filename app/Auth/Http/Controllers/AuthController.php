<?php

namespace App\Auth\Http\Controllers;

use App\Auth\DataTransferObjects\LoginDTO;
use App\Auth\DataTransferObjects\LogoutDTO;
use App\Auth\Http\Requests\LoginRequest;
use App\Auth\Http\Requests\LogoutRequest;
use App\Auth\Services\AuthService;
use App\Core\Http\Resources\UserResource;
use Auth;
use Illuminate\Http\JsonResponse;

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
        $login = $this->authService->login(LoginDTO::fromRequest($request));

        return response()->json([
            'data'  => new UserResource($login['user']),
            'token' => $login['token'],
        ]);
    }

    /**
     * Logout user
     * @authenticated
     */
    public function logout(LogoutRequest $request): JsonResponse
    {
        return response()->json($this->authService->logout(Auth::user(), LogoutDTO::fromRequest($request)));
    }

    /**
     * Get authenticated user
     * @authenticated
     */
    public function user(): UserResource
    {
        return new UserResource(Auth::user());
    }
}
