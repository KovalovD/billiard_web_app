<?php

namespace App\Auth\Repositories;

use App\Core\Models\User;
use Laravel\Sanctum\NewAccessToken;

class AuthRepository
{
    public function createToken(User $user, string $deviceName): NewAccessToken
    {
        $this->invalidateToken($user, $deviceName);

        return $user->createToken($deviceName, ['*'], now()->addDays(2));
    }

    public function invalidateToken(User $user, string $deviceName): void
    {
        $user->tokens()->where('name', $deviceName)->delete();
    }
}
