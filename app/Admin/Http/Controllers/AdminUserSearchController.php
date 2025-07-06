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

        $users = User::where(static function ($q) use ($query) {
            $q
                ->where('firstname', 'LIKE', "%$query%")
                ->orWhere('lastname', 'LIKE', "%$query%")
                ->orWhere('email', 'LIKE', "%$query%")
                ->orWhereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", ["%$query%"])
                ->orWhereRaw("CONCAT(lastname, ' ', firstname) LIKE ?", ["%$query%"])
            ;
        })
            ->where('is_active', true)
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->limit($limit)
            ->get()
        ;

        return UserResource::collection($users);
    }
}
