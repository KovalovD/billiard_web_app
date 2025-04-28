<?php

namespace App\Auth\Services;

use App\Auth\DataTransferObjects\LoginDTO;
use App\Auth\DataTransferObjects\LogoutDTO;
use App\Auth\Repositories\AuthRepository;
use App\Core\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

readonly class AuthService
{
    public function __construct(private AuthRepository $repository)
    {
    }

    public function login(LoginDTO $loginDTO): array
    {
        if (Auth::attempt(['email' => $loginDTO->email, 'password' => $loginDTO->password], true)) {
            $token = $this->repository->createToken(Auth::user(), $loginDTO->deviceName)->plainTextToken;

            return [
                'user'  => Auth::user(),
                'token' => $token,
            ];
        }

        throw ValidationException::withMessages([
            'email'    => [
                'Wrong credentials.',
            ],
            'password' => [
                'Wrong credentials.',
            ],
        ]);
    }

    public function logout(User $user, LogoutDTO $logoutDTO): bool
    {
        $user->tokens()->where('name', '=', $logoutDTO->deviceName)->delete();

        return true;
    }
}
