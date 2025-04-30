<?php

namespace App\Auth\Services;

use App\Auth\DataTransferObjects\LoginDTO;
use App\Auth\DataTransferObjects\LogoutDTO;
use App\Auth\Repositories\AuthRepository;
use App\Core\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use RuntimeException;

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
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        $user = Auth::user();

        if (!$user) {
            // This should rarely happen, but just in case
            throw new RuntimeException('Authentication system error. Please try again.');
        }

        try {
            // Create token using repository
            $token = $this->repository->createToken($user, $loginDTO->deviceName)->plainTextToken;

            return [
                'user'  => $user,
                'token' => $token,
            ];
        } catch (Exception) {
            throw new RuntimeException('Failed to create authentication token. Please try again.');
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
            if ($logoutDTO->deviceName === 'web' || str_starts_with($logoutDTO->deviceName, 'web-')) {
                // Revoke all tokens for security
                $user->tokens()->delete();

                // Logout from web guard and invalidate session
                Auth::guard('web')->logout();
                Session::invalidate();
                Session::regenerateToken();
            }

            return [
                'success' => true,
                'message' => 'Successfully logged out.',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'An error occurred during logout, but your session has been terminated.',
            ];
        }
    }
}
