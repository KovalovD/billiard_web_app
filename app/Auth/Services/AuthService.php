<?php

namespace App\Auth\Services;

use App\Auth\DataTransferObjects\LoginDTO;
use App\Auth\DataTransferObjects\LogoutDTO;
use App\Auth\Repositories\AuthRepository;
use App\Core\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

/**
 * Service for handling authentication operations
 */
readonly class AuthService
{
    /**
     * @param  AuthRepository  $repository
     */
    public function __construct(private AuthRepository $repository)
    {
    }

    /**
     * Authenticate a user and create a new API token
     *
     * @param  LoginDTO  $loginDTO
     * @return array{user: User, token: string}
     * @throws ValidationException|Exception
     */
    public function login(LoginDTO $loginDTO): array
    {
        // Attempt authentication
        if (!Auth::attempt(['email' => $loginDTO->email, 'password' => $loginDTO->password], true)) {
            Log::info('Failed login attempt for email: '.$loginDTO->email);

            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        $user = Auth::user();

        if (!$user) {
            // This should rarely happen, but just in case
            Log::error('Auth::attempt succeeded but user is null', [
                'email' => $loginDTO->email,
            ]);

            throw new Exception('Authentication system error. Please try again.');
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
            Log::error('Failed to create token', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            throw new Exception('Failed to create authentication token. Please try again.');
        }
    }

    /**
     * Logout a user by invalidating their token for the specified device
     *
     * @param  User  $user
     * @param  LogoutDTO  $logoutDTO
     * @return array{success: bool, message: string}
     */
    public function logout(User $user, LogoutDTO $logoutDTO): array
    {
        try {
            // Use repository to invalidate token
            $this->repository->invalidateToken($user, $logoutDTO->deviceName);

            // Invalidate all tokens when logging out from the web
            if ($logoutDTO->deviceName === 'web' || strpos($logoutDTO->deviceName, 'web-') === 0) {
                // Revoke all tokens for security
                $user->tokens()->delete();

                // Logout from web guard and invalidate session
                Auth::guard('web')->logout();
                Session::invalidate();
                Session::regenerateToken();
            }

            Log::info("User {$user->id} logged out from device: {$logoutDTO->deviceName}");

            return [
                'success' => true,
                'message' => 'Successfully logged out.',
            ];
        } catch (Exception $e) {
            Log::warning("Error during logout for user {$user->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred during logout, but your session has been terminated.',
            ];
        }
    }
}
