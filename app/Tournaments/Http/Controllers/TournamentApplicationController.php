<?php

namespace App\Tournaments\Http\Controllers;

use App\Tournaments\Http\Resources\TournamentPlayerResource;
use App\Tournaments\Models\Tournament;
use App\Tournaments\Services\TournamentApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * @group Tournament Applications
 */
readonly class TournamentApplicationController
{
    public function __construct(
        private TournamentApplicationService $applicationService,
    ) {
    }

    /**
     * Apply to tournament
     * @authenticated
     */
    public function apply(Tournament $tournament): JsonResponse
    {
        try {
            $application = $this->applicationService->applyToTournament(
                $tournament,
                Auth::user(),
            );

            return response()->json([
                'success'     => true,
                'application' => new TournamentPlayerResource($application),
                'message'     => 'Application submitted successfully. Please wait for admin confirmation.',
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cancel application
     * @authenticated
     */
    public function cancel(Tournament $tournament): JsonResponse
    {
        try {
            $this->applicationService->cancelApplication($tournament, Auth::user());

            return response()->json([
                'success' => true,
                'message' => 'Application cancelled successfully.',
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get user's application status for tournament
     * @authenticated
     */
    public function status(Tournament $tournament): JsonResponse
    {
        $application = $this->applicationService->getUserApplication($tournament, Auth::user());

        if (!$application) {
            return response()->json([
                'has_application' => false,
                'can_apply'       => $tournament->canAcceptApplications(),
            ]);
        }

        return response()->json([
            'has_application' => true,
            'application'     => new TournamentPlayerResource($application),
            'can_apply'       => false,
        ]);
    }
}
