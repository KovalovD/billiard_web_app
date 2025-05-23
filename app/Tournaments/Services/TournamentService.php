<?php

namespace App\Tournaments\Services;

use App\Auth\DataTransferObjects\RegisterDTO;
use App\Auth\Services\AuthService;
use App\Core\Models\User;
use App\Tournaments\Models\Tournament;
use App\Tournaments\Models\TournamentPlayer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class TournamentService
{
    public function __construct(
        private readonly AuthService $authService,
    ) {
    }

    /**
     * Get all tournaments with filtering
     */
    public function getAllTournaments(array $filters = []): Collection
    {
        $query = Tournament::with(['game', 'city.country', 'club']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['game_id'])) {
            $query->where('game_id', $filters['game_id']);
        }

        if (isset($filters['city_id'])) {
            $query->where('city_id', $filters['city_id']);
        }

        if (isset($filters['year'])) {
            $query->whereYear('start_date', $filters['year']);
        }

        return $query->orderBy('end_date', 'desc')->get();
    }

    /**
     * Get upcoming tournaments
     */
    public function getUpcomingTournaments(): Collection
    {
        return Tournament::with(['game', 'city.country', 'club'])
            ->where('status', 'upcoming')
            ->orderBy('start_date')
            ->get()
        ;
    }

    /**
     * Get active tournaments
     */
    public function getActiveTournaments(): Collection
    {
        return Tournament::with(['game', 'city.country', 'club'])
            ->where('status', 'active')
            ->orderBy('start_date')
            ->get()
        ;
    }

    /**
     * Get completed tournaments
     */
    public function getCompletedTournaments(): Collection
    {
        return Tournament::with(['game', 'city.country', 'club'])
            ->where('status', 'completed')
            ->orderBy('start_date', 'desc')
            ->get()
        ;
    }

    /**
     * Create new tournament
     */
    public function createTournament(array $data): Tournament
    {
        return Tournament::create($data);
    }

    /**
     * Update tournament
     */
    public function updateTournament(Tournament $tournament, array $data): Tournament
    {
        $tournament->update($data);
        return $tournament->fresh();
    }

    /**
     * Delete tournament
     */
    public function deleteTournament(Tournament $tournament): void
    {
        $tournament->delete();
    }

    /**
     * Get tournament players
     */
    public function getTournamentPlayers(Tournament $tournament): Collection
    {
        return $tournament
            ->players()
            ->with('user')
            ->orderByRaw('position IS NULL, position ASC')
            ->get()
        ;
    }

    /**
     * Get tournament results
     */
    public function getTournamentResults(Tournament $tournament): array
    {
        $players = $tournament
            ->players()
            ->with('user')
            ->whereNotNull('position')
            ->orderBy('position')
            ->get()
        ;

        return [
            'tournament' => [
                'id'         => $tournament->id,
                'name'       => $tournament->name,
                'start_date' => $tournament->start_date?->format('Y-m-d'),
                'end_date'   => $tournament->end_date?->format('Y-m-d'),
                'prize_pool' => $tournament->prize_pool,
            ],
            'results'    => $players->map(function ($player) {
                return [
                    'position'      => $player->position,
                    'player'        => [
                        'id'   => $player->user->id,
                        'name' => $player->user->firstname.' '.$player->user->lastname,
                    ],
                    'rating_points' => $player->rating_points,
                    'prize_amount'  => $player->prize_amount,
                ];
            }),
        ];
    }

    /**
     * Add existing player to tournament
     * @throws Throwable
     */
    public function addPlayerToTournament(Tournament $tournament, int $userId): TournamentPlayer
    {
        // Check if player already exists
        $existingPlayer = $tournament->players()->where('user_id', $userId)->first();
        if ($existingPlayer) {
            throw new RuntimeException('Player is already registered for this tournament');
        }

        // Check max participants limit
        if ($tournament->max_participants && $tournament->players_count >= $tournament->max_participants) {
            throw new RuntimeException('Tournament has reached maximum participants limit');
        }

        // Check if tournament is still accepting registrations
        if (!$tournament->isRegistrationOpen()) {
            throw new RuntimeException('Registration is closed for this tournament');
        }

        return TournamentPlayer::create([
            'tournament_id' => $tournament->id,
            'user_id'       => $userId,
            'status'        => 'registered',
            'registered_at' => now(),
        ]);
    }

    /**
     * Add new player to tournament with registration
     * @throws Throwable
     */
    public function addNewPlayerToTournament(Tournament $tournament, RegisterDTO $playerData): array
    {
        return DB::transaction(function () use ($tournament, $playerData) {
            // Get or create user
            $user = $this->getOrCreateUser($playerData);

            // Try to add to tournament
            try {
                $player = $this->addPlayerToTournament($tournament, $user->id);

                return [
                    'success' => true,
                    'user'    => $user,
                    'player'  => $player,
                    'message' => 'Player added to tournament successfully',
                ];
            } catch (Throwable $e) {
                return [
                    'success' => false,
                    'user'    => $user,
                    'player'  => null,
                    'message' => $e->getMessage(),
                ];
            }
        });
    }

    /**
     * Get or create user from registration data
     */
    private function getOrCreateUser(RegisterDTO $playerData): User
    {
        // Check if user with this email already exists
        $existingUser = User::where('email', $playerData->email)->first();

        if (!$existingUser) {
            $existingUser = $this->authService->register($playerData, false)['user'];
        }

        return $existingUser;
    }

    /**
     * Remove player from tournament
     */
    public function removePlayerFromTournament(TournamentPlayer $player): void
    {
        $player->delete();
    }

    /**
     * Update tournament player
     */
    public function updateTournamentPlayer(TournamentPlayer $player, array $data): TournamentPlayer
    {
        $player->update($data);
        return $player->fresh();
    }

    /**
     * Set tournament results
     * @throws Throwable
     */
    public function setTournamentResults(Tournament $tournament, array $results): void
    {
        DB::transaction(static function () use ($tournament, $results) {
            foreach ($results as $result) {
                $player = TournamentPlayer::findOrFail($result['player_id']);

                if ($player->tournament_id !== $tournament->id) {
                    throw new RuntimeException("Player $player->id does not belong to tournament $tournament->id");
                }

                $player->update([
                    'position'      => $result['position'],
                    'rating_points' => $result['rating_points'] ?? 0,
                    'prize_amount'  => $result['prize_amount'] ?? 0,
                    'status'        => 'confirmed',
                ]);
            }

            // Update tournament status if not already completed
            if ($tournament->status !== 'completed') {
                $tournament->update(['status' => 'completed']);
            }
        });
    }

    /**
     * Change tournament status
     */
    public function changeTournamentStatus(Tournament $tournament, string $status): Tournament
    {
        $tournament->update(['status' => $status]);
        return $tournament->fresh();
    }

    /**
     * Search users by name or email
     */
    public function searchUsers(string $query): Collection
    {
        return User::where(static function ($q) use ($query) {
            $q
                ->where('firstname', 'LIKE', "%$query%")
                ->orWhere('lastname', 'LIKE', "%$query%")
                ->orWhere('email', 'LIKE', "%$query%")
            ;
        })
            ->limit(20)
            ->get()
        ;
    }
}
