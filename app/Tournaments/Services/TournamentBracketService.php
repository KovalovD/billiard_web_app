<?php
// app/Tournaments/Services/TournamentBracketService.php

namespace App\Tournaments\Services;

use App\Tournaments\Enums\BracketType;
use App\Tournaments\Enums\EliminationRound;
use App\Tournaments\Enums\MatchStage;
use App\Tournaments\Enums\TournamentType;
use App\Tournaments\Models\Tournament;
use App\Tournaments\Models\TournamentBracket;
use App\Tournaments\Models\TournamentGroup;
use App\Tournaments\Models\TournamentMatch;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class TournamentBracketService
{
    /**
     * Generate tournament bracket based on tournament type
     * @throws Throwable
     */
    public function generateBracket(Tournament $tournament): TournamentBracket
    {
        return DB::transaction(function () use ($tournament) {
            $confirmedPlayers = $tournament
                ->players()
                ->where('status', 'confirmed')
                ->orderBy('seed_number')
                ->get()
            ;

            if ($confirmedPlayers->count() < 2) {
                throw new RuntimeException('At least 2 confirmed players are required to generate a bracket');
            }

            // Clear existing brackets and matches
            $tournament->brackets()->delete();
            $tournament->matches()->delete();

            switch ($tournament->tournament_type) {
                case TournamentType::SINGLE_ELIMINATION:
                    return $this->generateSingleEliminationBracket($tournament, $confirmedPlayers);

                case TournamentType::DOUBLE_ELIMINATION:
                case TournamentType::DOUBLE_ELIMINATION_FULL:
                    return $this->generateDoubleEliminationBracket($tournament, $confirmedPlayers);

                case TournamentType::ROUND_ROBIN:
                    return $this->generateRoundRobinBracket($tournament, $confirmedPlayers);

                default:
                    throw new RuntimeException("Bracket generation not supported for tournament type: {$tournament->tournament_type->value}");
            }
        });
    }

    /**
     * Generate single elimination bracket
     */
    private function generateSingleEliminationBracket(Tournament $tournament, $confirmedPlayers): TournamentBracket
    {
        $playerCount = $confirmedPlayers->count();
        $bracketSize = 2 ** ceil(log($playerCount, 2)); // Next power of 2
        $rounds = ceil(log($bracketSize, 2));

        $bracket = TournamentBracket::create([
            'tournament_id' => $tournament->id,
            'bracket_type'  => BracketType::SINGLE,
            'total_rounds'  => $rounds,
            'players_count' => $bracketSize,
        ]);

        // Create matches
        $this->createEliminationMatches($tournament, $bracket, $confirmedPlayers, MatchStage::BRACKET);

        // Create third place match if enabled
        if ($tournament->has_third_place_match) {
            $this->createThirdPlaceMatch($tournament);
        }

        return $bracket;
    }

    /**
     * Create elimination matches
     */
    private function createEliminationMatches(
        Tournament $tournament,
        TournamentBracket $bracket,
        $players,
        MatchStage $stage,
        string $bracketSide = 'upper',
    ): void {
        $rounds = $bracket->total_rounds;
        $matchesInFirstRound = $bracket->players_count / 2;

        // Determine rounds
        $roundMap = $this->getRoundMap($matchesInFirstRound);

        // Create matches for each round
        for ($round = 1; $round <= $rounds; $round++) {
            $matchesInRound = $matchesInFirstRound / (2 ** ($round - 1));
            $roundName = $roundMap[$matchesInRound] ?? EliminationRound::ROUND_128;

            for ($match = 0; $match < $matchesInRound; $match++) {
                $matchCode = "{$bracketSide}_R{$round}_M{$match}";

                $tournamentMatch = TournamentMatch::create([
                    'tournament_id'    => $tournament->id,
                    'match_code'       => $matchCode,
                    'stage'            => $stage,
                    'round'            => $roundName,
                    'bracket_position' => $match,
                    'bracket_side'     => $bracketSide,
                    'races_to'         => $tournament->races_to,
                    'status'           => 'pending',
                ]);

                // For first round, assign players
                if ($round === 1) {
                    $player1Index = $match * 2;
                    $player2Index = $match * 2 + 1;

                    if (isset($players[$player1Index])) {
                        $tournamentMatch->player1_id = $players[$player1Index]->user_id;
                    }
                    if (isset($players[$player2Index])) {
                        $tournamentMatch->player2_id = $players[$player2Index]->user_id;
                    }

                    $tournamentMatch->save();
                }
            }
        }

        // Set up match progression
        $this->setupMatchProgression($tournament, $bracketSide);
    }

    /**
     * Get round name mapping
     */
    private function getRoundMap(int $matchesInFirstRound): array
    {
        return [
            1  => EliminationRound::FINALS,
            2  => EliminationRound::SEMIFINALS,
            4  => EliminationRound::QUARTERFINALS,
            8  => EliminationRound::ROUND_16,
            16 => EliminationRound::ROUND_32,
            32 => EliminationRound::ROUND_64,
            64 => EliminationRound::ROUND_128,
        ];
    }

    /**
     * Set up match progression (winners advance to next matches)
     */
    private function setupMatchProgression(Tournament $tournament, string $bracketSide): void
    {
        $matches = $tournament
            ->matches()
            ->where('bracket_side', $bracketSide)
            ->orderBy('round')
            ->orderBy('bracket_position')
            ->get()
        ;

        $matchesByRound = $matches->groupBy('round');

        foreach ($matchesByRound as $round => $roundMatches) {
            foreach ($roundMatches as $index => $match) {
                // Find next match in the progression
                $nextRoundMatches = $matchesByRound->get($round + 1);
                if ($nextRoundMatches) {
                    $nextMatchIndex = floor($index / 2);
                    if (isset($nextRoundMatches[$nextMatchIndex])) {
                        $match->next_match_id = $nextRoundMatches[$nextMatchIndex]->id;
                        $match->save();
                    }
                }
            }
        }
    }

    /**
     * Create third place match
     */
    private function createThirdPlaceMatch(Tournament $tournament): TournamentMatch
    {
        return TournamentMatch::create([
            'tournament_id' => $tournament->id,
            'match_code'    => '3RD',
            'stage'         => MatchStage::THIRD_PLACE,
            'round'         => EliminationRound::THIRD_PLACE,
            'races_to'      => $tournament->races_to,
            'status'        => 'pending',
        ]);
    }

    /**
     * Generate double elimination bracket
     */
    private function generateDoubleEliminationBracket(Tournament $tournament, $confirmedPlayers): TournamentBracket
    {
        // Implementation for double elimination
        // This is a simplified version - full implementation would be more complex

        $playerCount = $confirmedPlayers->count();
        $bracketSize = pow(2, ceil(log($playerCount, 2)));

        // Create upper bracket
        $upperBracket = TournamentBracket::create([
            'tournament_id' => $tournament->id,
            'bracket_type'  => BracketType::DOUBLE_UPPER,
            'total_rounds'  => ceil(log($bracketSize, 2)),
            'players_count' => $bracketSize,
        ]);

        // Create lower bracket
        $lowerBracket = TournamentBracket::create([
            'tournament_id' => $tournament->id,
            'bracket_type'  => BracketType::DOUBLE_LOWER,
            'total_rounds'  => (ceil(log($bracketSize, 2)) - 1) * 2,
            'players_count' => $bracketSize,
        ]);

        // Create matches for upper bracket
        $this->createEliminationMatches($tournament, $upperBracket, $confirmedPlayers, MatchStage::BRACKET, 'upper');

        return $upperBracket;
    }

    /**
     * Generate round robin bracket
     */
    private function generateRoundRobinBracket(Tournament $tournament, $confirmedPlayers): TournamentBracket
    {
        $bracket = TournamentBracket::create([
            'tournament_id' => $tournament->id,
            'bracket_type'  => BracketType::SINGLE,
            'total_rounds'  => 1,
            'players_count' => $confirmedPlayers->count(),
        ]);

        // Create matches between every pair of players
        foreach ($confirmedPlayers as $i => $player1) {
            foreach ($confirmedPlayers->slice($i + 1) as $player2) {
                TournamentMatch::create([
                    'tournament_id' => $tournament->id,
                    'match_code'    => "RR_{$player1->id}_{$player2->id}",
                    'stage'         => MatchStage::BRACKET,
                    'round'         => EliminationRound::ROUND_128, // Or create a specific round robin round
                    'player1_id'    => $player1->user_id,
                    'player2_id'    => $player2->user_id,
                    'races_to'      => $tournament->races_to,
                    'status'        => 'pending',
                ]);
            }
        }

        return $bracket;
    }

    /**
     * Generate groups for group stage tournaments
     * @throws Throwable
     */
    public function generateGroups(Tournament $tournament): array
    {
        return DB::transaction(function () use ($tournament) {
            if (!in_array($tournament->tournament_type->value, ['groups', 'groups_playoff', 'team_groups_playoff'])) {
                throw new RuntimeException('Tournament type does not support group stage');
            }

            $confirmedPlayers = $tournament
                ->players()
                ->where('status', 'confirmed')
                ->orderBy('seed_number')
                ->get()
            ;

            if ($confirmedPlayers->count() < $tournament->group_size_min) {
                throw new RuntimeException("At least {$tournament->group_size_min} confirmed players are required for group stage");
            }

            // Clear existing groups
            $tournament->groups()->delete();

            // Calculate number of groups
            $playerCount = $confirmedPlayers->count();
            $avgGroupSize = ($tournament->group_size_min + $tournament->group_size_max) / 2;
            $groupCount = max(1, round($playerCount / $avgGroupSize));

            $groups = [];

            // Create groups
            for ($i = 0; $i < $groupCount; $i++) {
                $groupCode = chr(65 + $i); // A, B, C, etc.

                $group = TournamentGroup::create([
                    'tournament_id' => $tournament->id,
                    'group_code'    => $groupCode,
                    'group_size'    => 0, // Will be updated
                    'advance_count' => $tournament->playoff_players_per_group ?? 2,
                ]);

                $groups[] = $group;
            }

            // Distribute players to groups using snake seeding
            $playerIndex = 0;
            $direction = 1;
            $currentGroup = 0;

            foreach ($confirmedPlayers as $player) {
                $player->update([
                    'group_code'     => $groups[$currentGroup]->group_code,
                    'group_position' => null,
                ]);

                // Snake pattern: 0,1,2,2,1,0,0,1,2...
                $currentGroup += $direction;
                if ($currentGroup >= $groupCount || $currentGroup < 0) {
                    $direction *= -1;
                    $currentGroup = max(0, min($groupCount - 1, $currentGroup));
                }
            }

            // Update group sizes and create round-robin matches
            foreach ($groups as $group) {
                $groupPlayers = $tournament
                    ->players()
                    ->where('group_code', $group->group_code)
                    ->get()
                ;

                $group->update(['group_size' => $groupPlayers->count()]);

                // Create round-robin matches for this group
                $this->createGroupMatches($tournament, $group, $groupPlayers);
            }

            return $groups;
        });
    }

    /**
     * Create group stage matches
     */
    private function createGroupMatches(Tournament $tournament, TournamentGroup $group, $players): void
    {
        foreach ($players as $i => $player1) {
            foreach ($players->slice($i + 1) as $player2) {
                TournamentMatch::create([
                    'tournament_id' => $tournament->id,
                    'match_code'    => "{$group->group_code}_{$player1->id}_{$player2->id}",
                    'stage'         => MatchStage::GROUP,
                    'round'         => EliminationRound::GROUPS,
                    'player1_id'    => $player1->user_id,
                    'player2_id'    => $player2->user_id,
                    'races_to'      => $tournament->races_to,
                    'status'        => 'pending',
                ]);
            }
        }
    }
}
