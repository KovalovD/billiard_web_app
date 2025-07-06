<?php

namespace App\User\Http\Controllers;

use App\User\Http\Requests\UpdateEquipmentRequest;
use App\User\Http\Requests\UpdatePasswordRequest;
use App\User\Http\Requests\UpdateProfileRequest;
use App\User\Http\Resources\UserResource;
use App\User\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\UnauthorizedException;

readonly class ProfileController
{
    public function __construct(private ProfileService $profileService)
    {
    }

    /**
     * Update the authenticated user's profile
     * Using POST with _method field for Laravel method spoofing
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            throw new UnauthorizedException('User not authenticated');
        }

        $updatedUser = $this->profileService->updateProfile($user, $request);

        return response()->json(new UserResource($updatedUser));
    }

    /**
     * Update the authenticated user's equipment
     */
    public function updateEquipment(UpdateEquipmentRequest $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            throw new UnauthorizedException('User not authenticated');
        }

        $updatedUser = $this->profileService->updateEquipment($user, $request);

        return response()->json(new UserResource($updatedUser));
    }

    /**
     * Update the authenticated user's password
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            throw new UnauthorizedException('User not authenticated');
        }

        $this->profileService->updatePassword($user, $request);

        return response()->json(['message' => 'Password updated successfully']);
    }

    /**
     * Delete the authenticated user's account
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = Auth::user();

        if (!$user) {
            throw new UnauthorizedException('User not authenticated');
        }

        $this->profileService->deleteAccount($user);

        // Logout the user
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Account deleted successfully']);
    }

    /**
     * Delete profile picture
     */
    public function deletePicture(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            throw new UnauthorizedException('User not authenticated');
        }

        $type = $request->get('type', 'profile');

        $this->profileService->deletePicture($user, $type);

        return response()->json(['message' => 'Picture deleted successfully']);
    }
}
