<?php

namespace App\User\Http\Controllers;

use App\Core\Models\Club;
use App\Leagues\Http\Resources\ClubResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClubsController
{
    /**
     * Get list of clubs, optionally filtered by city
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Club::with(['city.country']);

        if ($request->has('city_id')) {
            $query->where('city_id', $request->city_id);
        }

        $clubs = $query->orderBy('name')->get();

        return ClubResource::collection($clubs);
    }
}
