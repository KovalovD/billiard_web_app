<?php

namespace App\OfficialRatings\Http\Controllers;

use App\OfficialRatings\Http\Resources\OfficialRatingPlayerResource;
use App\OfficialRatings\Http\Resources\OfficialRatingResource;
use App\OfficialRatings\Models\OfficialRating;
use App\OfficialRatings\Services\OfficialRatingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Official Ratings
 */
readonly class OfficialRatingsController
{
    public function __construct(
        private OfficialRatingService $officialRatingService,
    ) {
    }

    /**
     * Get all official ratings
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $ratings = $this->officialRatingService->getAllRatings($request->query());

        return OfficialRatingResource::collection($ratings);
    }

    /**
     * Get official rating by id
     */
    public function show(OfficialRating $officialRating): OfficialRatingResource
    {
        $officialRating->load(['players.user', 'tournaments']);

        return new OfficialRatingResource($officialRating);
    }

    /**
     * Get rating players
     */
    public function players(OfficialRating $officialRating, Request $request): AnonymousResourceCollection
    {
        $players = $this->officialRatingService->getRatingPlayers($officialRating, $request->query());

        return OfficialRatingPlayerResource::collection($players);
    }

    /**
     * Get rating tournaments
     */
    public function tournaments(OfficialRating $officialRating): JsonResponse
    {
        $tournaments = $this->officialRatingService->getRatingTournaments($officialRating);

        return response()->json($tournaments);
    }

    /**
     * Get top players
     */
    public function topPlayers(OfficialRating $officialRating, Request $request): AnonymousResourceCollection
    {
        $limit = min($request->get('limit', 10), 50);
        $players = $officialRating->getTopPlayers($limit);

        return OfficialRatingPlayerResource::collection($players);
    }

    /**
     * Get player rating info
     */
    public function playerRating(OfficialRating $officialRating, int $userId): JsonResponse
    {
        $player = $officialRating->getPlayerRating($userId);

        if (!$player) {
            return response()->json([
                'message' => 'Player not found in this rating',
            ], 404);
        }

        return response()->json(new OfficialRatingPlayerResource($player));
    }

    /**
     * Get active ratings
     */
    public function active(): AnonymousResourceCollection
    {
        $ratings = $this->officialRatingService->getActiveRatings();

        return OfficialRatingResource::collection($ratings);
    }

    /**
     * Get rating delta for current user since date
     */
    public function playerDelta(OfficialRating $officialRating, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
        ]);

        $user = $request->user();

        $delta = $this->officialRatingService->getPlayerDeltaSinceDate(
            $officialRating,
            $user->id,
            Carbon::parse($validated['date']),
        );

        if (!$delta) {
            return response()->json([
                'message' => 'Player not found in this rating',
            ], 404);
        }

        return response()->json($delta);
    }

    public function getOneYearRating(): JsonResponse
    {
        return response()->json($this->officialRatingService->getOneYearRating());
    }
}
