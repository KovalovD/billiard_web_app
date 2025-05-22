<?php

namespace App\OfficialRatings\Http\Controllers;

use App\OfficialRatings\Http\Requests\CreateOfficialRatingRequest;
use App\OfficialRatings\Http\Requests\UpdateOfficialRatingRequest;
use App\OfficialRatings\Http\Resources\OfficialRatingResource;
use App\OfficialRatings\Models\OfficialRating;
use App\OfficialRatings\Services\OfficialRatingService;
use App\Tournaments\Models\Tournament;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * @group Admin Official Ratings
 */
readonly class AdminOfficialRatingsController
{
    public function __construct(
        private OfficialRatingService $officialRatingService,
    ) {
    }

    /**
     * Create official rating
     * @admin
     */
    public function store(CreateOfficialRatingRequest $request): OfficialRatingResource
    {
        $rating = $this->officialRatingService->createRating($request->validated());

        return new OfficialRatingResource($rating);
    }

    /**
     * Update official rating
     * @admin
     */
    public function update(UpdateOfficialRatingRequest $request, OfficialRating $officialRating): OfficialRatingResource
    {
        $rating = $this->officialRatingService->updateRating($officialRating, $request->validated());

        return new OfficialRatingResource($rating);
    }

    /**
     * Delete official rating
     * @admin
     */
    public function destroy(OfficialRating $officialRating): JsonResponse
    {
        $this->officialRatingService->deleteRating($officialRating);

        return response()->json(['message' => 'Official rating deleted successfully']);
    }

    /**
     * Add tournament to rating
     * @admin
     */
    public function addTournament(Request $request, OfficialRating $officialRating): JsonResponse
    {
        $validated = $request->validate([
            'tournament_id'      => 'required|integer|exists:tournaments,id',
            'rating_coefficient' => 'numeric|min:0.1|max:5.0',
            'is_counting'        => 'boolean',
        ]);

        try {
            $this->officialRatingService->addTournamentToRating(
                $officialRating,
                $validated['tournament_id'],
                $validated['rating_coefficient'] ?? 1.0,
                $validated['is_counting'] ?? true,
            );

            return response()->json(['message' => 'Tournament added to rating successfully']);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove tournament from rating
     * @admin
     */
    public function removeTournament(OfficialRating $officialRating, Tournament $tournament): JsonResponse
    {
        try {
            $this->officialRatingService->removeTournamentFromRating($officialRating, $tournament);

            return response()->json(['message' => 'Tournament removed from rating successfully']);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Add player to rating
     * @admin
     */
    public function addPlayer(Request $request, OfficialRating $officialRating): JsonResponse
    {
        $validated = $request->validate([
            'user_id'        => 'required|integer|exists:users,id',
            'initial_rating' => 'integer|min:0',
        ]);

        try {
            $player = $this->officialRatingService->addPlayerToRating(
                $officialRating,
                $validated['user_id'],
                $validated['initial_rating'] ?? $officialRating->initial_rating,
            );

            return response()->json([
                'message' => 'Player added to rating successfully',
                'player'  => $player,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove player from rating
     * @admin
     */
    public function removePlayer(OfficialRating $officialRating, int $userId): JsonResponse
    {
        try {
            $this->officialRatingService->removePlayerFromRating($officialRating, $userId);

            return response()->json(['message' => 'Player removed from rating successfully']);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Recalculate rating positions
     * @admin
     */
    public function recalculatePositions(OfficialRating $officialRating): JsonResponse
    {
        try {
            $this->officialRatingService->recalculatePositions($officialRating);

            return response()->json(['message' => 'Rating positions recalculated successfully']);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update rating from tournament results
     * @admin
     */
    public function updateFromTournament(OfficialRating $officialRating, Tournament $tournament): JsonResponse
    {
        try {
            $updated = $this->officialRatingService->updateRatingFromTournament($officialRating, $tournament);

            return response()->json([
                'message'         => "Rating updated successfully. $updated players affected.",
                'players_updated' => $updated,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
