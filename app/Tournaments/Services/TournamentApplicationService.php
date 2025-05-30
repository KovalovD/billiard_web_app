<?php

namespace App\Tournaments\Services;

use App\Core\Models\User;
use App\Tournaments\Models\Tournament;
use App\Tournaments\Models\TournamentPlayer;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

class TournamentApplicationService
{
    /**
     * Apply to tournament
     */
    public function applyToTournament(Tournament $tournament, User $user): TournamentPlayer
    {
        // Check if tournament accepts applications
        if (!$tournament->canAcceptApplications()) {
            throw new RuntimeException('This tournament is not accepting applications at this time.');
        }

        // Check if user already has an application
        $existingApplication = $this->getUserApplication($tournament, $user);
        if ($existingApplication) {
            throw new RuntimeException('You have already applied to this tournament.');
        }

        // Check max participants limit
        if ($tournament->max_participants && $tournament->confirmed_players_count >= $tournament->max_participants) {
            throw new RuntimeException('Tournament has reached maximum participants limit.');
        }

        // Create application
        return TournamentPlayer::create([
            'tournament_id' => $tournament->id,
            'user_id'       => $user->id,
            'status'        => $tournament->auto_approve_applications ? 'confirmed' : 'applied',
            'registered_at' => now(),
            'applied_at'    => now(),
            'confirmed_at'  => $tournament->auto_approve_applications ? now() : null,
        ]);
    }

    /**
     * Get user's application for tournament
     */
    public function getUserApplication(Tournament $tournament, User $user): ?TournamentPlayer
    {
        return $tournament
            ->players()
            ->where('user_id', $user->id)
            ->first()
        ;
    }

    /**
     * Cancel application
     */
    public function cancelApplication(Tournament $tournament, User $user): void
    {
        $application = $this->getUserApplication($tournament, $user);

        if (!$application) {
            throw new RuntimeException('No application found for this tournament.');
        }

        if ($application->status === 'confirmed') {
            throw new RuntimeException('Cannot cancel confirmed application. Please contact administrator.');
        }

        $application->delete();
    }

    /**
     * Get pending applications for tournament
     */
    public function getPendingApplications(Tournament $tournament): Collection
    {
        return $tournament
            ->players()
            ->where('status', 'applied')
            ->with('user')
            ->orderBy('applied_at')
            ->get()
        ;
    }

    /**
     * Bulk confirm applications
     */
    public function bulkConfirmApplications(Tournament $tournament, array $applicationIds): int
    {
        $applications = $tournament
            ->players()
            ->whereIn('id', $applicationIds)
            ->whereIn('status', ['applied', 'rejected'])
            ->get()
        ;

        $confirmedCount = 0;
        foreach ($applications as $application) {
            try {
                $this->confirmApplication($tournament, $application);
                $confirmedCount++;
            } catch (RuntimeException) {
                // Skip applications that can't be confirmed (e.g., max participants reached)
                continue;
            }
        }

        return $confirmedCount;
    }

    /**
     * Confirm application
     */
    public function confirmApplication(Tournament $tournament, TournamentPlayer $application): TournamentPlayer
    {
        if ($application->tournament_id !== $tournament->id) {
            throw new RuntimeException('Application does not belong to this tournament.');
        }

        // Check max participants limit
        if ($tournament->max_participants && $tournament->confirmed_players_count >= $tournament->max_participants) {
            throw new RuntimeException('Tournament has reached maximum participants limit.');
        }

        $application->update([
            'status'       => 'confirmed',
            'confirmed_at' => now(),
        ]);

        return $application->fresh();
    }

    /**
     * Bulk reject applications
     */
    public function bulkRejectApplications(Tournament $tournament, array $applicationIds): int
    {
        $applications = $tournament
            ->players()
            ->whereIn('id', $applicationIds)
            ->where('status', 'applied')
            ->get()
        ;

        $rejectedCount = 0;
        foreach ($applications as $application) {
            try {
                $this->rejectApplication($tournament, $application);
                $rejectedCount++;
            } catch (RuntimeException) {
                continue;
            }
        }

        return $rejectedCount;
    }

    /**
     * Reject application
     */
    public function rejectApplication(Tournament $tournament, TournamentPlayer $application): TournamentPlayer
    {
        if ($application->tournament_id !== $tournament->id) {
            throw new RuntimeException('Application does not belong to this tournament.');
        }

        if ($application->status !== 'applied') {
            throw new RuntimeException('Only pending applications can be rejected.');
        }

        $application->update([
            'status'      => 'rejected',
            'rejected_at' => now(),
        ]);

        return $application->fresh();
    }
}
