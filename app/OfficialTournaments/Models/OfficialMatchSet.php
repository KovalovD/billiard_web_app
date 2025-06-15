<?php

namespace App\OfficialTournaments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficialMatchSet extends Model
{
    use HasFactory;

    protected $table = 'official_match_sets';

    protected $fillable = [
        'match_id',
        'set_no',
        'winner_participant_id',
        'score_json',
    ];

    protected $casts = [
        'set_no'     => 'integer',
        'score_json' => 'array',
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(OfficialMatch::class, 'match_id');
    }

    public function winnerParticipant(): BelongsTo
    {
        return $this->belongsTo(OfficialParticipant::class, 'winner_participant_id');
    }

    /**
     * Get score for participant 1
     */
    public function getParticipant1Score(): ?int
    {
        return $this->score_json['participant1'] ?? null;
    }

    /**
     * Get score for participant 2
     */
    public function getParticipant2Score(): ?int
    {
        return $this->score_json['participant2'] ?? null;
    }

    /**
     * Set scores
     */
    public function setScores(int $p1Score, int $p2Score): void
    {
        $this->score_json = [
            'participant1' => $p1Score,
            'participant2' => $p2Score,
        ];
    }
}
