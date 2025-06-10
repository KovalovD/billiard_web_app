<?php

namespace App\Tournaments\Services;

use App\Core\Models\User;
use App\Tournaments\Enums\TournamentFormat;
use App\Tournaments\Enums\SeedingMethod;
use App\Tournaments\Models\Tournament;
use App\Tournaments\Models\TournamentBracket;
use App\Tournaments\Models\TournamentGroup;
use App\Tournaments\Models\TournamentMatch;
use App\Tournaments\Models\TournamentPlayer;
use App\Tournaments\Models\TournamentTeam;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TournamentManagementService
{
    /**
     * Initialize tournament structure based on format
     */
    public function initializeTournamentStructure(Tournament $tournament): void
    {
        if ($tournament->isStructureInitialized()) {
            throw new RuntimeException('Tournament structure is already initialized');
        }

        if ($tournament->confirmed_players_count < 2) {
            throw new RuntimeException('At least 2 confirmed players required to initialize tournament');
        }

        DB::transaction(function () use ($tournament) {
            $tournament->initializeStructure();
            $tournament->update(['status' => 'active']);
        });
    }

    /**
     * Create and configure tournament groups
     */
    public function createTournamentGroups(Tournament $tournament, array $groupConfig): Collection
    {
        if (!$tournament->hasGroups()) {
            throw new RuntimeException('Tournament format does not support groups');
        }

        $groups = collect();

        foreach ($groupConfig as $config) {
            $group = TournamentGroup::create([
                'tournament_id'    => $tournament->id,
                'name'             => $config['name'],
                'display_name'     => $config['display_name'] ?? null,
                'group_number'     => $config['group_number'],
                'max_participants' => $config['max_participants'],
                'advance_count'    => $config['advance_count'] ?? 2,
            ]);

            $groups->push($group);
        }

        return $groups;
    }

    /**
     * Create tournament teams for team-based tournaments
     */
    public function createTournamentTeam(Tournament $tournament, array $teamData, array $playerIds): TournamentTeam
    {
        if (!$tournament->is_team_tournament) {
            throw new RuntimeException('Tournament is not configured for teams');
        }

        return DB::transaction(function () use ($tournament, $teamData, $playerIds) {
            $team = TournamentTeam::create([
                'tournament_id' => $tournament->id,
                'name'          => $teamData['name'],
                'short_name'    => $teamData['short_name'] ?? null,
                'seed'          => $teamData['seed'] ?? null,
                'group_id'      => $teamData['group_id'] ?? null,
                'roster_data'   => $teamData['roster_data'] ?? [],
            ]);

            // Assign players to team
            foreach ($playerIds as $index => $playerId) {
                $player = TournamentPlayer::where('tournament_id', $tournament->id)
                    ->where('user_id', $playerId)
                    ->first()
                ;

                if (!$player) {
                    throw new RuntimeException("Player ID {$playerId} is not registered for this tournament");
                }

                $role = $index === 0 ? 'captain' : 'player';
                $player->update([
                    'team_id'   => $team->id,
                    'team_role' => $role,
                ]);
            }

            return $team;
        });
    }

    /**
     * Assign players to groups using specified seeding method
     */
    public function assignPlayersToGroups(Tournament $tournament): void
    {
        if (!$tournament->hasGroups()) {
            throw new RuntimeException('Tournament does not use groups');
        }

        $players = $tournament->confirmedPlayers()->get();
        $groups = $tournament->groups()->orderBy('group_number')->get();

        if ($groups->isEmpty()) {
            throw new RuntimeException('No groups found for tournament');
        }

        // Apply seeding
        $seededPlayers = $this->applySeedingMethod($players, $tournament->seeding_method);

        // Distribute players across groups
        foreach ($seededPlayers as $index => $player) {
            $groupIndex = $index % $groups->count();
            $group = $groups[$groupIndex];

            $player->update([
                'group_id' => $group->id,
                'seed'     => $index + 1,
            ]);
        }

        // Create group matches for each group
        foreach ($groups as $group) {
            $this->createGroupMatches($group);
        }
    }

    /**
     * Create round-robin matches for a group
     */
    public function createGroupMatches(TournamentGroup $group): void
    {
        $participants = $group->tournament->is_team_tournament
            ? $group->teams()->get()
            : $group->players()->get();

        $participantCount = $participants->count();

        if ($participantCount < 2) {
            return;
        }

        $matchNumber = 1;

        // Create round-robin matches
        for ($i = 0; $i < $participantCount; $i++) {
            for ($j = $i + 1; $j < $participantCount; $j++) {
                TournamentMatch::create([
                    'tournament_id'      => $group->tournament_id,
                    'match_type'         => 'group',
                    'round_number'       => 1,
                    'match_number'       => $matchNumber,
                    'group_id'           => $group->id,
                    'participant_1_id'   => $participants[$i]->id,
                    'participant_1_type' => $group->tournament->is_team_tournament ? 'team' : 'player',
                    'participant_2_id'   => $participants[$j]->id,
                    'participant_2_type' => $group->tournament->is_team_tournament ? 'team' : 'player',
                    'status'             => 'pending',
                ]);

                $matchNumber++;
            }
        }
    }

    /**
     * Create elimination bracket matches
     */
    public function createBracketMatches(Tournament $tournament): void
    {
        if (!$tournament->hasBrackets()) {
            throw new RuntimeException('Tournament format does not support brackets');
        }

        $participants = $this->getBracketParticipants($tournament);
        $mainBracket = $tournament
            ->brackets()
            ->where('bracket_type', $tournament->tournament_format === 'double_elimination' ? 'upper' : 'main')
            ->first()
        ;

        if (!$mainBracket) {
            throw new RuntimeException('Main bracket not found');
        }

        if ($tournament->tournament_format === 'single_elimination') {
            $mainBracket->initializeSingleElimination($participants);
        } elseif ($tournament->tournament_format === 'double_elimination') {
            $mainBracket->initializeDoubleElimination($participants);
        }
    }

    /**
     * Enter match result and advance tournament
     */
    public function enterMatchResult(TournamentMatch $match, array $result): void
    {
        if ($match->isCompleted()) {
            throw new RuntimeException('Match is already completed');
        }

        DB::transaction(function () use ($match, $result) {
            $match->complete($result);

            // Update player statistics
            $this->updatePlayerStatistics($match);

            // Check for tournament progression
            $this->checkTournamentProgression($match);
        });
    }

    /**
     * Reschedule a tournament match
     */
    public function rescheduleMatch(
        TournamentMatch $match,
        \DateTime $newTime,
        ?int $tableNumber = null,
        ?int $clubId = null,
    ): void {
        if ($match->isCompleted()) {
            throw new RuntimeException('Cannot reschedule completed match');
        }

        $updateData = [
            'scheduled_at' => $newTime,
        ];

        if ($tableNumber !== null) {
            $updateData['table_number'] = $tableNumber;
        }

        if ($clubId !== null) {
            $updateData['club_id'] = $clubId;
        }

        $match->update($updateData);
    }

    /**
     * Get tournament bracket participants
     */
    protected function getBracketParticipants(Tournament $tournament): array
    {
        if ($tournament->tournament_format === 'group_playoff') {
            // Get advancing players from groups
            $participants = [];
            foreach ($tournament->groups as $group) {
                $advancing = $group->getAdvancingParticipants();
                foreach ($advancing as $participant) {
                    $participants[] = [
                        'id'   => $participant['participant_id'],
                        'type' => $tournament->is_team_tournament ? 'team' : 'player',
                        'seed' => count($participants) + 1,
                    ];
                }
            }
            return $participants;
        }

        // Direct bracket entry
        $participants = [];
        $entities = $tournament->is_team_tournament
            ? $tournament->teams()->orderBy('seed')->get()
            : $tournament->confirmedPlayers()->orderBy('seed')->get();

        foreach ($entities as $entity) {
            $participants[] = [
                'id'   => $entity->id,
                'type' => $tournament->is_team_tournament ? 'team' : 'player',
                'seed' => $entity->seed ?? count($participants) + 1,
            ];
        }

        return $participants;
    }

    /**
     * Apply seeding method to participants
     */
    protected function applySeedingMethod(Collection $participants, string $seedingMethod): Collection
    {
        return match ($seedingMethod) {
            SeedingMethod::Random->value => $participants->shuffle(),
            SeedingMethod::RatingBased->value => $this->seedByRating($participants),
            SeedingMethod::Manual->value => $participants->sortBy('seed'),
            default => $participants,
        };
    }

    /**
     * Seed participants by rating
     */
    protected function seedByRating(Collection $participants): Collection
    {
        return $participants->sortByDesc(function ($participant) {
            // Get the highest rating from user's active league ratings
            return $participant->user
                ->ratings()
                ->where('is_active', true)
                ->orderBy('rating', 'desc')
                ->first()?->rating ?? 1000;
        });
    }

    /**
     * Update player statistics after match completion
     */
    protected function updatePlayerStatistics(TournamentMatch $match): void
    {
        if ($match->participant_1_type === 'player' && $match->participant_1_id) {
            $player = TournamentPlayer::find($match->participant_1_id);
            $player?->updateStatistics();
        }

        if ($match->participant_2_type === 'player' && $match->participant_2_id) {
            $player = TournamentPlayer::find($match->participant_2_id);
            $player?->updateStatistics();
        }
    }

    /**
     * Check tournament progression after match completion
     */
    protected function checkTournamentProgression(TournamentMatch $match): void
    {
        if ($match->match_type === 'group') {
            $this->checkGroupProgression($match);
        } elseif ($match->match_type === 'bracket') {
            $this->checkBracketProgression($match);
        }
    }

    /**
     * Check group stage progression
     */
    protected function checkGroupProgression(TournamentMatch $match): void
    {
        if (!$match->group) {
            return;
        }

        $group = $match->group;

        // Update group standings
        $group->updateStandings();

        // Check if group is complete
        if ($group->checkCompletion()) {
            // Check if all groups are complete
            $tournament = $match->tournament;
            $allGroupsComplete = $tournament
                    ->groups()
                    ->where('is_completed', false)
                    ->count() === 0;

            if ($allGroupsComplete && $tournament->tournament_format === 'group_playoff') {
                // Create playoff bracket
                $this->createBracketMatches($tournament);
            }
        }
    }

    /**
     * Check bracket progression
     */
    protected function checkBracketProgression(TournamentMatch $match): void
    {
        $tournament = $match->tournament;
        $bracket = $tournament
            ->brackets()
            ->where('bracket_type', $match->bracket_type)
            ->first()
        ;

        if (!$bracket) {
            return;
        }

        // Check if current round is complete
        if ($bracket->isRoundComplete($match->round_number)) {
            $bracket->advanceToNextRound($match->round_number);

            // Check if tournament is complete
            if ($bracket->is_completed) {
                $this->completeTournament($tournament);
            }
        }
    }

    /**
     * Complete tournament and calculate final standings
     */
    protected function completeTournament(Tournament $tournament): void
    {
        $tournament->update(['status' => 'completed']);

        // Calculate final positions and distribute prizes
        $this->calculateFinalStandings($tournament);
    }

    /**
     * Calculate final standings and positions
     */
    protected function calculateFinalStandings(Tournament $tournament): void
    {
        // Implementation would depend on tournament format
        // This is a placeholder for the actual standing calculation logic

        if ($tournament->hasBrackets()) {
            $this->calculateBracketStandings($tournament);
        } elseif ($tournament->hasGroups() && $tournament->tournament_format === 'group_stage') {
            $this->calculateGroupStandings($tournament);
        }
    }

    /**
     * Calculate standings for bracket tournaments
     */
    protected function calculateBracketStandings(Tournament $tournament): void
    {
        $mainBracket = $tournament
            ->brackets()
            ->where('bracket_type', 'main')
            ->orWhere('bracket_type', 'upper')
            ->first()
        ;

        if (!$mainBracket) {
            return;
        }

        $champion = $mainBracket->getChampion();

        if ($champion && $champion['type'] === 'player') {
            $player = TournamentPlayer::find($champion['id']);
            $player?->update(['position' => 1]);
        }

        // Calculate other positions based on elimination round
        // This is a simplified version - full implementation would be more complex
    }

    /**
     * Calculate standings for group-only tournaments
     */
    protected function calculateGroupStandings(Tournament $tournament): void
    {
        $allStandings = [];

        foreach ($tournament->groups as $group) {
            $groupStandings = $group->getStandings();
            $allStandings = array_merge($allStandings, $groupStandings);
        }

        // Sort by points, then by games difference
        usort($allStandings, function ($a, $b) {
            if ($a['points'] !== $b['points']) {
                return $b['points'] - $a['points'];
            }
            return $b['games_difference'] - $a['games_difference'];
        });

        // Update player positions
        foreach ($allStandings as $index => $standing) {
            $player = TournamentPlayer::find($standing['participant_id']);
            $player?->update(['position' => $index + 1]);
        }
    }
}
