<?php

namespace App\OfficialTournaments\Services\Bracket;

use App\OfficialTournaments\Models\OfficialMatch;
use App\OfficialTournaments\Models\OfficialParticipant;
use App\OfficialTournaments\Models\OfficialStage;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * Service for managing Round Robin and Group stage tournaments
 */
class RoundRobinService
{
    /**
     * Generate multiple groups with round robin
     *
     * @param  OfficialStage  $stage
     * @param  Collection  $participants  All participants
     * @param  int  $groupCount  Number of groups
     * @return array ['groups' => groups info, 'matches' => all matches]
     * @throws Throwable
     * @throws Throwable
     */
    public function generateGroups(OfficialStage $stage, Collection $participants, int $groupCount): array
    {
        if ($groupCount < 1) {
            throw new InvalidArgumentException('At least 1 group required');
        }

        $participantsPerGroup = ceil($participants->count() / $groupCount);
        $groups = [];
        $allMatches = [];

        // Distribute participants into groups (assumes they're already seeded)
        $groupedParticipants = $this->distributeIntoGroups($participants, $groupCount);

        DB::beginTransaction();
        try {
            foreach ($groupedParticipants as $index => $groupParticipants) {
                $groupName = chr(65 + $index); // A, B, C, D...

                $groups[] = [
                    'name'              => "Group {$groupName}",
                    'identifier'        => $groupName,
                    'participants'      => $groupParticipants,
                    'participant_count' => $groupParticipants->count(),
                ];

                $matches = $this->generateRoundRobin($stage, $groupParticipants, $groupName);
                $allMatches = array_merge($allMatches, $matches);
            }

            DB::commit();

            return [
                'groups'        => $groups,
                'matches'       => $allMatches,
                'total_matches' => count($allMatches),
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Distribute participants into groups using snake pattern
     */
    protected function distributeIntoGroups(Collection $participants, int $groupCount): array
    {
        $groups = [];
        for ($i = 0; $i < $groupCount; $i++) {
            $groups[$i] = collect();
        }

        // Snake distribution based on seed
        $sorted = $participants->sortBy('seed');
        $direction = 1;
        $currentGroup = 0;

        foreach ($sorted as $participant) {
            $groups[$currentGroup]->push($participant);

            $currentGroup += $direction;
            if ($currentGroup >= $groupCount) {
                $currentGroup = $groupCount - 1;
                $direction = -1;
            } elseif ($currentGroup < 0) {
                $currentGroup = 0;
                $direction = 1;
            }
        }

        return $groups;
    }

    /**
     * Generate round robin matches for a single group
     *
     * @param  OfficialStage  $stage
     * @param  Collection  $participants
     * @param  string  $groupName
     * @return array Generated matches
     * @throws Throwable
     */
    public function generateRoundRobin(OfficialStage $stage, Collection $participants, string $groupName = 'A'): array
    {
        $matches = [];
        $participantCount = $participants->count();

        if ($participantCount < 2) {
            return $matches;
        }

        // Create all possible match combinations
        $participantArray = $participants->values()->toArray();

        DB::beginTransaction();
        try {
            for ($i = 0; $i < $participantCount; $i++) {
                for ($j = $i + 1; $j < $participantCount; $j++) {
                    $match = OfficialMatch::create([
                        'stage_id' => $stage->id,
                        'round'    => null, // Round robin doesn't use rounds
                        'bracket'  => $groupName,
                        'status'   => OfficialMatch::STATUS_PENDING,
                        'metadata' => [
                            'group'           => $groupName,
                            'participant1_id' => $participantArray[$i]->id,
                            'participant2_id' => $participantArray[$j]->id,
                            'is_group_match'  => true,
                        ],
                    ]);

                    $matches[] = $match;
                }
            }

            DB::commit();
            return $matches;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Generate playoff bracket from group winners
     *
     * @param  OfficialStage  $groupStage
     * @param  OfficialStage  $playoffStage
     * @param  int  $advancePerGroup
     * @return void
     * @throws Throwable
     * @throws Throwable
     */
    public function generatePlayoffFromGroups(
        OfficialStage $groupStage,
        OfficialStage $playoffStage,
        int $advancePerGroup = 2,
    ): void {
        $advancing = $this->getAdvancingParticipants($groupStage, $advancePerGroup);

        // Create participants in playoff stage
        DB::transaction(static function () use ($advancing, $playoffStage) {
            $seed = 1;

            foreach ($advancing as $participant) {
                OfficialParticipant::create([
                    'stage_id'        => $playoffStage->id,
                    'user_id'         => $participant->user_id,
                    'team_id'         => $participant->team_id,
                    'seed'            => $seed++,
                    'rating_snapshot' => $participant->rating_snapshot,
                ]);
            }
        });
    }

    /**
     * Get participants who advance from groups
     *
     * @param  OfficialStage  $stage
     * @param  int  $advancePerGroup  Number to advance from each group
     * @return Collection
     */
    public function getAdvancingParticipants(OfficialStage $stage, int $advancePerGroup = 2): Collection
    {
        $allStandings = $this->calculateAllGroupStandings($stage);
        $advancing = collect();

        foreach ($allStandings as $groupData) {
            $topParticipants = $groupData['standings']
                ->take($advancePerGroup)
                ->pluck('participant')
            ;

            $advancing = $advancing->merge($topParticipants);
        }

        return $advancing;
    }

    /**
     * Calculate standings for all groups
     *
     * @param  OfficialStage  $stage
     * @return Collection
     */
    public function calculateAllGroupStandings(OfficialStage $stage): Collection
    {
        return $this->getGroups($stage)->map(function ($groupIdentifier) use ($stage) {
            return [
                'group'     => $groupIdentifier,
                'standings' => $this->calculateGroupStandings($stage, $groupIdentifier),
            ];
        });
    }

    /**
     * Get all group identifiers in stage
     */
    protected function getGroups(OfficialStage $stage): Collection
    {
        return $stage
            ->matches()
            ->whereNotNull('bracket')
            ->pluck('bracket')
            ->unique()
            ->sort()
        ;
    }

    /**
     * Calculate standings for a group
     *
     * @param  OfficialStage  $stage
     * @param  string  $groupIdentifier
     * @return Collection Sorted standings
     */
    public function calculateGroupStandings(OfficialStage $stage, string $groupIdentifier): Collection
    {
        // Get all matches for this group
        $matches = $stage
            ->matches()
            ->where('bracket', $groupIdentifier)
            ->where('status', OfficialMatch::STATUS_FINISHED)
            ->with('matchSets')
            ->get()
        ;

        // Get all participants in this group
        $participants = $this->getGroupParticipants($stage, $groupIdentifier);

        // Initialize standings
        $standings = $participants->mapWithKeys(function ($participant) {
            return [
                $participant->id => [
                    'participant'    => $participant,
                    'matches_played' => 0,
                    'matches_won'    => 0,
                    'matches_lost'   => 0,
                    'sets_won'       => 0,
                    'sets_lost'      => 0,
                    'games_won'      => 0,
                    'games_lost'     => 0,
                    'points'         => 0,
                    'h2h'            => [], // Head-to-head results
                ],
            ];
        });

        // Process each match
        foreach ($matches as $match) {
            $this->processMatchForStandings($match, $standings);
        }

        // Calculate additional metrics and sort
        $sorted = $standings->map(function ($record) {
            $record['sets_difference'] = $record['sets_won'] - $record['sets_lost'];
            $record['games_difference'] = $record['games_won'] - $record['games_lost'];
            $record['win_percentage'] = $record['matches_played'] > 0
                ? round($record['matches_won'] / $record['matches_played'] * 100, 1)
                : 0;

            return $record;
        })->sort(function ($a, $b) use ($standings) {
            // Sort by: Points -> Matches Won -> Head-to-Head -> Sets Diff -> Games Diff
            if ($a['points'] !== $b['points']) {
                return $b['points'] <=> $a['points'];
            }

            if ($a['matches_won'] !== $b['matches_won']) {
                return $b['matches_won'] <=> $a['matches_won'];
            }

            // Head-to-head
            $h2h = $this->compareHeadToHead(
                $a['participant']->id,
                $b['participant']->id,
                $standings,
            );
            if ($h2h !== 0) {
                return $h2h;
            }

            if ($a['sets_difference'] !== $b['sets_difference']) {
                return $b['sets_difference'] <=> $a['sets_difference'];
            }

            return $b['games_difference'] <=> $a['games_difference'];
        });

        // Add position numbers
        $position = 1;
        return $sorted->map(function ($record) use (&$position) {
            $record['position'] = $position++;
            return $record;
        })->values();
    }

    /**
     * Get participants in a specific group
     */
    protected function getGroupParticipants(OfficialStage $stage, string $groupIdentifier): Collection
    {
        $participantIds = $stage
            ->matches()
            ->where('bracket', $groupIdentifier)
            ->get()
            ->flatMap(function ($match) {
                return [
                    $match->metadata['participant1_id'] ?? null,
                    $match->metadata['participant2_id'] ?? null,
                ];
            })
            ->filter()
            ->unique()
        ;

        return OfficialParticipant::whereIn('id', $participantIds)->get();
    }

    /**
     * Process match results for standings
     */
    protected function processMatchForStandings(OfficialMatch $match, Collection $standings): void
    {
        $p1Id = $match->metadata['participant1_id'];
        $p2Id = $match->metadata['participant2_id'];

        if (!$p1Id || !$p2Id) {
            return;
        }

        // Count sets won
        $p1SetsWon = $match->matchSets->where('winner_participant_id', $p1Id)->count();
        $p2SetsWon = $match->matchSets->where('winner_participant_id', $p2Id)->count();

        // Count total games/racks
        $p1GamesWon = 0;
        $p2GamesWon = 0;

        foreach ($match->matchSets as $set) {
            $p1GamesWon += $set->score_json['participant1'] ?? 0;
            $p2GamesWon += $set->score_json['participant2'] ?? 0;
        }

        // Update standings
        if (isset($standings[$p1Id])) {
            $standings[$p1Id]['matches_played']++;
            $standings[$p1Id]['sets_won'] += $p1SetsWon;
            $standings[$p1Id]['sets_lost'] += $p2SetsWon;
            $standings[$p1Id]['games_won'] += $p1GamesWon;
            $standings[$p1Id]['games_lost'] += $p2GamesWon;

            if ($p1SetsWon > $p2SetsWon) {
                $standings[$p1Id]['matches_won']++;
                $standings[$p1Id]['points'] += 3; // 3 points for win
                $standings[$p1Id]['h2h'][$p2Id] = 'W';
            } else {
                $standings[$p1Id]['matches_lost']++;
                $standings[$p1Id]['h2h'][$p2Id] = 'L';
            }
        }

        if (isset($standings[$p2Id])) {
            $standings[$p2Id]['matches_played']++;
            $standings[$p2Id]['sets_won'] += $p2SetsWon;
            $standings[$p2Id]['sets_lost'] += $p1SetsWon;
            $standings[$p2Id]['games_won'] += $p2GamesWon;
            $standings[$p2Id]['games_lost'] += $p1GamesWon;

            if ($p2SetsWon > $p1SetsWon) {
                $standings[$p2Id]['matches_won']++;
                $standings[$p2Id]['points'] += 3;
                $standings[$p2Id]['h2h'][$p1Id] = 'W';
            } else {
                $standings[$p2Id]['matches_lost']++;
                $standings[$p2Id]['h2h'][$p1Id] = 'L';
            }
        }
    }

    /**
     * Compare head-to-head results
     */
    protected function compareHeadToHead(int $id1, int $id2, Collection $standings): int
    {
        $p1H2H = $standings[$id1]['h2h'][$id2] ?? null;

        if ($p1H2H === 'W') {
            return -1; // p1 wins
        }

        if ($p1H2H === 'L') {
            return 1; // p2 wins
        }

        return 0; // No h2h or draw
    }

    /**
     * Check if all group matches are complete
     */
    public function areAllGroupMatchesComplete(OfficialStage $stage, string $groupIdentifier): bool
    {
        return !$stage
            ->matches()
            ->where('bracket', $groupIdentifier)
            ->where('status', '!=', OfficialMatch::STATUS_FINISHED)
            ->exists()
        ;
    }

    /**
     * Get match schedule for round robin (minimizing wait times)
     *
     * @param  Collection  $matches  All round robin matches
     * @param  int  $simultaneousMatches  How many can play at once
     * @return Collection Ordered matches
     */
    public function optimizeRoundRobinSchedule(Collection $matches, int $simultaneousMatches = 2): Collection
    {
        // Use circle method for optimal scheduling
        $participants = $this->extractParticipantsFromMatches($matches);
        $participantCount = $participants->count();

        if ($participantCount % 2 === 1) {
            $participantCount++; // Add bye if odd
        }

        $rounds = [];
        $schedule = collect();

        // Create rounds using circle method
        for ($round = 0; $round < $participantCount - 1; $round++) {
            $roundMatches = collect();

            for ($i = 0; $i < $participantCount / 2; $i++) {
                $home = ($round + $i) % ($participantCount - 1);
                $away = ($participantCount - 1 - $i + $round) % ($participantCount - 1);

                if ($i === 0) {
                    $away = $participantCount - 1;
                }

                // Find the actual match
                $match = $matches->first(function ($m) use ($participants, $home, $away) {
                    $p1Id = $m->metadata['participant1_id'];
                    $p2Id = $m->metadata['participant2_id'];

                    return ($participants->values()[$home]->id === $p1Id &&
                            $participants->values()[$away]->id === $p2Id) ||
                        ($participants->values()[$home]->id === $p2Id &&
                            $participants->values()[$away]->id === $p1Id);
                });

                if ($match) {
                    $roundMatches->push($match);
                }
            }

            $schedule = $schedule->merge($roundMatches);
        }

        return $schedule;
    }

    /**
     * Extract unique participants from matches
     */
    protected function extractParticipantsFromMatches(Collection $matches): Collection
    {
        $participantIds = $matches->flatMap(function ($match) {
            return [
                $match->metadata['participant1_id'],
                $match->metadata['participant2_id'],
            ];
        })->unique()->filter();

        return OfficialParticipant::whereIn('id', $participantIds)->get();
    }
}
