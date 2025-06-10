<?php

namespace App\Tournaments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentTeam extends Model
{
    protected $fillable = [
        'tournament_id',
        'name',
        'short_name',
        'seed',
        'group_id',
        'bracket_position',
        'is_active',
        'roster_data',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'roster_data' => 'array',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(TournamentGroup::class, 'group_id');
    }

    public function players(): HasMany
    {
        return $this->hasMany(TournamentPlayer::class, 'team_id');
    }

    public function captain(): HasMany
    {
        return $this->players()->where('team_role', 'captain');
    }

    public function activeMembers(): HasMany
    {
        return $this->players()->whereIn('team_role', ['captain', 'player']);
    }

    public function substitutes(): HasMany
    {
        return $this->players()->where('team_role', 'substitute');
    }

    /**
     * Get matches where this team participates
     */
    public function matches()
    {
        return TournamentMatch::where('tournament_id', $this->tournament_id)
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q
                        ->where('participant_1_id', $this->id)
                        ->where('participant_1_type', 'team')
                    ;
                })->orWhere(function ($q) {
                    $q
                        ->where('participant_2_id', $this->id)
                        ->where('participant_2_type', 'team')
                    ;
                });
            })
        ;
    }

    /**
     * Get team statistics
     */
    public function getStats(): array
    {
        $matches = $this->matches()->where('status', 'completed')->get();

        $stats = [
            'matches_played' => $matches->count(),
            'wins'           => 0,
            'losses'         => 0,
            'games_for'      => 0,
            'games_against'  => 0,
        ];

        foreach ($matches as $match) {
            $isParticipant1 = $match->participant_1_id === $this->id
                && $match->participant_1_type === 'team';

            if ($isParticipant1) {
                $stats['games_for'] += $match->participant_1_score;
                $stats['games_against'] += $match->participant_2_score;

                if ($match->winner_id === $this->id && $match->winner_type === 'team') {
                    $stats['wins']++;
                } else {
                    $stats['losses']++;
                }
            } else {
                $stats['games_for'] += $match->participant_2_score;
                $stats['games_against'] += $match->participant_1_score;

                if ($match->winner_id === $this->id && $match->winner_type === 'team') {
                    $stats['wins']++;
                } else {
                    $stats['losses']++;
                }
            }
        }

        $stats['games_difference'] = $stats['games_for'] - $stats['games_against'];
        $stats['win_percentage'] = $stats['matches_played'] > 0
            ? round(($stats['wins'] / $stats['matches_played']) * 100, 2)
            : 0;

        return $stats;
    }

    /**
     * Check if team has minimum required players
     */
    public function hasMinimumPlayers(): bool
    {
        $activeCount = $this->activeMembers()->count();
        return $activeCount >= ($this->tournament->team_size ?? 2);
    }

    /**
     * Check if team has a captain
     */
    public function hasCaptain(): bool
    {
        return $this->captain()->exists();
    }

    /**
     * Check if team is ready to compete
     */
    public function isReadyToCompete(): bool
    {
        return $this->is_active &&
            $this->hasMinimumPlayers() &&
            $this->hasCaptain();
    }

    /**
     * Get team display name
     */
    public function getDisplayName(): string
    {
        return $this->short_name ?: $this->name;
    }

    /**
     * Get team roster summary
     */
    public function getRosterSummary(): array
    {
        return [
            'total_players'       => $this->players()->count(),
            'active_players'      => $this->activeMembers()->count(),
            'substitutes'         => $this->substitutes()->count(),
            'captain'             => $this->captain()->with('user')->first(),
            'has_minimum_players' => $this->hasMinimumPlayers(),
            'is_ready'            => $this->isReadyToCompete(),
        ];
    }
}
