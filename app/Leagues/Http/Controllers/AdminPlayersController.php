<?php

namespace App\Leagues\Http\Controllers;

use App\Leagues\Http\Resources\RatingResource;
use App\Leagues\Models\League;
use App\Leagues\Models\Rating;
use App\Leagues\Services\RatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class AdminPlayersController
{
    public function __construct(private RatingService $ratingService)
    {
    }

    /**
     * Get all pending (unconfirmed) players for a league
     * @admin
     */
    public function pendingPlayers(League $league): AnonymousResourceCollection
    {
        $pendingPlayers = Rating::where('league_id', $league->id)
            ->where('is_active', true)
            ->where('is_confirmed', false)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get()
        ;

        return RatingResource::collection($pendingPlayers);
    }

    /**
     * Confirm a player in the league
     * @admin
     */
    public function confirmPlayer(League $league, Rating $rating): JsonResponse
    {
        if ($rating->league_id !== $league->id) {
            return response()->json([
                'message' => 'Rating does not belong to the specified league.',
            ], 400);
        }

        $rating->update(['is_confirmed' => true]);

        // Rearrange positions after confirming a player
        $this->ratingService->rearrangePositions($league->id);

        return response()->json([
            'message' => 'Player confirmed successfully.',
            'rating'  => new RatingResource($rating->fresh()),
        ]);
    }

    /**
     * Reject a player from the league
     * @admin
     */
    public function rejectPlayer(League $league, Rating $rating): JsonResponse
    {
        if ($rating->league_id !== $league->id) {
            return response()->json([
                'message' => 'Rating does not belong to the specified league.',
            ], 400);
        }

        // Set is_active to false when rejecting a player
        $rating->update([
            'is_active'    => false,
            'is_confirmed' => false,
        ]);

        // Rearrange positions after rejecting a player
        $this->ratingService->rearrangePositions($league->id);

        return response()->json([
            'message' => 'Player rejected successfully.',
        ]);
    }

    /**
     * Bulk confirm multiple players
     * @admin
     */
    public function bulkConfirmPlayers(Request $request, League $league): JsonResponse
    {
        $request->validate([
            'rating_ids'   => 'required|array',
            'rating_ids.*' => 'integer|exists:ratings,id',
        ]);

        $ratings = Rating::whereIn('id', $request->rating_ids)
            ->where('league_id', $league->id)
            ->where('is_active', true)
            ->where('is_confirmed', false)
            ->get()
        ;

        foreach ($ratings as $rating) {
            $rating->update(['is_confirmed' => true]);
        }

        // Rearrange positions after confirming players
        $this->ratingService->rearrangePositions($league->id);

        return response()->json([
            'message' => count($ratings).' players confirmed successfully.',
        ]);
    }

    /**
     * Deactivate a confirmed player
     * @admin
     */
    public function deactivatePlayer(League $league, Rating $rating): JsonResponse
    {
        if ($rating->league_id !== $league->id) {
            return response()->json([
                'message' => 'Rating does not belong to the specified league.',
            ], 400);
        }

        // Verify the player is currently confirmed and active
        if (!$rating->is_confirmed || !$rating->is_active) {
            return response()->json([
                'message' => 'Player is not currently confirmed or active.',
            ], 400);
        }

        // Update player status to inactive
        $rating->update([
            'is_active'    => false,
            'is_confirmed' => false,
        ]);

        // Rearrange positions after deactivating a player
        $this->ratingService->rearrangePositions($league->id);

        return response()->json([
            'message' => 'Player deactivated successfully.',
        ]);
    }

    /**
     * Get all confirmed players for a league
     * @admin
     */
    public function confirmedPlayers(League $league): AnonymousResourceCollection
    {
        $confirmedPlayers = Rating::where('league_id', $league->id)
            ->where('is_active', true)
            ->where('is_confirmed', true)
            ->with('user')
            ->orderBy('position', 'asc')
            ->get()
        ;

        return RatingResource::collection($confirmedPlayers);
    }

    /**
     * Bulk deactivate multiple players
     * @admin
     */
    public function bulkDeactivatePlayers(Request $request, League $league): JsonResponse
    {
        $request->validate([
            'rating_ids'   => 'required|array',
            'rating_ids.*' => 'integer|exists:ratings,id',
        ]);

        $ratings = Rating::whereIn('id', $request->rating_ids)
            ->where('league_id', $league->id)
            ->where('is_active', true)
            ->where('is_confirmed', true)
            ->get()
        ;

        foreach ($ratings as $rating) {
            $rating->update([
                'is_active'    => false,
                'is_confirmed' => false,
            ]);
        }

        // Rearrange positions after deactivating players
        $this->ratingService->rearrangePositions($league->id);

        return response()->json([
            'message' => count($ratings).' players deactivated successfully.',
        ]);
    }
}
