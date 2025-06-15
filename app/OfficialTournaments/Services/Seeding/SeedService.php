<?php

namespace App\OfficialTournaments\Services\Seeding;

use App\OfficialTournaments\Models\OfficialParticipant;
use App\OfficialTournaments\Models\OfficialStage;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Service for seeding tournament participants
 *
 * Handles various seeding methods: manual, random, rating-based snake
 */
class SeedService
{
    /**
     * Apply manual seeding
     *
     * @param  Collection  $participants  Collection with 'id' => 'seed' mapping
     * @return void
     * @throws Throwable
     */
    public function applySeedingManual(Collection $participants): void
    {
        DB::transaction(static function () use ($participants) {
            foreach ($participants as $participantId => $seed) {
                OfficialParticipant::where('id', $participantId)
                    ->update(['seed' => $seed])
                ;
            }
        });
    }

    /**
     * Apply random seeding with optional same-club avoidance
     *
     * @param  OfficialStage  $stage
     * @param  bool  $avoidSameClub  Try to separate players from same club
     * @return Collection Seeded participants
     * @throws Throwable
     * @throws Throwable
     */
    public function applySeedingRandom(OfficialStage $stage, bool $avoidSameClub = true): Collection
    {
        $participants = $stage
            ->participants()
            ->with(['user.homeClub', 'team.club'])
            ->get()
        ;

        if ($avoidSameClub) {
            return $this->randomSeedWithClubSeparation($participants);
        }

        // Simple random shuffle
        $shuffled = $participants->shuffle();

        DB::transaction(static function () use ($shuffled) {
            $seed = 1;
            foreach ($shuffled as $participant) {
                $participant->update(['seed' => $seed]);
                $seed++;
            }
        });

        return $shuffled;
    }

    /**
     * Random seeding with club separation
     */
    protected function randomSeedWithClubSeparation(Collection $participants): Collection
    {
        // Group by club
        $grouped = $participants->groupBy(function ($participant) {
            if ($participant->team_id) {
                return 'team_'.$participant->team->club_id;
            }
            return 'user_'.($participant->user->home_club_id ?? 'no_club');
        });

        // Distribute players from same club evenly
        $distributed = collect();
        $maxRounds = $grouped->max(fn($group) => $group->count());

        for ($round = 0; $round < $maxRounds; $round++) {
            foreach ($grouped as $clubId => $clubParticipants) {
                if ($round < $clubParticipants->count()) {
                    $distributed->push($clubParticipants->values()[$round]);
                }
            }
        }

        // Add some randomization while keeping general distribution
        $chunks = $distributed->chunk(4);
        $shuffled = collect();

        foreach ($chunks as $chunk) {
            $shuffled = $shuffled->merge($chunk->shuffle());
        }

        // Assign seeds
        DB::transaction(static function () use ($shuffled) {
            $seed = 1;
            foreach ($shuffled as $participant) {
                $participant->update(['seed' => $seed]);
                $seed++;
            }
        });

        return $shuffled;
    }

    /**
     * Apply rating-based snake seeding
     *
     * Snake pattern ensures balanced groups/brackets:
     * Group 1: 1, 8, 9, 16
     * Group 2: 2, 7, 10, 15
     * Group 3: 3, 6, 11, 14
     * Group 4: 4, 5, 12, 13
     *
     * @param  OfficialStage  $stage
     * @param  int  $groupCount  Number of groups/pools
     * @return Collection Seeded participants
     * @throws Throwable
     * @throws Throwable
     */
    public function applySeedingByRating(OfficialStage $stage, int $groupCount = 4): Collection
    {
        $participants = $stage
            ->participants()
            ->with('user')
            ->get()
            ->sortByDesc('rating_snapshot')
        ;

        // First, assign sequential seeds by rating
        $rankedParticipants = collect();
        $rank = 1;

        foreach ($participants as $participant) {
            $participant->rating_rank = $rank;
            $rankedParticipants->push($participant);
            $rank++;
        }

        // Apply snake pattern for final seeding
        $seededParticipants = $this->applySnakePattern($rankedParticipants, $groupCount);

        DB::transaction(static function () use ($seededParticipants) {
            foreach ($seededParticipants as $seed => $participant) {
                $participant->update(['seed' => $seed + 1]);
            }
        });

        return $seededParticipants;
    }

    /**
     * Apply snake pattern for group distribution
     */
    protected function applySnakePattern(Collection $rankedParticipants, int $groupCount): Collection
    {
        $groups = collect();
        for ($i = 0; $i < $groupCount; $i++) {
            $groups->push(collect());
        }

        $direction = 1; // 1 for forward, -1 for reverse
        $currentGroup = 0;

        foreach ($rankedParticipants as $participant) {
            $groups[$currentGroup]->push($participant);

            // Move to next group
            $currentGroup += $direction;

            // Change direction at the ends
            if ($currentGroup >= $groupCount) {
                $currentGroup = $groupCount - 1;
                $direction = -1;
            } elseif ($currentGroup < 0) {
                $currentGroup = 0;
                $direction = 1;
            }
        }

        // Flatten groups back to single collection with proper seeding
        $seeded = collect();
        $seed = 0;

        // Take one from each group in rounds
        $maxInGroup = $groups->max(fn($g) => $g->count());

        for ($round = 0; $round < $maxInGroup; $round++) {
            foreach ($groups as $group) {
                if ($round < $group->count()) {
                    $participant = $group[$round];
                    $participant->final_seed = $seed;
                    $seeded->push($participant);
                    $seed++;
                }
            }
        }

        return $seeded;
    }

    /**
     * Apply seeding based on previous tournament results
     *
     * @param  OfficialStage  $stage
     * @param  int  $previousTournamentId  Tournament to use for seeding
     * @return Collection
     * @throws Throwable
     * @throws Throwable
     */
    public function applySeedingByPreviousResults(OfficialStage $stage, int $previousTournamentId): Collection
    {
        $participants = $stage
            ->participants()
            ->with(['user'])
            ->get()
        ;

        // Get previous tournament results
        $previousResults = DB::table('official_participants as p')
            ->join('official_stages as s', 'p.stage_id', '=', 's.id')
            ->join('official_tournaments as t', 's.tournament_id', '=', 't.id')
            ->where('t.id', $previousTournamentId)
            ->whereIn('p.user_id', $participants->pluck('user_id'))
            ->select('p.user_id', 'p.seed as previous_position')
            ->orderBy('p.seed')
            ->get()
            ->keyBy('user_id')
        ;

        // Sort participants based on previous results
        $sorted = $participants->sort(function ($a, $b) use ($previousResults) {
            $aPrevious = $previousResults[$a->user_id]->previous_position ?? 999;
            $bPrevious = $previousResults[$b->user_id]->previous_position ?? 999;

            if ($aPrevious === $bPrevious) {
                // Use rating as tiebreaker
                return $b->rating_snapshot <=> $a->rating_snapshot;
            }

            return $aPrevious <=> $bPrevious;
        });

        // Assign seeds
        DB::transaction(static function () use ($sorted) {
            $seed = 1;
            foreach ($sorted as $participant) {
                $participant->update(['seed' => $seed]);
                $seed++;
            }
        });

        return $sorted->values();
    }

    /**
     * Reorder participants for optimal bracket placement
     *
     * @param  Collection  $seededParticipants  Participants with seeds assigned
     * @param  int  $bracketSize  Target bracket size
     * @return Collection Reordered participants
     */
    public function reorderForBracket(Collection $seededParticipants, int $bracketSize): Collection
    {
        $positions = $this->getBracketPositions($bracketSize);
        $reordered = collect();

        // Create array with bracket size
        $bracket = array_fill(0, $bracketSize, null);

        // Place seeded participants
        foreach ($seededParticipants as $participant) {
            $seed = $participant->seed;
            if (isset($positions[$seed])) {
                $bracket[$positions[$seed] - 1] = $participant;
            }
        }

        // Fill remaining spots with BYEs or unseeded
        $unseeded = $seededParticipants->filter(function ($p) use ($positions) {
            return !isset($positions[$p->seed]);
        });

        $unseededIndex = 0;
        for ($i = 0; $i < $bracketSize; $i++) {
            if ($bracket[$i] === null) {
                if ($unseededIndex < $unseeded->count()) {
                    $bracket[$i] = $unseeded->values()[$unseededIndex];
                    $unseededIndex++;
                }
                // else remains null (BYE)
            }
        }

        return collect($bracket);
    }

    /**
     * Get optimal bracket positions for seeded players
     * This ensures top seeds meet as late as possible
     *
     * @param  int  $bracketSize  Must be power of 2
     * @return array Mapping of seed => bracket position
     */
    public function getBracketPositions(int $bracketSize): array
    {
        if (!$this->isPowerOfTwo($bracketSize)) {
            throw new InvalidArgumentException('Bracket size must be a power of 2');
        }

        // For a standard tournament bracket, we want:
        // Seed 1 at position 1
        // Seed 2 at position bracketSize
        // Seeds 3-4 at positions bracketSize/2 and bracketSize/2 + 1
        // etc.

        $positions = [];
        $positions[1] = 1;
        $positions[2] = $bracketSize;

        $groups = 2;
        $seedCounter = 3;

        while ($seedCounter <= $bracketSize) {
            $groupSize = $bracketSize / $groups;

            for ($group = 0; $group < $groups; $group += 2) {
                $basePosition = $group * $groupSize + 1;

                // Place next seed at the end of even groups
                if ($group % 2 === 0) {
                    $positions[$seedCounter] = $basePosition + $groupSize - 1;
                } else {
                    $positions[$seedCounter] = $basePosition;
                }

                $seedCounter++;

                if ($seedCounter > $bracketSize) {
                    break;
                }
            }

            $groups *= 2;
        }

        return $positions;
    }

    /**
     * Check if number is power of two
     */
    protected function isPowerOfTwo(int $n): bool
    {
        return ($n & ($n - 1)) === 0 && $n > 0;
    }

    /**
     * Validate seeding before applying
     *
     * @param  Collection  $participants
     * @throws Exception if seeding is invalid
     */
    public function validateSeeding(Collection $participants): void
    {
        $seeds = $participants->pluck('seed')->sort()->values();

        // Check for duplicates
        if ($seeds->count() !== $seeds->unique()->count()) {
            throw new RuntimeException('Duplicate seeds found');
        }

        // Check for gaps
        $expected = range(1, $participants->count());
        if ($seeds->toArray() !== $expected) {
            throw new RuntimeException('Seeds must be sequential starting from 1');
        }
    }

    /**
     * Preview seeding result for groups
     *
     * @param  Collection  $participants  Seeded participants
     * @param  int  $groupCount  Number of groups
     * @return Collection Groups with participants
     */
    public function previewGroups(Collection $participants, int $groupCount): Collection
    {
        $groups = collect();

        for ($i = 0; $i < $groupCount; $i++) {
            $groups->push(collect([
                'name'         => 'Group '.chr(65 + $i), // A, B, C, D...
                'participants' => collect(),
            ]));
        }

        // Distribute using snake pattern
        $sorted = $participants->sortBy('seed');
        $direction = 1;
        $currentGroup = 0;

        foreach ($sorted as $participant) {
            $groups[$currentGroup]['participants']->push($participant);

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
}
