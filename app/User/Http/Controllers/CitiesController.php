<?php

namespace App\User\Http\Controllers;

use App\Core\Models\City;
use App\User\Http\Resources\CityResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CitiesController
{
    /**
     * Get list of cities
     */
    public function index(): AnonymousResourceCollection
    {
        $cities = City::with('country')->orderBy('name')->get();

        return CityResource::collection($cities);
    }
}
