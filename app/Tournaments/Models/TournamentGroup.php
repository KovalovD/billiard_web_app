<?php

namespace App\Tournaments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentGroup extends Model
{
    protected $fillable = [
        'tournament_id',
        'name',
        'display_name',
        'group_number',
        'max_participants',
        'advance_count',
        'is_completed',
        'standings_cache',
    ];

    protected $casts = [
        'is_completed'    => 'boolean',
        'standings_cache' => 'array',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(TournamentPlayer::class, 'group_id');
    }

    public function teams(): HasMany
    {
        return $this->hasMany(TournamentTeam::class, 'group_id');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class, 'group_id');
    }

    /**
     * Get current standings for this group
     */
    public function getStandings(): array
    {
        if ($this->standings_cache) {
            return $this->standings_cache;
        }

        return $this->calculateStandings();
    }

    /**
     * Calculate current standings based on match results
     */
    public function calculateStandings(): array
    {
        $participants = $this->tournament->is_team_tournament
            ? $this->teams()->with('players')->get()
            : $this->players()->with('user')->get();

        $standings = [];

        foreach ($participants as $participant) {
            $matches = $this
                ->matches()
                ->where(function ($query) use ($participant) {
                    $participantId = $participant->id;
                    $participantType = $this->tournament->is_team_tournament ? 'team' : 'player';

                    $query->where(function ($q) use ($participantId, $participantType) {
                        $q
                            ->where('participant_1_id', $participantId)
                            ->where('participant_1_type', $participantType)
                        ;
                    })->orWhere(function ($q) use ($participantId, $participantType) {
                        $q
                            ->where('participant_2_id', $participantId)
                            ->where('participant_2_type', $participantType)
                        ;
                    });
                })
                ->where('status', 'completed')
                ->get()
            ;

            $stats = [
                'participant_id'   => $participant->id,
                'participant_name' => $this->tournament->is_team_tournament
                    ? $participant->name
                    : $participant->user->firstname.' '.$participant->user->lastname,
                'matches_played'   => $matches->count(),
                'wins'             => 0,
                'losses'           => 0,
                'games_for'        => 0,
                'games_against'    => 0,
                'points'           => 0, // Tournament-specific point system
            ];

            foreach ($matches as $match) {
                $isParticipant1 = $match->participant_1_id === $participant->id
                    && $match->participant_1_type === ($this->tournament->is_team_tournament ? 'team' : 'player');

                if ($isParticipant1) {
                    $stats['games_for'] += $match->participant_1_score;
                    $stats['games_against'] += $match->participant_2_score;

                    if ($match->winner_id === $participant->id) {
                        $stats['wins']++;
                        $stats['points'] += 3; // 3 points for a win
                    } else {
                        $stats['losses']++;
                    }
                } else {
                    $stats['games_for'] += $match->participant_2_score;
                    $stats['games_against'] += $match->participant_1_score;

                    if ($match->winner_id === $participant->id) {
                        $stats['wins']++;
                        $stats['points'] += 3;
                    } else {
                        $stats['losses']++;
                    }
                }
            }

            $stats['games_difference'] = $stats['games_for'] - $stats['games_against'];
            $stats['win_percentage'] = $stats['matches_played'] > 0
                ? round(($stats['wins'] / $stats['matches_played']) * 100, 2)
                : 0;

            $standings[] = $stats;
        }

        // Sort standings: Points DESC, Games Difference DESC, Games For DESC
        usort($standings, function ($a, $b) {
            if ($a['points'] !== $b['points']) {
                return $b['points'] - $a['points'];
            }
            if ($a['games_difference'] !== $b['games_difference']) {
                return $b['games_difference'] - $a['games_difference'];
            }
            return $b['games_for'] - $a['games_for'];
        });

        // Add position
        foreach ($standings as $index => &$standing) {
            $standing['position'] = $index + 1;
        }

        return $standings;
    }

    /**
     * Update and cache standings
     */
    public function updateStandings(): void
    {
        $standings = $this->calculateStandings();
        $this->update(['standings_cache' => $standings]);
    }

    /**
     * Get participants that advance from this group
     */
    public function getAdvancingParticipants(): array
    {
        $standings = $this->getStandings();
        return array_slice($standings, 0, $this->advance_count);
    }

    /**
     * Check if group stage is complete
     */
    public function checkCompletion(): bool
    {
        $totalMatches = $this->matches()->count();
        $completedMatches = $this->matches()->where('status', 'completed')->count();

        $isComplete = $totalMatches > 0 && $totalMatches === $completedMatches;

        if ($isComplete && !$this->is_completed) {
            $this->update(['is_completed' => true]);
            $this->updateStandings();
        }

        return $isComplete;
    }
}
