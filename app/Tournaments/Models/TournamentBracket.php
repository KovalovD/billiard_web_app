<?php

namespace App\Tournaments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentBracket extends Model
{
    protected $fillable = [
        'tournament_id',
        'bracket_type',
        'total_rounds',
        'total_participants',
        'is_active',
        'bracket_structure',
        'participant_positions',
        'advancement_rules',
        'current_round',
        'is_completed',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'is_active'             => 'boolean',
        'bracket_structure'     => 'array',
        'participant_positions' => 'array',
        'advancement_rules'     => 'array',
        'is_completed'          => 'boolean',
        'started_at'            => 'datetime',
        'completed_at'          => 'datetime',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function matches(): HasMany
    {
        return $this
            ->hasMany(TournamentMatch::class, 'tournament_id', 'tournament_id')
            ->where('bracket_type', $this->bracket_type)
        ;
    }

    /**
     * Initialize bracket structure for single elimination
     */
    public function initializeSingleElimination(array $participants): void
    {
        $participantCount = count($participants);
        $rounds = ceil(log($participantCount, 2));

        // Create bracket structure
        $structure = [];
        $currentRoundParticipants = $participantCount;

        for ($round = 1; $round <= $rounds; $round++) {
            $matchesInRound = ceil($currentRoundParticipants / 2);
            $structure[$round] = [
                'round_number'  => $round,
                'matches_count' => $matchesInRound,
                'is_completed'  => false,
            ];
            $currentRoundParticipants = $matchesInRound;
        }

        // Set participant positions
        $positions = [];
        foreach ($participants as $index => $participant) {
            $positions[$index + 1] = [
                'participant_id'   => $participant['id'],
                'participant_type' => $participant['type'],
                'seed'             => $participant['seed'] ?? $index + 1,
            ];
        }

        $this->update([
            'total_rounds'          => $rounds,
            'bracket_structure'     => $structure,
            'participant_positions' => $positions,
        ]);

        // Create first round matches
        $this->createFirstRoundMatches();
    }

    /**
     * Initialize bracket structure for double elimination
     */
    public function initializeDoubleElimination(array $participants): void
    {
        $participantCount = count($participants);
        $upperRounds = ceil(log($participantCount, 2));
        $lowerRounds = ($upperRounds - 1) * 2;

        $structure = [
            'upper_bracket' => [],
            'lower_bracket' => [],
        ];

        // Upper bracket
        $currentParticipants = $participantCount;
        for ($round = 1; $round <= $upperRounds; $round++) {
            $matchesInRound = ceil($currentParticipants / 2);
            $structure['upper_bracket'][$round] = [
                'round_number'  => $round,
                'matches_count' => $matchesInRound,
                'is_completed'  => false,
            ];
            $currentParticipants = $matchesInRound;
        }

        // Lower bracket
        for ($round = 1; $round <= $lowerRounds; $round++) {
            $structure['lower_bracket'][$round] = [
                'round_number'  => $round,
                'matches_count' => $this->calculateLowerBracketMatches($round, $participantCount),
                'is_completed'  => false,
            ];
        }

        // Finals
        $structure['finals'] = [
            'grand_final'       => ['matches_count' => 1, 'is_completed' => false],
            'grand_final_reset' => ['matches_count' => 1, 'is_completed' => false, 'conditional' => true],
        ];

        $this->update([
            'total_rounds'      => $upperRounds + $lowerRounds + 2,
            'bracket_structure' => $structure,
        ]);
    }

    /**
     * Create first round matches
     */
    private function createFirstRoundMatches(): void
    {
        $positions = $this->participant_positions ?? [];
        $positionKeys = array_keys($positions);

        // Pair participants for first round
        $matchNumber = 1;
        for ($i = 0, $iMax = count($positionKeys); $i < $iMax; $i += 2) {
            $participant1 = $positions[$positionKeys[$i]] ?? null;
            $participant2 = $positions[$positionKeys[$i + 1]] ?? null;

            if ($participant1 && $participant2) {
                TournamentMatch::create([
                    'tournament_id'      => $this->tournament_id,
                    'match_type'         => 'bracket',
                    'round_number'       => 1,
                    'match_number'       => $matchNumber,
                    'bracket_type'       => $this->bracket_type,
                    'participant_1_id'   => $participant1['participant_id'],
                    'participant_1_type' => $participant1['participant_type'],
                    'participant_2_id'   => $participant2['participant_id'],
                    'participant_2_type' => $participant2['participant_type'],
                    'status'             => 'pending',
                ]);

                $matchNumber++;
            }
        }
    }

    /**
     * Advance participants to next round
     */
    public function advanceToNextRound(int $round): void
    {
        $roundMatches = $this
            ->matches()
            ->where('round_number', $round)
            ->where('status', 'completed')
            ->get()
        ;

        if ($roundMatches->count() === 0) {
            return;
        }

        $nextRound = $round + 1;
        $structure = $this->bracket_structure;

        // Check if this is the final round
        if ($nextRound > $this->total_rounds) {
            $this->complete();
            return;
        }

        // Create next round matches
        $matchNumber = 1;
        $winners = [];

        foreach ($roundMatches as $match) {
            if ($match->winner_id) {
                $winners[] = [
                    'id'   => $match->winner_id,
                    'type' => $match->winner_type,
                ];
            }
        }

        // Pair winners for next round
        for ($i = 0, $iMax = count($winners); $i < $iMax; $i += 2) {
            $participant1 = $winners[$i] ?? null;
            $participant2 = $winners[$i + 1] ?? null;

            if ($participant1 && $participant2) {
                TournamentMatch::create([
                    'tournament_id'      => $this->tournament_id,
                    'match_type'         => 'bracket',
                    'round_number'       => $nextRound,
                    'match_number'       => $matchNumber,
                    'bracket_type'       => $this->bracket_type,
                    'participant_1_id'   => $participant1['id'],
                    'participant_1_type' => $participant1['type'],
                    'participant_2_id'   => $participant2['id'],
                    'participant_2_type' => $participant2['type'],
                    'status'             => 'pending',
                ]);

                $matchNumber++;
            }
        }

        // Update bracket structure
        $structure[$round]['is_completed'] = true;
        $this->update([
            'bracket_structure' => $structure,
            'current_round'     => $nextRound,
        ]);
    }

    /**
     * Check if current round is complete
     */
    public function isRoundComplete(int $round): bool
    {
        $roundMatches = $this->matches()->where('round_number', $round)->get();
        $completedMatches = $roundMatches->where('status', 'completed');

        return $roundMatches->count() > 0 && $roundMatches->count() === $completedMatches->count();
    }

    /**
     * Complete the bracket
     */
    public function complete(): void
    {
        $this->update([
            'is_completed' => true,
            'completed_at' => now(),
        ]);
    }

    /**
     * Get bracket champion
     */
    public function getChampion(): ?array
    {
        if (!$this->is_completed) {
            return null;
        }

        $finalMatch = $this
            ->matches()
            ->where('round_number', $this->total_rounds)
            ->where('status', 'completed')
            ->first()
        ;

        if (!$finalMatch || !$finalMatch->winner_id) {
            return null;
        }

        return [
            'id'   => $finalMatch->winner_id,
            'type' => $finalMatch->winner_type,
            'name' => $finalMatch->getParticipantName(
                $finalMatch->winner_id === $finalMatch->participant_1_id ? 1 : 2,
            ),
        ];
    }

    /**
     * Calculate matches for lower bracket round in double elimination
     */
    private function calculateLowerBracketMatches(int $round, int $totalParticipants): int
    {
        // Complex calculation for double elimination lower bracket
        // This is a simplified version - real implementation would be more complex
        $upperRounds = ceil(log($totalParticipants, 2));

        if ($round <= $upperRounds - 1) {
            return ceil($totalParticipants / (2 ** ($round + 1)));
        }

        return 1; // Simplified
    }

    /**
     * Get current bracket status
     */
    public function getStatus(): array
    {
        return [
            'bracket_type'  => $this->bracket_type,
            'current_round' => $this->current_round,
            'total_rounds'  => $this->total_rounds,
            'is_completed'  => $this->is_completed,
            'champion'      => $this->getChampion(),
        ];
    }
}
