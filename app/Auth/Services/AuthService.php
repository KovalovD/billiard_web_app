<?php

namespace App\Auth\Services;

use App\Auth\DataTransferObjects\LoginDTO;
use App\Auth\DataTransferObjects\LogoutDTO;
use App\Auth\DataTransferObjects\RegisterDTO;
use App\Auth\Repositories\AuthRepository;
use App\Core\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Service for handling authentication operations
 */
class AuthService
{
    /**
     * @param  AuthRepository  $repository
     */
    public function __construct(private readonly AuthRepository $repository)
    {
    }

    /**
     * Register a new user with lastname matching logic
     *
     * @param  RegisterDTO  $registerDTO
     * @param  bool  $selfRegistration
     * @return array{user: User, token: string}
     */
    public function register(RegisterDTO $registerDTO, bool $selfRegistration = true): array
    {
        try {
            $user = null;

            // Only apply lastname matching logic for self-registration
            if ($selfRegistration) {
                $user = $this->findAndUpdateExistingUserByName($registerDTO);
            }

            // If no existing user found or not self-registration, create new user
            if (!$user) {
                $user = User::create([
                    'firstname' => $registerDTO->firstname,
                    'lastname'  => $registerDTO->lastname,
                    'email'     => $registerDTO->email,
                    'phone'     => $registerDTO->phone,
                    'password'  => Hash::make($registerDTO->password),
                ]);
            }

            // Log in the user if self-registration
            if ($selfRegistration) {
                Auth::login($user, true);
                $token = $this->repository->createToken($user, 'web')->plainTextToken;
            } else {
                $token = '';
            }

            return [
                'user'  => $user,
                'token' => $token,
            ];
        } catch (Exception $e) {
            throw new RuntimeException('Failed to register user: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Find existing user by lastname (and firstname if multiple matches) and update it
     *
     * @param  RegisterDTO  $registerDTO
     * @return User|null
     */
    private function findAndUpdateExistingUserByName(RegisterDTO $registerDTO): ?User
    {
        // Find users with matching lastname that haven't been changed once
        $candidateUsers = User::where('lastname', $registerDTO->lastname)
            ->where('is_changed_once', false)
            ->where('is_active', true)
            ->get()
        ;

        if ($candidateUsers->isEmpty()) {
            return null;
        }


        if ($candidateUsers->count() === 1) {
            // Only one user with this lastname - use it
            $userToUpdate = $candidateUsers->first();
        } else {
            // Multiple users with same lastname - check firstname too
            $exactMatch = $candidateUsers->where('firstname', $registerDTO->firstname)->first();

            if ($exactMatch) {
                $userToUpdate = $exactMatch;
            } else {
                // No exact match by firstname - don't update any user
                return null;
            }
        }

        if ($userToUpdate) {
            // Update the existing user with new data
            $userToUpdate->update([
                'firstname'         => $registerDTO->firstname,
                'email'             => $registerDTO->email,
                'phone'             => $registerDTO->phone,
                'password'          => Hash::make($registerDTO->password),
                'is_changed_once'   => true,
                'email_verified_at' => now(), // Auto-verify email for updated accounts
            ]);

            return $userToUpdate->fresh();
        }

        return null;
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
        if (!Auth::attempt(['email' => $loginDTO->email, 'password' => $loginDTO->password, 'is_active' => true],
            true)) {
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
        } catch (Exception) {
            return [
                'success' => false,
                'message' => 'An error occurred during logout, but your session has been terminated.',
            ];
        }
    }
}
