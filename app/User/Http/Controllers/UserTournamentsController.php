<?php

namespace App\User\Http\Controllers;

use App\Tournaments\Http\Resources\TournamentResource;
use App\User\Services\UserTournamentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

readonly class UserTournamentsController
{
    public function __construct(private UserTournamentService $userTournamentService)
    {
    }

    /**
     * Get recent tournaments for dashboard
     */
    public function recent(): AnonymousResourceCollection
    {
        $tournaments = $this->userTournamentService->getRecentTournaments();
        return TournamentResource::collection($tournaments);
    }

    /**
     * Get user's tournaments and applications
     */
    public function myTournamentsAndApplications(): JsonResponse
    {
        $user = Auth::user();
        $data = $this->userTournamentService->getUserTournamentsAndApplications($user);

        return response()->json($data);
    }

    /**
     * Get upcoming tournaments user can register for
     */
    public function upcoming(): AnonymousResourceCollection
    {
        $tournaments = $this->userTournamentService->getUpcomingTournaments();
        return TournamentResource::collection($tournaments);
    }
}
