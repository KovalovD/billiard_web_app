<?php

namespace App\User\Http\Controllers;

use App\Leagues\Http\Resources\MatchGameResource;
use App\Leagues\Http\Resources\RatingResource;
use App\User\Services\UserStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

readonly class UserStatsController
{

    public function __construct(private UserStatsService $statsService)
    {
    }

    /**
     * Get the authenticated user's ratings across all leagues
     */
    public function ratings(): AnonymousResourceCollection
    {
        $user = Auth::user();
        $ratings = $this->statsService->getUserRatings($user);

        return RatingResource::collection($ratings);
    }

    /**
     * Get the authenticated user's match history
     */
    public function matches(): AnonymousResourceCollection
    {
        $user = Auth::user();
        $matches = $this->statsService->getUserMatches($user);

        return MatchGameResource::collection($matches);
    }

    /**
     * Get overall statistics for the authenticated user
     */
    public function stats(): JsonResponse
    {
        $user = Auth::user();
        $stats = $this->statsService->getUserStats($user);

        return response()->json($stats);
    }

    /**
     * Get game type statistics for the authenticated user
     */
    public function gameTypeStats(): JsonResponse
    {
        $user = Auth::user();
        $stats = $this->statsService->getGameTypeStats($user);

        return response()->json($stats);
    }
}
