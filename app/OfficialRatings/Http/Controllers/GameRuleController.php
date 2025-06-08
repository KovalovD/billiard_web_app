<?php

namespace App\OfficialRatings\Http\Controllers;

use App\OfficialRatings\Models\GameRule;
use App\OfficialRatings\Models\OfficialRating;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class GameRuleController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    public function index(): JsonResponse
    {
        $rules = GameRule::with('officialRating')->get();
        return response()->json($rules);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'official_rating_id' => 'required|exists:official_ratings,id',
            'rules'              => 'required|string',
        ]);

        // Check if rule already exists
        $existingRule = GameRule::where('official_rating_id', $validated['official_rating_id'])->first();
        if ($existingRule) {
            return response()->json(['message' => 'Rule already exists for this rating'], 422);
        }

        $rule = GameRule::create($validated);
        return response()->json($rule, 201);
    }

    public function show(GameRule $gameRule): JsonResponse
    {
        return response()->json($gameRule->load('officialRating'));
    }

    public function update(Request $request, GameRule $gameRule): JsonResponse
    {
        $validated = $request->validate([
            'rules' => 'required|string',
        ]);

        $gameRule->update($validated);
        return response()->json($gameRule);
    }

    public function destroy(GameRule $gameRule): JsonResponse
    {
        $gameRule->delete();
        return response()->json(null, 204);
    }

    public function getByRating(OfficialRating $officialRating): JsonResponse
    {
        $rule = GameRule::where('official_rating_id', $officialRating->id)->first();
        return response()->json($rule);
    }
}
