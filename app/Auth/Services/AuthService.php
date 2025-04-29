<?php

namespace App\Auth\Services;

use App\Auth\DataTransferObjects\LoginDTO;
use App\Auth\DataTransferObjects\LogoutDTO;
use App\Auth\Repositories\AuthRepository;
use App\Core\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;

/**
 * Service for handling authentication operations
 */
readonly class AuthService
{
    /**
     * @param AuthRepository $repository
     */
    public function __construct(private AuthRepository $repository)
    {
    }

    /**
     * Authenticate a user and create a new API token
     *
     * @param LoginDTO $loginDTO
     * @return array{user: User, token: string}
     * @throws ValidationException
     */
    public function login(LoginDTO $loginDTO): array
    {
        // Attempt authentication
        if (!Auth::attempt(['email' => $loginDTO->email, 'password' => $loginDTO->password], true)) {
            Log::info('Failed login attempt for email: ' . $loginDTO->email);

            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        $user = Auth::user();

        if (!$user) {
            // This should rarely happen, but just in case
            Log::error('Auth::attempt succeeded but user is null');
            throw ValidationException::withMessages([
                'email' => ['Authentication error occurred. Please try again.'],
            ]);
        }

        try {
            // Create token using repository
            $token = $this->repository->createToken($user, $loginDTO->deviceName)->plainTextToken;

            Log::info("User {$user->id} logged in successfully with device: {$loginDTO->deviceName}");

            return [
                'user'  => $user,
                'token' => $token,
            ];
        } catch (Exception $e) {
            Log::error('Failed to create token: ' . $e->getMessage());

            throw ValidationException::withMessages([
                'email' => ['Failed to create authentication token. Please try again.'],
            ]);
        }
    }

    /**
     * Logout a user by invalidating their token for the specified device
     *
     * @param User $user
     * @param LogoutDTO $logoutDTO
     * @return array{success: bool, message: string}
     */
    public function logout(User $user, LogoutDTO $logoutDTO): array
    {
        try {
            // Use repository to invalidate token
            $this->repository->invalidateToken($user, $logoutDTO->deviceName);

            Log::info("User {$user->id} logged out from device: {$logoutDTO->deviceName}");

            return [
                'success' => true,
                'message' => 'Successfully logged out.'
            ];
        } catch (Exception $e) {
            Log::warning("Error during logout for user {$user->id}: " . $e->getMessage());

            return [
                'success' => false,
                'message' => 'An error occurred during logout, but your session has been terminated.'
            ];
        }
    }
}
