<?php

namespace App\Admin\Http\Controllers;

use App\Admin\Http\Requests\CityRequest;
use App\Admin\Http\Resources\CityResource;
use App\Core\Models\City;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminCitiesController
{
    /**
     * Get all cities
     * @admin
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = City::with('country');

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->has('country_id')) {
            $query->where('country_id', $request->get('country_id'));
        }

        $cities = $query->orderBy('name')->paginate($request->get('per_page', 50));

        return CityResource::collection($cities);
    }

    /**
     * Get a single city
     * @admin
     */
    public function show(City $city): CityResource
    {
        $city->load('country');
        return new CityResource($city);
    }

    /**
     * Create a new city
     * @admin
     */
    public function store(CityRequest $request): JsonResponse
    {
        $city = City::create($request->validated());
        $city->load('country');

        return response()->json([
            'success' => true,
            'city'    => new CityResource($city),
            'message' => 'City created successfully',
        ], 201);
    }

    /**
     * Update a city
     * @admin
     */
    public function update(CityRequest $request, City $city): JsonResponse
    {
        $city->update($request->validated());
        $city->load('country');

        return response()->json([
            'success' => true,
            'city'    => new CityResource($city),
            'message' => 'City updated successfully',
        ]);
    }

    /**
     * Delete a city
     * @admin
     */
    public function destroy(City $city): JsonResponse
    {
        // Check if city has clubs or users
        if ($city->clubs()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete city with existing clubs',
            ], 422);
        }

        if ($city->users()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete city with existing users',
            ], 422);
        }

        $city->delete();

        return response()->json([
            'success' => true,
            'message' => 'City deleted successfully',
        ]);
    }
}
