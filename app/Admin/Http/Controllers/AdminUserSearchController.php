<?php

namespace App\Admin\Http\Controllers;

use App\Core\Models\User;
use App\User\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Admin User Search
 */
class AdminUserSearchController
{
    /**
     * Search users by name or email
     * @admin
     */
    public function searchUsers(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'query' => 'required|string|min:2',
            'limit' => 'integer|min:1|max:50',
        ]);

        $query = $validated['query'];
        $limit = $validated['limit'] ?? 20;

        $users = User::searchUser($query, $limit);

        return UserResource::collection($users);
    }
}
