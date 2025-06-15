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
 * Service for generating and managing Single Elimination brackets
 */
class SingleElimService
{
    /**
     * Generate single elimination bracket
     *
     * @param  OfficialStage  $stage
     * @param  Collection  $participants
     * @param  bool  $includeThirdPlace  Include 3rd place match
     * @return array Generated matches
     * @throws Throwable
     */
    public function generateBracket(
        OfficialStage $stage,
        Collection $participants,
        bool $includeThirdPlace = false,
    ): array {
        $count = $participants->count();

        if ($count < 2) {
            throw new InvalidArgumentException('Need at least 2 participants for single elimination');
        }

        if ($count > 256) {
            throw new InvalidArgumentException('Maximum 256 participants supported');
        }

        // Calculate bracket size (next power of 2)
        $bracketSize = $this->getNextPowerOfTwo($count);
        $byesNeeded = $bracketSize - $count;

        // Prepare participants with BYEs
        $bracketParticipants = $this->prepareBracketWithByes($participants, $byesNeeded);

        DB::beginTransaction();
        try {
            $matches = [];
            $totalRounds = (int) log($bracketSize, 2);

            // Generate all rounds
            $matchNumber = 1;

            for ($round = 1; $round <= $totalRounds; $round++) {
                $matchesInRound = $bracketSize / (2 ** $round);
                $roundMatches = [];

                for ($i = 0; $i < $matchesInRound; $i++) {
                    $metadata = [
                        'match_number'      => $matchNumber,
                        'position_in_round' => $i + 1,
                    ];

                    // For first round, assign participants
                    if ($round === 1) {
                        $p1Index = $i * 2;
                        $p2Index = $i * 2 + 1;

                        $metadata['participant1_id'] = $bracketParticipants[$p1Index]?->id;
                        $metadata['participant2_id'] = $bracketParticipants[$p2Index]?->id;
                    } else {
                        // Will be filled by winner progression
                        $metadata['participant1_id'] = null;
                        $metadata['participant2_id'] = null;

                        // Track which matches feed into this one
                        $prevRoundStart = $this->getFirstMatchOfRound($round - 1, $bracketSize);
                        $feederMatch1 = $prevRoundStart + ($i * 2);
                        $feederMatch2 = $feederMatch1 + 1;

                        $metadata['feeder_matches'] = [$feederMatch1, $feederMatch2];
                    }

                    // Determine next match (except for final)
                    if ($round < $totalRounds) {
                        $metadata['next_match'] = $this->calculateNextMatch($matchNumber, $round, $bracketSize);
                    } else {
                        $metadata['is_final'] = true;
                    }

                    // Track if this is semi-final (for 3rd place match)
                    if ($round === $totalRounds - 1) {
                        $metadata['is_semifinal'] = true;
                    }

                    $match = OfficialMatch::create([
                        'stage_id' => $stage->id,
                        'round'    => $round,
                        'bracket'  => OfficialMatch::BRACKET_WINNER,
                        'status'   => OfficialMatch::STATUS_PENDING,
                        'metadata' => $metadata,
                    ]);

                    // Auto-advance if BYE
                    if ($round === 1 && $this->shouldAutoAdvance($match)) {
                        $this->handleByeAdvancement($match);
                    }

                    $matches[] = $match;
                    $matchNumber++;
                }
            }

            // Generate 3rd place match if requested
            if ($includeThirdPlace && $totalRounds > 1) {
                $thirdPlaceMatch = $this->generateThirdPlaceMatch($stage, $totalRounds);
                $matches[] = $thirdPlaceMatch;
            }

            DB::commit();
            return $matches;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Calculate next power of 2
     */
    protected function getNextPowerOfTwo(int $n): int
    {
        return 2 ** ceil(log($n, 2));
    }

    /**
     * Prepare bracket with BYEs in optimal positions
     */
    protected function prepareBracketWithByes(Collection $participants, int $byesNeeded): array
    {
        if ($byesNeeded === 0) {
            return $participants->values()->toArray();
        }

        $totalSlots = $participants->count() + $byesNeeded;
        $bracket = array_fill(0, $totalSlots, null);

        // Place seeded players first
        $seeded = $participants->sortBy('seed');
        $byePositions = $this->calculateOptimalByePositions($totalSlots, $byesNeeded);

        $participantIndex = 0;
        for ($i = 0; $i < $totalSlots; $i++) {
            if (in_array($i, $byePositions, true)) {
                // BYE position - leave null
                continue;
            }

            if ($participantIndex < $seeded->count()) {
                $bracket[$i] = $seeded->values()[$participantIndex];
                $participantIndex++;
            }
        }

        return $bracket;
    }

    /**
     * Calculate optimal BYE positions to give them to lower seeds
     */
    protected function calculateOptimalByePositions(int $totalSlots, int $byesNeeded): array
    {
        $positions = [];

        // Give BYEs to highest seeds (they play against BYEs)
        // In standard seeding, seed 1 is at position 0, seed 2 at position totalSlots-1, etc.

        // Place BYEs next to top seeds
        $positions[] = 1; // Next to seed 1
        if ($byesNeeded > 1) {
            $positions[] = $totalSlots - 2; // Next to seed 2
        }

        // Continue pattern for remaining BYEs
        $seedPosition = 3;
        while (count($positions) < $byesNeeded) {
            // Calculate where seed N would be placed
            $position = $this->getSeedPosition($seedPosition, $totalSlots);
            if ($position !== null) {
                // Place BYE next to this seed
                $positions[] = $position + 1;
            }
            $seedPosition++;
        }

        return $positions;
    }

    /**
     * Get position of a seed in the bracket
     */
    protected function getSeedPosition(int $seed, int $bracketSize): ?int
    {
        // This implements standard tournament seeding positions
        // Simplified version - in reality would use complete seeding algorithm

        if ($seed === 1) {
            return 0;
        }
        if ($seed === 2) {
            return $bracketSize - 1;
        }
        if ($seed === 3) {
            return $bracketSize / 2 - 1;
        }
        if ($seed === 4) {
            return $bracketSize / 2;
        }

        // For other seeds, distribute evenly
        return null;
    }

    /**
     * Get first match number of a round
     */
    protected function getFirstMatchOfRound(int $round, int $bracketSize): int
    {
        $matchNumber = 1;

        for ($r = 1; $r < $round; $r++) {
            $matchNumber += $bracketSize / (2 ** $r);
        }

        return $matchNumber;
    }

    /**
     * Calculate which match winner advances to
     */
    protected function calculateNextMatch(int $currentMatch, int $currentRound, int $bracketSize): int
    {
        $firstMatchOfRound = $this->getFirstMatchOfRound($currentRound, $bracketSize);
        $positionInRound = $currentMatch - $firstMatchOfRound;

        $firstMatchOfNextRound = $this->getFirstMatchOfRound($currentRound + 1, $bracketSize);
        $nextMatchPosition = floor($positionInRound / 2);

        return $firstMatchOfNextRound + $nextMatchPosition;
    }

    /**
     * Check if match should auto-advance (BYE)
     */
    protected function shouldAutoAdvance(OfficialMatch $match): bool
    {
        $p1 = $match->getParticipant1();
        $p2 = $match->getParticipant2();

        // Auto-advance if one participant is null (BYE) or is marked as BYE
        return ($p1 && !$p2) || (!$p1 && $p2) ||
            ($p1 && $p1->isBye()) || ($p2 && $p2->isBye());
    }

    /**
     * Handle automatic advancement for BYE
     */
    protected function handleByeAdvancement(OfficialMatch $match): void
    {
        $p1 = $match->getParticipant1();
        $p2 = $match->getParticipant2();

        $winner = null;
        if ($p1 && (!$p2 || $p2->isBye())) {
            $winner = $p1;
        } elseif ($p2 && (!$p1 || $p1->isBye())) {
            $winner = $p2;
        }

        if ($winner) {
            $match->status = OfficialMatch::STATUS_WALKOVER;
            $match->save();

            if ($nextMatch = $match->metadata['next_match'] ?? null) {
                $this->progressWinner($winner, $nextMatch, $match->stage_id);
            }
        }
    }

    /**
     * Progress winner to next match
     */
    protected function progressWinner(OfficialParticipant $winner, int $nextMatchNumber, int $stageId): void
    {
        $nextMatch = OfficialMatch::where('stage_id', $stageId)
            ->whereJsonContains('metadata->match_number', $nextMatchNumber)
            ->first()
        ;

        if (!$nextMatch) {
            return;
        }

        $metadata = $nextMatch->metadata;

        // Determine which slot to fill based on feeder matches
        if (isset($metadata['feeder_matches'])) {
            // Find which feeder match just completed
            $completedMatch = OfficialMatch::where('stage_id', $stageId)
                ->whereJsonContains('metadata->next_match', $nextMatchNumber)
                ->where('status', OfficialMatch::STATUS_FINISHED)
                ->orderBy('updated_at', 'desc')
                ->first()
            ;

            if ($completedMatch) {
                $completedMatchNumber = $completedMatch->metadata['match_number'];

                if ($completedMatchNumber === $metadata['feeder_matches'][0]) {
                    $metadata['participant1_id'] = $winner->id;
                } else {
                    $metadata['participant2_id'] = $winner->id;
                }
            }
        } else {
            // Fallback: fill first empty slot
            if (!$metadata['participant1_id']) {
                $metadata['participant1_id'] = $winner->id;
            } else {
                $metadata['participant2_id'] = $winner->id;
            }
        }

        $nextMatch->metadata = $metadata;
        $nextMatch->save();

        // Check if next match should auto-advance (in case of another BYE)
        if ($this->shouldAutoAdvance($nextMatch)) {
            $this->handleByeAdvancement($nextMatch);
        }
    }

    /**
     * Generate 3rd place match
     */
    protected function generateThirdPlaceMatch(OfficialStage $stage, int $totalRounds): OfficialMatch
    {
        return OfficialMatch::create([
            'stage_id' => $stage->id,
            'round'    => $totalRounds, // Same round as final
            'bracket'  => OfficialMatch::BRACKET_CONSOLATION,
            'status'   => OfficialMatch::STATUS_PENDING,
            'metadata' => [
                'is_third_place'  => true,
                'participant1_id' => null, // Loser of semifinal 1
                'participant2_id' => null, // Loser of semifinal 2
            ],
        ]);
    }

    /**
     * Handle match completion and progression
     *
     * @param  OfficialMatch  $match
     * @param  OfficialParticipant  $winner
     * @param  OfficialParticipant  $loser
     * @return void
     * @throws Throwable
     */
    public function handleMatchCompletion(
        OfficialMatch $match,
        OfficialParticipant $winner,
        OfficialParticipant $loser,
    ): void {
        DB::beginTransaction();
        try {
            // Update match status
            $match->status = OfficialMatch::STATUS_FINISHED;
            $match->save();

            // Progress winner to next match
            if ($nextMatchNumber = $match->metadata['next_match'] ?? null) {
                $this->progressWinner($winner, $nextMatchNumber, $match->stage_id);
            }

            // Handle 3rd place match qualification
            if ($match->metadata['is_semifinal'] ?? false) {
                $this->qualifyForThirdPlace($loser, $match->stage_id);
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Qualify loser for 3rd place match
     */
    protected function qualifyForThirdPlace(OfficialParticipant $loser, int $stageId): void
    {
        $thirdPlace = OfficialMatch::where('stage_id', $stageId)
            ->where('bracket', OfficialMatch::BRACKET_CONSOLATION)
            ->whereJsonContains('metadata->is_third_place', true)
            ->first()
        ;

        if (!$thirdPlace) {
            return;
        }

        $metadata = $thirdPlace->metadata;

        if (!$metadata['participant1_id']) {
            $metadata['participant1_id'] = $loser->id;
        } else {
            $metadata['participant2_id'] = $loser->id;
        }

        $thirdPlace->metadata = $metadata;
        $thirdPlace->save();
    }

    /**
     * Get bracket visualization data
     *
     * @param  OfficialStage  $stage
     * @return array
     */
    public function getBracketVisualizationData(OfficialStage $stage): array
    {
        $matches = $stage
            ->matches()
            ->where('bracket', OfficialMatch::BRACKET_WINNER)
            ->orderBy('round')
            ->orderBy('id')
            ->get()
        ;

        $rounds = [];
        $maxRound = $matches->max('round');

        for ($round = 1; $round <= $maxRound; $round++) {
            $roundMatches = $matches->where('round', $round)->values();

            $rounds[] = [
                'round'   => $round,
                'name'    => $this->getRoundName($round, $maxRound),
                'matches' => $roundMatches->map(function ($match) {
                    return [
                        'id'           => $match->id,
                        'match_number' => $match->metadata['match_number'] ?? null,
                        'participant1' => $match->getParticipant1(),
                        'participant2' => $match->getParticipant2(),
                        'winner_id'    => $match->getWinnerId(),
                        'status'       => $match->status,
                        'scheduled_at' => $match->scheduled_at,
                        'table'        => $match->table,
                    ];
                }),
            ];
        }

        // Add 3rd place match if exists
        $thirdPlace = $stage
            ->matches()
            ->where('bracket', OfficialMatch::BRACKET_CONSOLATION)
            ->first()
        ;

        return [
            'rounds'             => $rounds,
            'third_place_match'  => $thirdPlace,
            'total_rounds'       => $maxRound,
            'participants_count' => $stage->participants->count(),
        ];
    }

    /**
     * Get round name
     */
    protected function getRoundName(int $round, int $totalRounds): string
    {
        $roundsFromEnd = $totalRounds - $round;

        return match ($roundsFromEnd) {
            0 => 'Final',
            1 => 'Semi-finals',
            2 => 'Quarter-finals',
            3 => 'Round of 16',
            4 => 'Round of 32',
            5 => 'Round of 64',
            6 => 'Round of 128',
            default => "Round {$round}",
        };
    }
}
