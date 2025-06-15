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
 * Service for generating and managing Double Elimination brackets
 *
 * Double Elimination gives each participant two chances - they must lose twice to be eliminated.
 * Winners bracket (W) progresses normally, losers drop to Losers bracket (L).
 */
class DoubleElimService
{
    /**
     * Generate double elimination bracket for given participants
     *
     * @param  OfficialStage  $stage  The stage to generate bracket for
     * @param  Collection  $participants  Collection of OfficialParticipant models
     * @return array Array of generated OfficialMatch instances
     * @throws Throwable
     */
    public function generateBracket(OfficialStage $stage, Collection $participants): array
    {
        $count = $participants->count();

        if ($count < 2) {
            throw new InvalidArgumentException('Need at least 2 participants for double elimination');
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

            // Generate Winners Bracket
            $winnerMatches = $this->generateWinnersBracket($stage, $bracketParticipants);
            $matches = array_merge($matches, $winnerMatches);

            // Generate Losers Bracket
            $loserMatches = $this->generateLosersBracket($stage, $bracketSize);
            $matches = array_merge($matches, $loserMatches);

            // Generate Grand Final matches
            $grandFinalMatches = $this->generateGrandFinal($stage, $bracketSize);
            $matches = array_merge($matches, $grandFinalMatches);

            DB::commit();
            return $matches;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get next power of two
     */
    protected function getNextPowerOfTwo(int $n): int
    {
        return 2 ** ceil(log($n, 2));
    }

    /**
     * Prepare bracket with BYEs
     */
    protected function prepareBracketWithByes(Collection $participants, int $byesNeeded): Collection
    {
        $result = collect();

        // Distribute BYEs evenly
        if ($byesNeeded > 0) {
            $totalSlots = $participants->count() + $byesNeeded;
            $byePositions = $this->calculateByePositions($totalSlots, $byesNeeded);

            $participantIndex = 0;
            for ($i = 0; $i < $totalSlots; $i++) {
                if (in_array($i, $byePositions, true)) {
                    // Create BYE participant
                    $bye = new OfficialParticipant();
                    $bye->id = -($i + 1); // Negative ID for BYEs
                    $result->push($bye);
                } else {
                    $result->push($participants[$participantIndex]);
                    $participantIndex++;
                }
            }
        } else {
            $result = $participants;
        }

        return $result;
    }

    /**
     * Calculate optimal BYE positions to avoid same-half clustering
     */
    protected function calculateByePositions(int $totalSlots, int $byesNeeded): array
    {
        $positions = [];
        $interval = $totalSlots / ($byesNeeded + 1);

        for ($i = 1; $i <= $byesNeeded; $i++) {
            $positions[] = (int) round($i * $interval) - 1;
        }

        return $positions;
    }

    /**
     * Generate Winners Bracket matches
     */
    protected function generateWinnersBracket(OfficialStage $stage, Collection $participants): array
    {
        $matches = [];
        $bracketSize = $participants->count();
        $totalRounds = (int) log($bracketSize, 2);

        // Round 1 - Initial matches
        $matchNumber = 1;
        $participantPairs = $participants->chunk(2);

        foreach ($participantPairs as $pair) {
            $players = $pair->values();

            $match = OfficialMatch::create([
                'stage_id' => $stage->id,
                'round'    => 1,
                'bracket'  => OfficialMatch::BRACKET_WINNER,
                'status'   => OfficialMatch::STATUS_PENDING,
                'metadata' => [
                    'match_number'      => $matchNumber,
                    'participant1_id'   => $players[0]?->id,
                    'participant2_id'   => $players[1]?->id ?? null,
                    'next_match_winner' => $this->calculateNextMatchNumber($matchNumber, $bracketSize, 'W'),
                    'next_match_loser'  => $this->calculateLoserDropPosition($matchNumber, 1, $bracketSize),
                ],
            ]);

            // Auto-advance if playing against BYE
            if ($players[0] && (!isset($players[1]) || $players[1]->isBye())) {
                $match->status = OfficialMatch::STATUS_WALKOVER;
                $match->save();
            }

            $matches[] = $match;
            $matchNumber++;
        }

        // Generate subsequent rounds
        for ($round = 2; $round <= $totalRounds; $round++) {
            $matchesInRound = $bracketSize / (2 ** $round);

            for ($i = 0; $i < $matchesInRound; $i++) {
                $match = OfficialMatch::create([
                    'stage_id' => $stage->id,
                    'round'    => $round,
                    'bracket'  => OfficialMatch::BRACKET_WINNER,
                    'status'   => OfficialMatch::STATUS_PENDING,
                    'metadata' => [
                        'match_number'      => $matchNumber,
                        'participant1_id'   => null, // Will be filled by winner progression
                        'participant2_id'   => null,
                        'next_match_winner' => $round < $totalRounds ?
                            $this->calculateNextMatchNumber($matchNumber, $bracketSize, 'W') :
                            'GF', // Grand Final
                        'next_match_loser'  => $this->calculateLoserDropPosition($matchNumber, $round, $bracketSize),
                    ],
                ]);

                $matches[] = $match;
                $matchNumber++;
            }
        }

        return $matches;
    }

    /**
     * Calculate next match number for winner progression
     */
    protected function calculateNextMatchNumber(int $currentMatch, int $bracketSize, string $bracket): int
    {
        if ($bracket === 'W') {
            $currentRound = $this->getRoundFromMatchNumber($currentMatch, $bracketSize);
            $positionInRound = $this->getPositionInRound($currentMatch, $bracketSize, $currentRound);

            $nextRoundFirstMatch = $this->getFirstMatchOfRound($currentRound + 1, $bracketSize);
            $nextPosition = ceil($positionInRound / 2);

            return $nextRoundFirstMatch + $nextPosition - 1;
        }

        // Complex calculation for losers bracket
        return $currentMatch + 1; // Simplified for now
    }

    /**
     * Get round number from match number
     */
    protected function getRoundFromMatchNumber(int $matchNumber, int $bracketSize): int
    {
        $round = 1;
        $matchesAccountedFor = 0;

        while ($matchesAccountedFor < $matchNumber) {
            $matchesInRound = $bracketSize / (2 ** $round);
            $matchesAccountedFor += $matchesInRound;

            if ($matchesAccountedFor >= $matchNumber) {
                return $round;
            }
            $round++;
        }

        return $round;
    }

    /**
     * Get position within round
     */
    protected function getPositionInRound(int $matchNumber, int $bracketSize, int $round): int
    {
        $matchesBefore = 0;

        for ($r = 1; $r < $round; $r++) {
            $matchesBefore += $bracketSize / (2 ** $r);
        }

        return $matchNumber - $matchesBefore;
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
     * Calculate where loser drops to in losers bracket
     */
    protected function calculateLoserDropPosition(int $matchNumber, int $round, int $bracketSize): string
    {
        // Round 1 losers go to L1, L2, etc.
        if ($round === 1) {
            return 'L'.ceil($matchNumber / 2);
        }

        // Later rounds have more complex drop positions
        // This is simplified - real implementation would need full mapping
        $loserRound = ($round - 1) * 2;
        $dropPosition = $this->getLoserBracketPosition($matchNumber, $round, $bracketSize);

        return 'L'.$dropPosition;
    }

    /**
     * Calculate loser bracket position mapping
     */
    protected function getLoserBracketPosition(int $winnerMatchNumber, int $round, int $bracketSize): int
    {
        // This would contain the full mapping logic
        // For now, simplified version
        return $winnerMatchNumber + $bracketSize;
    }

    /**
     * Generate Losers Bracket matches
     */
    protected function generateLosersBracket(OfficialStage $stage, int $bracketSize): array
    {
        $matches = [];
        $totalWinnerRounds = (int) log($bracketSize, 2);
        $totalLoserRounds = ($totalWinnerRounds - 1) * 2;

        // Track match numbering for losers bracket
        $loserMatchNumber = 1;

        for ($round = 1; $round <= $totalLoserRounds; $round++) {
            $isDropRound = $round % 2 === 1; // Odd rounds receive drops from winners bracket

            if ($round === 1) {
                // First round of losers - matches between R1 losers
                $matchesInRound = $bracketSize / 4;
            } else {
                // Calculate matches based on progression
                $effectiveRound = ceil($round / 2);
                $matchesInRound = $bracketSize / (2 ** ($effectiveRound + 1));
            }

            for ($i = 0; $i < $matchesInRound; $i++) {
                $metadata = [
                    'match_number'    => 'L'.$loserMatchNumber,
                    'participant1_id' => null,
                    'participant2_id' => null,
                    'is_drop_round'   => $isDropRound,
                ];

                // Determine next match
                if ($round < $totalLoserRounds) {
                    $metadata['next_match_winner'] = 'L'.($loserMatchNumber + $matchesInRound);
                } else {
                    $metadata['next_match_winner'] = 'GF'; // Losers bracket final goes to Grand Final
                }

                $match = OfficialMatch::create([
                    'stage_id' => $stage->id,
                    'round'    => $round,
                    'bracket'  => OfficialMatch::BRACKET_LOSER,
                    'status'   => OfficialMatch::STATUS_PENDING,
                    'metadata' => $metadata,
                ]);

                $matches[] = $match;
                $loserMatchNumber++;
            }
        }

        return $matches;
    }

    /**
     * Generate Grand Final matches (including potential reset)
     */
    protected function generateGrandFinal(OfficialStage $stage, int $bracketSize): array
    {
        $matches = [];

        // Grand Final
        $grandFinal = OfficialMatch::create([
            'stage_id' => $stage->id,
            'round'    => 999, // Special round number for finals
            'bracket'  => OfficialMatch::BRACKET_WINNER,
            'status'   => OfficialMatch::STATUS_PENDING,
            'metadata' => [
                'match_number'      => 'GF',
                'participant1_id'   => null, // Winner of Winners Bracket
                'participant2_id'   => null, // Winner of Losers Bracket
                'is_grand_final'    => true,
                'next_match_winner' => null,
                'next_match_loser'  => 'GF-RESET', // If loser bracket winner wins
            ],
        ]);
        $matches[] = $grandFinal;

        // Grand Final Reset (if needed)
        $grandFinalReset = OfficialMatch::create([
            'stage_id' => $stage->id,
            'round'    => 999,
            'bracket'  => OfficialMatch::BRACKET_WINNER,
            'status'   => OfficialMatch::STATUS_PENDING,
            'metadata' => [
                'match_number'         => 'GF-RESET',
                'participant1_id'      => null,
                'participant2_id'      => null,
                'is_grand_final_reset' => true,
                'is_conditional'       => true, // Only played if LB winner wins GF
            ],
        ]);
        $matches[] = $grandFinalReset;

        return $matches;
    }

    /**
     * Handle match completion and progression
     * @throws Exception
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

            // Progress winner
            if ($nextMatchWinner = $match->metadata['next_match_winner'] ?? null) {
                $this->progressParticipant($winner, $nextMatchWinner, $match->stage_id);
            }

            // Progress loser (to losers bracket)
            if ($nextMatchLoser = $match->metadata['next_match_loser'] ?? null) {
                if ($match->bracket === OfficialMatch::BRACKET_WINNER) {
                    $this->progressParticipant($loser, $nextMatchLoser, $match->stage_id);
                }
            }

            // Handle Grand Final special case
            if ($match->metadata['is_grand_final'] ?? false) {
                $this->handleGrandFinalResult($match, $winner);
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Progress participant to next match
     */
    protected function progressParticipant(
        OfficialParticipant $participant,
        string $nextMatchIdentifier,
        int $stageId,
    ): void {
        $nextMatch = OfficialMatch::where('stage_id', $stageId)
            ->whereJsonContains('metadata->match_number', $nextMatchIdentifier)
            ->first()
        ;

        if (!$nextMatch) {
            return;
        }

        $metadata = $nextMatch->metadata;

        // Assign to appropriate slot
        if (!$metadata['participant1_id']) {
            $metadata['participant1_id'] = $participant->id;
        } elseif (!$metadata['participant2_id']) {
            $metadata['participant2_id'] = $participant->id;
        }

        $nextMatch->metadata = $metadata;
        $nextMatch->save();
    }

    /**
     * Handle Grand Final result
     */
    protected function handleGrandFinalResult(OfficialMatch $grandFinal, OfficialParticipant $winner): void
    {
        $metadata = $grandFinal->metadata;

        // Check if winner came from losers bracket
        $winnerFromLosers = $this->isParticipantFromLosersBracket($winner, $grandFinal->stage_id);

        if ($winnerFromLosers) {
            // Activate Grand Final Reset
            $resetMatch = OfficialMatch::where('stage_id', $grandFinal->stage_id)
                ->whereJsonContains('metadata->match_number', 'GF-RESET')
                ->first()
            ;

            if ($resetMatch) {
                $resetMetadata = $resetMatch->metadata;
                $resetMetadata['participant1_id'] = $metadata['participant1_id'];
                $resetMetadata['participant2_id'] = $metadata['participant2_id'];
                $resetMetadata['is_active'] = true;
                $resetMatch->metadata = $resetMetadata;
                $resetMatch->save();
            }
        }
    }

    /**
     * Check if participant came through losers bracket
     */
    protected function isParticipantFromLosersBracket(OfficialParticipant $participant, int $stageId): bool
    {
        return OfficialMatch::where('stage_id', $stageId)
            ->where('bracket', OfficialMatch::BRACKET_LOSER)
            ->where(function ($query) use ($participant) {
                $query
                    ->whereJsonContains('metadata->participant1_id', $participant->id)
                    ->orWhereJsonContains('metadata->participant2_id', $participant->id)
                ;
            })
            ->exists()
        ;
    }
}
