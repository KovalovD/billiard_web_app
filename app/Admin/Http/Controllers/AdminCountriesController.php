<?php

namespace App\Admin\Http\Controllers;

use App\Admin\Http\Requests\CountryRequest;
use App\Admin\Http\Resources\CountryResource;
use App\Core\Models\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminCountriesController
{
    /**
     * Get all countries
     * @admin
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Country::query();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('name', 'like', "%{$search}%");
        }

        $countries = $query->orderBy('name')->paginate($request->get('per_page', 50));

        return CountryResource::collection($countries);
    }

    /**
     * Get a single country
     * @admin
     */
    public function show(Country $country): CountryResource
    {
        return new CountryResource($country);
    }

    /**
     * Create a new country
     * @admin
     */
    public function store(CountryRequest $request): JsonResponse
    {
        $country = Country::create($request->validated());

        return response()->json([
            'success' => true,
            'country' => new CountryResource($country),
            'message' => 'Country created successfully',
        ], 201);
    }

    /**
     * Update a country
     * @admin
     */
    public function update(CountryRequest $request, Country $country): JsonResponse
    {
        $country->update($request->validated());

        return response()->json([
            'success' => true,
            'country' => new CountryResource($country),
            'message' => 'Country updated successfully',
        ]);
    }

    /**
     * Delete a country
     * @admin
     */
    public function destroy(Country $country): JsonResponse
    {
        // Check if country has cities
        if ($country->cities()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete country with existing cities',
            ], 422);
        }

        $country->delete();

        return response()->json([
            'success' => true,
            'message' => 'Country deleted successfully',
        ]);
    }
}
