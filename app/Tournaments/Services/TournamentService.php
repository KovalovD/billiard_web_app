<?php
// app/Tournaments/Services/TournamentService.php

namespace App\Tournaments\Services;

use App\Auth\DataTransferObjects\RegisterDTO;
use App\Auth\Services\AuthService;
use App\Core\Models\User;
use App\OfficialRatings\Services\OfficialRatingService;
use App\Tournaments\Enums\MatchStage;
use App\Tournaments\Enums\MatchStatus;
use App\Tournaments\Enums\TournamentStage;
use App\Tournaments\Enums\TournamentStatus;
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
        private readonly OfficialRatingService $officialRatingService,
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

        if (isset($filters['stage'])) {
            $query->where('stage', $filters['stage']);
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

        if (isset($filters['tournament_type'])) {
            $query->where('tournament_type', $filters['tournament_type']);
        }

        return $query->orderBy('end_date', 'desc')->get();
    }

    /**
     * Get upcoming tournaments
     */
    public function getUpcomingTournaments(): Collection
    {
        return Tournament::with(['game', 'city.country', 'club'])
            ->where('status', TournamentStatus::UPCOMING)
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
            ->where('status', TournamentStatus::ACTIVE)
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
            ->where('status', TournamentStatus::COMPLETED)
            ->orderBy('start_date', 'desc')
            ->get()
        ;
    }

    /**
     * Create new tournament
     * @throws Throwable
     */
    public function createTournament(array $data): Tournament
    {
        return DB::transaction(function () use ($data) {
            // Set default values for optional fields
            $data['status'] = $data['status'] ?? TournamentStatus::UPCOMING;
            $data['stage'] = $data['stage'] ?? TournamentStage::REGISTRATION;
            $data['seeding_method'] = $data['seeding_method'] ?? 'random';
            $data['races_to'] = $data['races_to'] ?? 7;
            $data['requires_application'] = $data['requires_application'] ?? true;
            $data['auto_approve_applications'] = $data['auto_approve_applications'] ?? false;
            $data['has_third_place_match'] = $data['has_third_place_match'] ?? false;

            // Create tournament
            $tournament = Tournament::create([
                'name'                      => $data['name'],
                'status'                    => $data['status'],
                'stage'                     => $data['stage'],
                'regulation'                => $data['regulation'] ?? null,
                'details'                   => $data['details'] ?? null,
                'game_id'                   => $data['game_id'],
                'city_id'                   => $data['city_id'] ?? null,
                'club_id'                   => $data['club_id'] ?? null,
                'start_date'                => $data['start_date'],
                'end_date'                  => $data['end_date'],
                'application_deadline' => $data['application_deadline'] ?? null,
                'max_participants'          => $data['max_participants'] ?? null,
                'entry_fee'                 => $data['entry_fee'] ?? 0,
                'prize_pool'                => $data['prize_pool'] ?? 0,
                'prize_distribution'        => $data['prize_distribution'] ?? null,
                'place_prizes'              => $data['place_prizes'] ?? null,
                'place_bonuses'             => $data['place_bonuses'] ?? null,
                'place_rating_points'       => $data['place_rating_points'] ?? null,
                'organizer'                 => $data['organizer'] ?? null,
                'format'                    => $data['format'] ?? null,
                'tournament_type'           => $data['tournament_type'],
                'group_size_min'            => $data['group_size_min'] ?? null,
                'group_size_max'            => $data['group_size_max'] ?? null,
                'playoff_players_per_group' => $data['playoff_players_per_group'] ?? null,
                'races_to'                  => $data['races_to'],
                'has_third_place_match'     => $data['has_third_place_match'],
                'seeding_method'            => $data['seeding_method'],
                'requires_application'      => $data['requires_application'],
                'auto_approve_applications' => $data['auto_approve_applications'],
            ]);

            // If official rating is selected, associate it with the tournament
            if (!empty($data['official_rating_id'])) {
                $ratingCoefficient = $data['rating_coefficient'] ?? 1.0;
                $rating = $this->officialRatingService
                    ->getAllRatings()
                    ->where('id', $data['official_rating_id'])
                    ->first()
                ;

                if ($rating) {
                    $this->officialRatingService->addTournamentToRating(
                        $rating,
                        $tournament->id,
                        $ratingCoefficient,
                    );
                }
            }

            return $tournament;
        });
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
            ->with('user', 'tournament.officialRatings')
            ->orderByRaw("CASE
               WHEN status = 'confirmed' AND position IS NOT NULL THEN 1
               WHEN status = 'confirmed' AND position IS NULL THEN 2
               WHEN status = 'applied' THEN 3
               WHEN status = 'rejected' THEN 4
               ELSE 5
           END")
            ->orderBy('seed_number')
            ->orderBy('position')
            ->orderBy('applied_at')
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
                'start_date' => $tournament->start_date?->format('Y-m-d H:i:s'),
                'end_date'   => $tournament->end_date?->format('Y-m-d H:i:s'),
                'prize_pool' => $tournament->prize_pool,
                'type'       => $tournament->tournament_type,
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
                    'bonus_amount'  => $player->bonus_amount,
                    'achievement_amount' => $player->achievement_amount,
                    'total_amount'  => $player->prize_amount + $player->bonus_amount + $player->achievement_amount,
                ];
            }),
        ];
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
                    'user'   => $user,
                    'player' => $player,
                    'message' => 'Player added to tournament successfully',
                ];
            } catch (Throwable $e) {
                return [
                    'success' => false,
                    'user'   => $user,
                    'player' => null,
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

        // Check max participants limit (confirmed players only)
        if ($tournament->max_participants && $tournament->confirmed_players_count >= $tournament->max_participants) {
            throw new RuntimeException('Tournament has reached maximum participants limit');
        }

        // Check if tournament accepts applications
        if ($tournament->stage !== TournamentStage::REGISTRATION) {
            throw new RuntimeException('Tournament is not in registration phase');
        }

        // Determine initial status based on tournament settings
        $status = $tournament->auto_approve_applications ? 'confirmed' : 'applied';
        $confirmedAt = $tournament->auto_approve_applications ? now() : null;

        return TournamentPlayer::create([
            'tournament_id' => $tournament->id,
            'user_id'       => $userId,
            'status'        => $status,
            'registered_at' => now(),
            'applied_at'    => now(),
            'confirmed_at'  => $confirmedAt,
            'prize_amount'  => 0,
            'bonus_amount'  => 0,
            'achievement_amount' => 0,
            'rating_points' => 0,
        ]);
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
     * Update player seeding
     */
    public function updatePlayerSeeding(TournamentPlayer $player, int $seedNumber): TournamentPlayer
    {
        $player->update(['seed_number' => $seedNumber]);
        return $player->fresh();
    }

    /**
     * Assign player to group
     */
    public function assignPlayerToGroup(TournamentPlayer $player, string $groupCode): TournamentPlayer
    {
        $player->update(['group_code' => $groupCode]);
        return $player->fresh();
    }

    /**
     * Set tournament results
     * @throws Throwable
     */
    public function setTournamentResults(Tournament $tournament, array $results): void
    {
        DB::transaction(function () use ($tournament, $results) {
            foreach ($results as $result) {
                $player = TournamentPlayer::findOrFail($result['player_id']);

                if ($player->tournament_id !== $tournament->id) {
                    throw new RuntimeException("Player $player->id does not belong to tournament $tournament->id");
                }

                $player->update([
                    'position'      => $result['position'],
                    'rating_points' => $result['rating_points'] ?? 0,
                    'prize_amount'  => $result['prize_amount'] ?? 0,
                    'bonus_amount'  => $result['bonus_amount'] ?? 0,
                    'achievement_amount' => $result['achievement_amount'] ?? 0,
                    'status'        => 'confirmed',
                ]);
            }

            // Update tournament status if not already completed
            if ($tournament->status !== TournamentStatus::COMPLETED) {
                $tournament->update([
                    'status' => TournamentStatus::COMPLETED,
                    'stage'  => TournamentStage::COMPLETED,
                ]);
            }

            // Update official ratings if tournament is associated with any
            $this->updateOfficialRatingsFromTournament($tournament);
        });
    }

    /**
     * Update official ratings from tournament results
     */
    public function updateOfficialRatingsFromTournament(Tournament $tournament): void
    {
        // Get all official ratings associated with this tournament
        $officialRatings = $tournament->officialRatings()->where('is_counting', true)->get();

        foreach ($officialRatings as $officialRating) {
            try {
                $this->officialRatingService->updateRatingFromTournament($officialRating, $tournament);
            } catch (Throwable) {
                // Continue with other ratings if one fails
                continue;
            }
        }
    }

    /**
     * Change tournament status
     */
    public function changeTournamentStatus(Tournament $tournament, string $status): Tournament
    {
        $tournament->update(['status' => $status]);

        // If status is being changed to completed, update official ratings
        if ($status === TournamentStatus::COMPLETED->value) {
            $tournament->update(['stage' => TournamentStage::COMPLETED]);
            $this->updateOfficialRatingsFromTournament($tournament);
        }

        return $tournament->fresh();
    }

    /**
     * Change tournament stage
     */
    public function changeTournamentStage(Tournament $tournament, string $stage): Tournament
    {
        $tournament->update(['stage' => $stage]);

        // Update timestamps based on stage
        if ($stage === TournamentStage::GROUP->value || $stage === TournamentStage::BRACKET->value) {
            $tournament->update(['seeding_completed_at' => now()]);
        }

        if ($stage === TournamentStage::BRACKET->value && $tournament->tournament_type === 'groups_playoff') {
            $tournament->update(['groups_completed_at' => now()]);
        }

        return $tournament->fresh();
    }

    /**
     * Complete seeding phase
     * @throws Throwable
     */
    public function completeSeedingPhase(Tournament $tournament): void
    {
        if ($tournament->stage !== TournamentStage::SEEDING) {
            throw new RuntimeException('Tournament is not in seeding phase');
        }

        // Verify all confirmed players have seed numbers
        $unseededPlayers = $tournament
            ->players()
            ->where('status', 'confirmed')
            ->whereNull('seed_number')
            ->count()
        ;

        if ($unseededPlayers > 0) {
            throw new RuntimeException("There are {$unseededPlayers} confirmed players without seed numbers");
        }

        // Move to next stage based on tournament type
        $nextStage = in_array($tournament->tournament_type->value, ['groups', 'groups_playoff', 'team_groups_playoff'])
            ? TournamentStage::GROUP
            : TournamentStage::BRACKET;

        $tournament->update([
            'stage'                => $nextStage,
            'seeding_completed_at' => now(),
        ]);
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

    /**
     * Start tournament registration phase
     */
    public function startRegistration(Tournament $tournament): Tournament
    {
        if ($tournament->status !== TournamentStatus::UPCOMING) {
            throw new RuntimeException('Tournament must be in upcoming status to start registration');
        }

        $tournament->update([
            'stage' => TournamentStage::REGISTRATION,
        ]);

        return $tournament->fresh();
    }

    /**
     * Move from registration to seeding phase
     */
    public function startSeeding(Tournament $tournament): Tournament
    {
        if ($tournament->stage !== TournamentStage::REGISTRATION) {
            throw new RuntimeException('Tournament must be in registration stage to start seeding');
        }

        $confirmedPlayers = $tournament->confirmed_players_count;

        if ($confirmedPlayers < 2) {
            throw new RuntimeException('At least 2 confirmed players are required to start seeding');
        }

        $tournament->update([
            'stage' => TournamentStage::SEEDING,
        ]);

        return $tournament->fresh();
    }

    /**
     * Complete group stage and move to bracket
     */
    public function completeGroupStage(Tournament $tournament): Tournament
    {
        if ($tournament->stage !== TournamentStage::GROUP) {
            throw new RuntimeException('Tournament must be in group stage');
        }

        // Verify all group matches are completed
        $incompleteMatches = $tournament
            ->matches()
            ->where('stage', MatchStage::GROUP)
            ->whereIn('status', [MatchStatus::PENDING, MatchStatus::IN_PROGRESS])
            ->count()
        ;

        if ($incompleteMatches > 0) {
            throw new RuntimeException("There are {$incompleteMatches} incomplete group matches");
        }

        // Calculate group standings and determine advancing players
        $this->calculateGroupStandings($tournament);

        $tournament->update([
            'stage'               => TournamentStage::BRACKET,
            'groups_completed_at' => now(),
        ]);

        return $tournament->fresh();
    }

    /**
     * Calculate group standings
     */
    private function calculateGroupStandings(Tournament $tournament): void
    {
        $groups = $tournament->groups;

        foreach ($groups as $group) {
            $players = $group->players;

            // Calculate wins, losses, and games difference for each player
            foreach ($players as $player) {
                $wins = 0;
                $losses = 0;
                $gamesDiff = 0;

                $matches = $tournament
                    ->matches()
                    ->where('stage', MatchStage::GROUP)
                    ->where(function ($query) use ($player) {
                        $query
                            ->where('player1_id', $player->user_id)
                            ->orWhere('player2_id', $player->user_id)
                        ;
                    })
                    ->where('status', MatchStatus::COMPLETED)
                    ->get()
                ;

                foreach ($matches as $match) {
                    if ($match->winner_id === $player->user_id) {
                        $wins++;
                        if ($match->player1_id === $player->user_id) {
                            $gamesDiff += $match->player1_score - $match->player2_score;
                        } else {
                            $gamesDiff += $match->player2_score - $match->player1_score;
                        }
                    } else {
                        $losses++;
                        if ($match->player1_id === $player->user_id) {
                            $gamesDiff += $match->player1_score - $match->player2_score;
                        } else {
                            $gamesDiff += $match->player2_score - $match->player1_score;
                        }
                    }
                }

                $player->update([
                    'group_wins'       => $wins,
                    'group_losses'     => $losses,
                    'group_games_diff' => $gamesDiff,
                ]);
            }

            // Sort players by wins, then by games difference
            $sortedPlayers = $players
                ->sortByDesc('group_wins')
                ->sortByDesc('group_games_diff')
                ->values()
            ;

            // Update group positions
            foreach ($sortedPlayers as $index => $player) {
                $player->update(['group_position' => $index + 1]);
            }
        }
    }

    /**
     * Get tournament stage transition options
     */
    public function getStageTransitions(Tournament $tournament): array
    {
        $transitions = [];

        switch ($tournament->stage) {
            case TournamentStage::REGISTRATION:
                if ($tournament->confirmed_players_count >= 2) {
                    $transitions[] = [
                        'stage'        => TournamentStage::SEEDING,
                        'label'        => 'Start Seeding',
                        'description'  => 'Move to seeding phase to arrange players',
                        'requirements' => [],
                    ];
                }
                break;

            case TournamentStage::SEEDING:
                $unseededPlayers = $tournament
                    ->players()
                    ->where('status', 'confirmed')
                    ->whereNull('seed_number')
                    ->count()
                ;

                if ($unseededPlayers === 0) {
                    $nextStage = in_array($tournament->tournament_type->value,
                        ['groups', 'groups_playoff', 'team_groups_playoff'])
                        ? TournamentStage::GROUP
                        : TournamentStage::BRACKET;

                    $transitions[] = [
                        'stage'        => $nextStage,
                        'label'        => $nextStage === TournamentStage::GROUP ? 'Generate Groups' : 'Generate Bracket',
                        'description'  => $nextStage === TournamentStage::GROUP
                            ? 'Create groups and generate round-robin matches'
                            : 'Create elimination bracket and matches',
                        'requirements' => [],
                    ];
                }
                break;

            case TournamentStage::GROUP:
                $incompleteMatches = $tournament
                    ->matches()
                    ->where('stage', MatchStage::GROUP)
                    ->whereIn('status', [MatchStatus::PENDING, MatchStatus::IN_PROGRESS])
                    ->count()
                ;

                if ($incompleteMatches === 0 && in_array($tournament->tournament_type->value,
                        ['groups_playoff', 'team_groups_playoff'])) {
                    $transitions[] = [
                        'stage'        => TournamentStage::BRACKET,
                        'label'        => 'Start Playoff',
                        'description'  => 'Move top players from each group to playoff bracket',
                        'requirements' => [],
                    ];
                }
                break;

            case TournamentStage::BRACKET:
                $incompleteMatches = $tournament
                    ->matches()
                    ->where('stage', MatchStage::BRACKET)
                    ->whereIn('status', [MatchStatus::PENDING, MatchStatus::IN_PROGRESS])
                    ->count()
                ;

                if ($incompleteMatches === 0) {
                    $transitions[] = [
                        'stage'        => TournamentStage::COMPLETED,
                        'label'        => 'Complete Tournament',
                        'description'  => 'Finalize results and update ratings',
                        'requirements' => [],
                    ];
                }
                break;
        }

        return $transitions;
    }
}
