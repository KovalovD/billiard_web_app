<?php

namespace App\Admin\Http\Controllers;

use App\Admin\Http\Requests\AdminPlayerRequest;
use App\Core\Models\User;
use App\User\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AdminEditPlayersController
{
    /**
     * Get all players with filters
     * @admin
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = User::with(['homeCity.country', 'homeClub']);

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q
                    ->where('firstname', 'like', "%{$search}%")
                    ->orWhere('lastname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", ["%{$search}%"])
                ;
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('is_admin')) {
            $query->where('is_admin', $request->boolean('is_admin'));
        }

        if ($request->has('city_id')) {
            $query->where('home_city_id', $request->get('city_id'));
        }

        if ($request->has('club_id')) {
            $query->where('home_club_id', $request->get('club_id'));
        }

        $players = $query
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->paginate($request->get('per_page', 50))
        ;

        return UserResource::collection($players);
    }

    /**
     * Get a single player
     * @admin
     */
    public function show(User $player): UserResource
    {
        $player->load(['homeCity.country', 'homeClub']);
        return new UserResource($player);
    }

    /**
     * Update a player
     * @admin
     */
    public function update(AdminPlayerRequest $request, User $player): JsonResponse
    {
        $data = $request->validated();

        // Handle password update
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        // Handle picture upload
        if ($request->hasFile('picture')) {
            // Delete old picture
            if ($player->picture) {
                Storage::disk('r2')->delete($player->picture);
            }
            $data['picture'] = $request->file('picture')?->store('users/pictures', 'r2');
        }

        // Handle tournament picture upload
        if ($request->hasFile('tournament_picture')) {
            // Delete old tournament picture
            if ($player->tournament_picture) {
                Storage::disk('r2')->delete($player->tournament_picture);
            }
            $data['tournament_picture'] = $request->file('tournament_picture')?->store('users/tournament-pictures',
                'r2');
        }

        // Handle email verification
        if (isset($data['email_verified']) && $data['email_verified'] && !$player->email_verified_at) {
            $data['email_verified_at'] = now();
        } elseif (isset($data['email_verified']) && !$data['email_verified']) {
            $data['email_verified_at'] = null;
        }
        unset($data['email_verified']);

        $player->update($data);
        $player->load(['homeCity.country', 'homeClub']);

        return response()->json([
            'success' => true,
            'player'  => new UserResource($player),
            'message' => 'Player updated successfully',
        ]);
    }

    /**
     * Update player equipment
     * @admin
     */
    public function updateEquipment(Request $request, User $player): JsonResponse
    {
        $validated = $request->validate([
            'equipment'               => 'array',
            'equipment.*.type'        => 'required|in:cue,case,chalk,glove,other',
            'equipment.*.brand'       => 'required|string|max:255',
            'equipment.*.model'       => 'nullable|string|max:255',
            'equipment.*.description' => 'nullable|string|max:500',
        ]);

        $player->update(['equipment' => $validated['equipment'] ?? []]);

        return response()->json([
            'success' => true,
            'player'  => new UserResource($player),
            'message' => 'Equipment updated successfully',
        ]);
    }

    /**
     * Delete player picture
     * @admin
     */
    public function deletePicture(Request $request, User $player): JsonResponse
    {
        $type = $request->get('type', 'profile');

        if ($type === 'profile' && $player->picture) {
            Storage::disk('r2')->delete($player->picture);
            $player->update(['picture' => null]);
        } elseif ($type === 'tournament' && $player->tournament_picture) {
            Storage::disk('r2')->delete($player->tournament_picture);
            $player->update(['tournament_picture' => null]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Picture deleted successfully',
        ]);
    }

    /**
     * Toggle player active status
     * @admin
     */
    public function toggleActive(User $player): JsonResponse
    {
        $player->update(['is_active' => !$player->is_active]);

        return response()->json([
            'success'   => true,
            'is_active' => $player->is_active,
            'message'   => $player->is_active
                ? 'Player activated successfully'
                : 'Player deactivated successfully',
        ]);
    }

    /**
     * Toggle player admin status
     * @admin
     */
    public function toggleAdmin(User $player): JsonResponse
    {
        $player->update(['is_admin' => !$player->is_admin]);

        return response()->json([
            'success'  => true,
            'is_admin' => $player->is_admin,
            'message'  => $player->is_admin
                ? 'Admin privileges granted'
                : 'Admin privileges revoked',
        ]);
    }

    /**
     * Reset player password
     * @admin
     */
    public function resetPassword(Request $request, User $player): JsonResponse
    {
        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $player->update(['password' => Hash::make($validated['password'])]);

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully',
        ]);
    }
}
