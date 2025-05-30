<?php

namespace App\Tournaments\Http\Controllers;

use App\Tournaments\Http\Resources\TournamentPlayerResource;
use App\Tournaments\Models\Tournament;
use App\Tournaments\Models\TournamentPlayer;
use App\Tournaments\Services\TournamentApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use RuntimeException;

/**
 * @group Admin Tournament Applications
 */
readonly class AdminTournamentApplicationsController
{
    public function __construct(
        private TournamentApplicationService $applicationService,
    ) {
    }

    /**
     * Get pending applications for tournament
     * @admin
     */
    public function pendingApplications(Tournament $tournament): AnonymousResourceCollection
    {
        $applications = $this->applicationService->getPendingApplications($tournament);
        return TournamentPlayerResource::collection($applications);
    }

    /**
     * Confirm application
     * @admin
     */
    public function confirmApplication(Tournament $tournament, TournamentPlayer $application): JsonResponse
    {
        try {
            $confirmedApplication = $this->applicationService->confirmApplication($tournament, $application);

            return response()->json([
                'success'     => true,
                'application' => new TournamentPlayerResource($confirmedApplication),
                'message'     => 'Application confirmed successfully.',
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Reject application
     * @admin
     */
    public function rejectApplication(Tournament $tournament, TournamentPlayer $application): JsonResponse
    {
        try {
            $rejectedApplication = $this->applicationService->rejectApplication(
                $tournament,
                $application,
            );

            return response()->json([
                'success'     => true,
                'application' => new TournamentPlayerResource($rejectedApplication),
                'message'     => 'Application rejected.',
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Bulk confirm applications
     * @admin
     */
    public function bulkConfirmApplications(Request $request, Tournament $tournament): JsonResponse
    {
        $validated = $request->validate([
            'application_ids'   => ['required', 'array', 'min:1'],
            'application_ids.*' => ['integer', 'exists:tournament_players,id'],
        ]);

        try {
            $confirmedCount = $this->applicationService->bulkConfirmApplications(
                $tournament,
                $validated['application_ids'],
            );

            return response()->json([
                'success'         => true,
                'confirmed_count' => $confirmedCount,
                'message'         => "Successfully confirmed $confirmedCount applications.",
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Bulk reject applications
     * @admin
     */
    public function bulkRejectApplications(Request $request, Tournament $tournament): JsonResponse
    {
        $validated = $request->validate([
            'application_ids'   => ['required', 'array', 'min:1'],
            'application_ids.*' => ['integer', 'exists:tournament_players,id'],
        ]);

        try {
            $rejectedCount = $this->applicationService->bulkRejectApplications(
                $tournament,
                $validated['application_ids'],
            );

            return response()->json([
                'success'        => true,
                'rejected_count' => $rejectedCount,
                'message'        => "Successfully rejected $rejectedCount applications.",
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get all applications for tournament (all statuses)
     * @admin
     */
    public function allApplications(Tournament $tournament): AnonymousResourceCollection
    {
        $applications = $tournament
            ->players()
            ->with('user')
            ->orderByRaw("CASE
                WHEN status = 'applied' THEN 1
                WHEN status = 'confirmed' THEN 2
                WHEN status = 'rejected' THEN 3
                ELSE 4
            END")
            ->orderBy('applied_at')
            ->get()
        ;

        return TournamentPlayerResource::collection($applications);
    }
}
