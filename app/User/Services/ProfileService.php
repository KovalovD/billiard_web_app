<?php

namespace App\User\Services;

use App\Core\Models\User;
use App\User\Http\Requests\UpdateEquipmentRequest;
use App\User\Http\Requests\UpdatePasswordRequest;
use App\User\Http\Requests\UpdateProfileRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileService
{
    public function updateProfile(User $user, UpdateProfileRequest $request): User
    {
        $validated = $request->validated();

        // Check if email changed, if so mark it as unverified
        if ($user->email !== $validated['email']) {
            $validated['email_verified_at'] = null;
        }

        // Handle profile picture upload
        if ($request->hasFile('picture')) {
            // Delete old picture if exists
            if ($user->picture) {
                Storage::disk('public')->delete($user->picture);
            }

            $path = $request->file('picture')?->store('profile-pictures', 'public');
            $validated['picture'] = $path;
        }

        // Handle tournament picture upload
        if ($request->hasFile('tournament_picture')) {
            // Delete old tournament picture if exists
            if ($user->tournament_picture) {
                Storage::disk('public')->delete($user->tournament_picture);
            }

            $path = $request->file('tournament_picture')?->store('tournament-pictures', 'public');
            $validated['tournament_picture'] = $path;
        }

        $user->update($validated);

        return $user->refresh();
    }

    public function updateEquipment(User $user, UpdateEquipmentRequest $request): User
    {
        $validated = $request->validated();

        $user->update([
            'equipment' => $validated['equipment'] ?? [],
        ]);

        return $user->refresh();
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
        // Delete profile pictures
        if ($user->picture) {
            Storage::disk('public')->delete($user->picture);
        }

        if ($user->tournament_picture) {
            Storage::disk('public')->delete($user->tournament_picture);
        }

        // Soft delete the user
        $user->update(['is_active' => false]);
        $user->refresh();

        return true;
    }

    public function deletePicture(User $user, string $type = 'profile'): bool
    {
        if ($type === 'profile' && $user->picture) {
            Storage::disk('public')->delete($user->picture);
            $user->update(['picture' => null]);
        } elseif ($type === 'tournament' && $user->tournament_picture) {
            Storage::disk('public')->delete($user->tournament_picture);
            $user->update(['tournament_picture' => null]);
        }

        return true;
    }
}
