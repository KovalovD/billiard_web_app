<?php

namespace App\User\Services;

use App\Core\Models\User;
use App\Tournaments\Enums\TournamentPlayerStatus;
use App\Tournaments\Enums\TournamentStatus;
use App\Tournaments\Http\Resources\TournamentPlayerResource;
use App\Tournaments\Http\Resources\TournamentResource;
use App\Tournaments\Models\Tournament;
use App\Tournaments\Models\TournamentPlayer;
use Illuminate\Database\Eloquent\Collection;

class UserTournamentService
{
    /**
     * Get recent tournaments (last 5 completed/active tournaments)
     */
    public function getRecentTournaments(): Collection
    {
        return Tournament::with(['game', 'city.country', 'club'])
            ->whereIn('status', ['active', 'completed'])
            ->orderBy('start_date', 'desc')
            ->limit(5)
            ->get()
        ;
    }

    /**
     * Get upcoming tournaments (open for registration)
     */
    public function getUpcomingTournaments(): Collection
    {
        return Tournament::with(['game', 'city.country', 'club'])
            ->where('status', 'upcoming')
            ->where(function ($query) {
                $query
                    ->whereNull('application_deadline')
                    ->orWhere('application_deadline', '>', now())
                ;
            })
            ->orderBy('start_date')
            ->limit(5)
            ->get()
        ;
    }

    /**
     * Get user's tournaments and applications organized by status
     */
    public function getUserTournamentsAndApplications(User $user): array
    {
        // Get all user's tournament participations
        $userTournaments = TournamentPlayer::where('user_id', $user->id)
            ->with([
                'tournament.game',
                'tournament.city.country',
                'tournament.club',
            ])
            ->orderBy('created_at', 'desc')
            ->get()
        ;

        $result = [
            'upcoming'              => [],
            'active'                => [],
            'completed'             => [],
            'pending_applications'  => [],
            'rejected_applications' => [],
        ];

        foreach ($userTournaments as $participation) {
            $tournament = $participation->tournament;
            $tournamentData = [
                'tournament'    => new TournamentResource($tournament),
                'participation' => new TournamentPlayerResource($participation),
            ];

            // Categorize based on tournament status and user's participation status
            if ($participation->status === TournamentPlayerStatus::APPLIED) {
                $result['pending_applications'][] = $tournamentData;
            } elseif ($participation->status === TournamentPlayerStatus::REJECTED) {
                $result['rejected_applications'][] = $tournamentData;
            } elseif ($participation->status === TournamentPlayerStatus::CONFIRMED) {
                switch ($tournament->status) {
                    case TournamentStatus::UPCOMING:
                        $result['upcoming'][] = $tournamentData;
                        break;
                    case TournamentStatus::ACTIVE:
                        $result['active'][] = $tournamentData;
                        break;
                    case TournamentStatus::COMPLETED:
                        $result['completed'][] = $tournamentData;
                        break;
                }
            }
        }

        // Calculate summary statistics
        $totalParticipations = $userTournaments->where('status', 'confirmed')->count();
        $totalWins = $userTournaments->where('status', 'confirmed')->where('position', 1)->count();
        $totalTopThree = $userTournaments->where('status', 'confirmed')->where('position', '<=', 3)->count();

        return [
            'tournaments' => $result,
            'stats'       => [
                'total_tournaments'    => $totalParticipations,
                'total_wins'           => $totalWins,
                'total_top_three'      => $totalTopThree,
                'win_rate'             => $totalParticipations > 0 ? round(($totalWins / $totalParticipations) * 100,
                    1) : 0,
                'top_three_rate'       => $totalParticipations > 0 ? round(($totalTopThree / $totalParticipations) * 100,
                    1) : 0,
                'pending_applications' => count($result['pending_applications']),
            ],
        ];
    }
}
