<?php

namespace App\OfficialTournaments\Models;

use App\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfficialParticipant extends Model
{
    use HasFactory;

    protected $table = 'official_participants';

    protected $fillable = [
        'stage_id',
        'user_id',
        'team_id',
        'seed',
        'rating_snapshot',
    ];

    protected $casts = [
        'seed'            => 'integer',
        'rating_snapshot' => 'integer',
    ];

    public function stage(): BelongsTo
    {
        return $this->belongsTo(OfficialStage::class, 'stage_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(OfficialTeam::class, 'team_id');
    }

    public function wonSets(): HasMany
    {
        return $this->hasMany(OfficialMatchSet::class, 'winner_participant_id');
    }

    /**
     * Get participant display name
     */
    public function getDisplayName(): string
    {
        if ($this->team) {
            return $this->team->name;
        }

        if ($this->user) {
            return $this->user->full_name ?? $this->user->name;
        }

        return 'BYE';
    }

    /**
     * Check if participant is a bye
     */
    public function isBye(): bool
    {
        return !$this->user_id && !$this->team_id;
    }

    /**
     * Get match statistics
     */
    public function getMatchStats(): array
    {
        $won = 0;
        $lost = 0;
        $setsWon = 0;
        $setsLost = 0;

        $matches = $this->matches()->with('matchSets')->get();

        foreach ($matches as $match) {
            if ($match->status !== 'finished') {
                continue;
            }

            $matchWon = $match->getWinnerId() === $this->id;

            if ($matchWon) {
                $won++;
            } else {
                $lost++;
            }

            foreach ($match->matchSets as $set) {
                if ($set->winner_participant_id === $this->id) {
                    $setsWon++;
                } else {
                    $setsLost++;
                }
            }
        }

        return [
            'matches_won'     => $won,
            'matches_lost'    => $lost,
            'sets_won'        => $setsWon,
            'sets_lost'       => $setsLost,
            'sets_difference' => $setsWon - $setsLost,
        ];
    }

    /**
     * Get all matches where this participant is involved
     */
    public function matches()
    {
        return OfficialMatch::query()
            ->where('stage_id', $this->stage_id)
            ->where(function ($query) {
                $query
                    ->whereHas('matchSets', function ($q) {
                        $q->where('winner_participant_id', $this->id);
                    })
                    ->orWhereJsonContains('metadata->participant1_id', $this->id)
                    ->orWhereJsonContains('metadata->participant2_id', $this->id)
                ;
            })
        ;
    }
}
