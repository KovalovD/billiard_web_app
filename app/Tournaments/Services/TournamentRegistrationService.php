<?php

namespace App\Tournaments\Services;

use App\Auth\DataTransferObjects\RegisterDTO;
use App\Auth\Services\AuthService;
use App\Core\Models\User;
use App\Tournaments\Models\Tournament;
use App\Tournaments\Models\TournamentPlayer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Throwable;

readonly class TournamentRegistrationService
{
    public function __construct(
        private AuthService $authService,
    ) {
    }

    /**
     * Register user and apply to tournament
     * @throws Throwable
     */
    public function registerAndApplyToTournament(Tournament $tournament, RegisterDTO $registerData): array
    {
        // Check if tournament accepts applications
        if (!$tournament->canAcceptApplications()) {
            throw new RuntimeException('This tournament is not accepting applications at this time.');
        }

        return DB::transaction(function () use ($tournament, $registerData) {
            // Check if user with this email already exists
            $existingUser = User::where('email', $registerData->email)->first();

            if ($existingUser) {
                // User exists - just create application
                return $this->handleExistingUser($tournament, $existingUser, $registerData);
            }

            // Register new user
            $registration = $this->authService->register($registerData, false);
            $user = $registration['user'];

            // Create tournament application
            $application = $this->createTournamentApplication($tournament, $user);

            return [
                'success'     => true,
                'user'        => $user,
                'application' => $application,
                'message'     => 'Successfully registered! Your tournament application is pending approval. Please check your email for login credentials.',
                'token'       => null, // Don't auto-login for tournament registration
            ];
        });
    }

    /**
     * Handle existing user trying to register for tournament
     */
    private function handleExistingUser(Tournament $tournament, User $existingUser, RegisterDTO $registerData): array
    {
        // Check if user already has an application
        $existingApplication = $tournament
            ->players()
            ->where('user_id', $existingUser->id)
            ->first()
        ;

        if ($existingApplication) {
            return [
                'success'     => false,
                'user'        => $existingUser,
                'application' => $existingApplication,
                'message'     => 'You have already applied to this tournament. Please login to check your application status.',
                'token'       => null,
            ];
        }

        // Verify password matches
        if (!Hash::check($registerData->password, $existingUser->password)) {
            throw new RuntimeException('An account with this email already exists. Please login with your existing password or use a different email.');
        }

        // Create tournament application
        $application = $this->createTournamentApplication($tournament, $existingUser);

        return [
            'success'     => true,
            'user'        => $existingUser,
            'application' => $application,
            'message'     => 'Your tournament application has been submitted and is pending approval. Please login with your existing account to track the status.',
            'token'       => null,
        ];
    }

    /**
     * Create tournament application
     */
    private function createTournamentApplication(Tournament $tournament, User $user): TournamentPlayer
    {
        return TournamentPlayer::create([
            'tournament_id' => $tournament->id,
            'user_id'       => $user->id,
            'status'        => $tournament->auto_approve_applications ? 'confirmed' : 'applied',
            'registered_at' => now(),
            'applied_at'    => now(),
            'confirmed_at'  => $tournament->auto_approve_applications ? now() : null,
        ]);
    }
}
