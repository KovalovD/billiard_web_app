<?php

namespace App\User\Services;

use App\Core\Models\User;
use App\User\Http\Requests\UpdatePasswordRequest;
use App\User\Http\Requests\UpdateProfileRequest;
use Illuminate\Support\Facades\Hash;

class ProfileService
{
    public function updateProfile(User $user, UpdateProfileRequest $request): User
    {
        $validated = $request->validated();

        // Check if email changed, if so mark it as unverified
        if ($user->email !== $validated['email']) {
            $validated['email_verified_at'] = null;
        }

        $user->update($validated);

        return $user->fresh();
    }

    public function updatePassword(User $user, UpdatePasswordRequest $request): bool
    {
        $validated = $request->validated();

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return true;
    }

    public function deleteAccount(User $user): bool
    {
        // Soft delete the user
        $user->delete();

        return true;
    }
}
