<?php

namespace App\Tournaments\Models;

use App\Core\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentPlayer extends Model
{
    protected $fillable = [
        'tournament_id',
        'user_id',
        'position',
        'seed',
        'bracket_position',
        'group_id',
        'team_id',
        'team_role',
        'rating_points',
        'matches_played',
        'matches_won',
        'matches_lost',
        'games_won',
        'games_lost',
        'win_percentage',
        'prize_amount',
        'bonus_amount',
        'achievement_amount',
        'bracket_path',
        'group_standings',
        'status',
        'registered_at',
        'applied_at',
        'confirmed_at',
        'rejected_at',
    ];

    protected $casts = [
        'prize_amount'       => 'decimal:2',
        'bonus_amount'       => 'decimal:2',
        'achievement_amount' => 'decimal:2',
        'win_percentage'  => 'decimal:2',
        'bracket_path'    => 'array',
        'group_standings' => 'array',
        'registered_at'      => 'datetime',
        'applied_at'         => 'datetime',
        'confirmed_at'       => 'datetime',
        'rejected_at'        => 'datetime',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(TournamentGroup::class, 'group_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(TournamentTeam::class, 'team_id');
    }

    // Existing methods
    public function isWinner(): bool
    {
        return $this->position === 1;
    }

    public function isInTopThree(): bool
    {
        return $this->position && $this->position <= 3;
    }

    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status) {
            'applied' => 'Applied',
            'confirmed' => 'Confirmed',
            'rejected' => 'Rejected',
            'eliminated' => 'Eliminated',
            'dnf' => 'Did Not Finish',
            default => ucfirst($this->status),
        };
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isPending(): bool
    {
        return $this->status === 'applied';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function getTotalAmountAttribute(): float
    {
        return (float) ($this->prize_amount + $this->achievement_amount);
    }

    // New methods for advanced tournament management

    /**
     * Check if player is team captain
     */
    public function isCaptain(): bool
    {
        return $this->team_role === 'captain';
    }

    /**
     * Check if player is a substitute
     */
    public function isSubstitute(): bool
    {
        return $this->team_role === 'substitute';
    }

    /**
     * Get matches where this player participates
     */
    public function matches(): TournamentMatch
    {
        return TournamentMatch::where('tournament_id', $this->tournament_id)
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q
                        ->where('participant_1_id', $this->id)
                        ->where('participant_1_type', 'player')
                    ;
                })->orWhere(function ($q) {
                    $q
                        ->where('participant_2_id', $this->id)
                        ->where('participant_2_type', 'player')
                    ;
                });
            })
        ;
    }

    /**
     * Get completed matches
     */
    public function completedMatches(): TournamentMatch
    {
        return $this->matches()->where('status', 'completed');
    }

    /**
     * Get upcoming matches
     */
    public function upcomingMatches(): TournamentMatch
    {
        return $this->matches()->whereIn('status', ['pending', 'in_progress']);
    }

    /**
     * Update player statistics from match results
     */
    public function updateStatistics(): void
    {
        $matches = $this->completedMatches()->get();

        $stats = [
            'matches_played' => $matches->count(),
            'matches_won'    => 0,
            'matches_lost'   => 0,
            'games_won'      => 0,
            'games_lost'     => 0,
        ];

        foreach ($matches as $match) {
            $isParticipant1 = $match->participant_1_id === $this->id
                && $match->participant_1_type === 'player';

            if ($isParticipant1) {
                $stats['games_won'] += $match->participant_1_score;
                $stats['games_lost'] += $match->participant_2_score;

            } else {
                $stats['games_won'] += $match->participant_2_score;
                $stats['games_lost'] += $match->participant_1_score;

            }
            if ($match->winner_id === $this->id && $match->winner_type === 'player') {
                $stats['matches_won']++;
            } else {
                $stats['matches_lost']++;
            }
        }

        $stats['win_percentage'] = $stats['matches_played'] > 0
            ? round(($stats['matches_won'] / $stats['matches_played']) * 100, 2)
            : 0;

        $this->update($stats);
    }

    /**
     * Get player's current position in group standings
     */
    public function getGroupPosition(): ?int
    {
        if (!$this->group) {
            return null;
        }

        $standings = $this->group->getStandings();

        foreach ($standings as $standing) {
            if ($standing['participant_id'] === $this->id) {
                return $standing['position'];
            }
        }

        return null;
    }

    /**
     * Get player's group stage performance
     */
    public function getGroupPerformance(): array
    {
        if (!$this->group) {
            return [];
        }

        $groupMatches = $this
            ->matches()
            ->where('match_type', 'group')
            ->where('group_id', $this->group_id)
            ->get()
        ;

        $performance = [
            'matches_played' => $groupMatches->where('status', 'completed')->count(),
            'wins'           => 0,
            'losses'         => 0,
            'games_for'      => 0,
            'games_against'  => 0,
            'position'       => $this->getGroupPosition(),
        ];

        foreach ($groupMatches->where('status', 'completed') as $match) {
            $isParticipant1 = $match->participant_1_id === $this->id
                && $match->participant_1_type === 'player';

            if ($isParticipant1) {
                $performance['games_for'] += $match->participant_1_score;
                $performance['games_against'] += $match->participant_2_score;

            } else {
                $performance['games_for'] += $match->participant_2_score;
                $performance['games_against'] += $match->participant_1_score;

            }
            if ($match->winner_id === $this->id) {
                $performance['wins']++;
            } else {
                $performance['losses']++;
            }
        }

        $performance['games_difference'] = $performance['games_for'] - $performance['games_against'];

        return $performance;
    }

    /**
     * Check if player advances from group stage
     */
    public function advancesFromGroup(): bool
    {
        if (!$this->group) {
            return false;
        }

        $position = $this->getGroupPosition();
        return $position && $position <= $this->group->advance_count;
    }

    /**
     * Get bracket progression path
     */
    public function getBracketProgression(): array
    {
        $bracketMatches = $this
            ->matches()
            ->where('match_type', 'bracket')
            ->orderBy('round_number')
            ->get()
        ;

        $progression = [];

        foreach ($bracketMatches as $match) {
            $progression[] = [
                'round'    => $match->round_number,
                'match_id' => $match->id,
                'opponent' => $match->getParticipantName(
                    $match->participant_1_id === $this->id ? 2 : 1,
                ),
                'result'   => $match->getResultSummary(),
                'advanced' => $match->winner_id === $this->id,
            ];
        }

        return $progression;
    }

    /**
     * Get next match for this player
     */
    public function getNextMatch(): ?TournamentMatch
    {
        return $this
            ->upcomingMatches()
            ->orderBy('scheduled_at')
            ->orderBy('round_number')
            ->first()
        ;
    }

    /**
     * Get player's tournament summary
     */
    public function getTournamentSummary(): array
    {
        $summary = [
            'player_name'    => $this->user->firstname.' '.$this->user->lastname,
            'seed'           => $this->seed,
            'final_position' => $this->position,
            'status'         => $this->status,
            'statistics'     => [
                'matches_played' => $this->matches_played,
                'matches_won'    => $this->matches_won,
                'matches_lost'   => $this->matches_lost,
                'win_percentage' => $this->win_percentage,
                'games_won'      => $this->games_won,
                'games_lost'     => $this->games_lost,
            ],
            'rewards'        => [
                'rating_points'      => $this->rating_points,
                'prize_amount'       => $this->prize_amount,
                'bonus_amount'       => $this->bonus_amount,
                'achievement_amount' => $this->achievement_amount,
                'total_amount'       => $this->total_amount,
            ],
        ];

        if ($this->group && $this->tournament->hasGroups()) {
            $summary['group_performance'] = $this->getGroupPerformance();
        }

        if ($this->tournament->hasBrackets()) {
            $summary['bracket_progression'] = $this->getBracketProgression();
        }

        if ($this->team) {
            $summary['team'] = [
                'name' => $this->team->name,
                'role' => $this->team_role,
            ];
        }

        return $summary;
    }
}
