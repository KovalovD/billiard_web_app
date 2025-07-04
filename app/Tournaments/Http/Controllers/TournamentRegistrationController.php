<?php

namespace App\Tournaments\Http\Controllers;

use App\Auth\DataTransferObjects\RegisterDTO;
use App\Core\Models\User;
use App\Tournaments\Http\Requests\TournamentRegistrationRequest;
use App\Tournaments\Models\Tournament;
use App\Tournaments\Services\TournamentRegistrationService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * @group Tournament Registration
 */
readonly class TournamentRegistrationController
{
    public function __construct(
        private TournamentRegistrationService $registrationService,
    ) {
    }

    /**
     * Register and apply to tournament
     * Public endpoint - no authentication required
     */
    public function register(TournamentRegistrationRequest $request, Tournament $tournament): JsonResponse
    {
        try {
            $result = $this->registrationService->registerAndApplyToTournament(
                $tournament,
                RegisterDTO::fromRequest($request),
            );

            return response()->json([
                'success'     => true,
                'user'        => $result['user'],
                'application' => $result['application'],
                'message'     => $result['message'],
                'token'       => $result['token'] ?? null,
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Check if email is already registered
     * Public endpoint - no authentication required
     */
    public function checkEmail(Tournament $tournament, string $email): JsonResponse
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'exists'    => false,
                'can_apply' => true,
            ]);
        }

        // Check if user already applied
        $hasApplication = $tournament
            ->players()
            ->where('user_id', $user->id)
            ->exists()
        ;

        return response()->json([
            'exists'          => true,
            'can_apply'       => !$hasApplication,
            'has_application' => $hasApplication,
            'message'         => $hasApplication
                ? 'You have already applied to this tournament. Please login to check your application status.'
                : 'An account with this email already exists. Please login to apply to this tournament.',
        ]);
    }
}
