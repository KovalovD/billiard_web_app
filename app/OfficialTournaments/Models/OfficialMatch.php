<?php

namespace App\OfficialTournaments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfficialMatch extends Model
{
    use HasFactory;

    const string STATUS_PENDING = 'pending';
    const string STATUS_ONGOING = 'ongoing';
    const string STATUS_FINISHED = 'finished';
    const string STATUS_WALKOVER = 'walkover';
    const string BRACKET_WINNER = 'W';
    const string BRACKET_LOSER = 'L';
    const string BRACKET_CONSOLATION = 'C';
    protected $table = 'official_matches';
    protected $fillable = [
        'stage_id',
        'round',
        'bracket',
        'scheduled_at',
        'table_id',
        'status',
        'metadata',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'metadata'     => 'array',
    ];

    public function stage(): BelongsTo
    {
        return $this->belongsTo(OfficialStage::class, 'stage_id');
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(OfficialPoolTable::class, 'table_id');
    }

    /**
     * Set participants
     */
    public function setParticipants(?OfficialParticipant $p1, ?OfficialParticipant $p2): void
    {
        $metadata = $this->metadata ?? [];
        $metadata['participant1_id'] = $p1?->id;
        $metadata['participant2_id'] = $p2?->id;
        $this->metadata = $metadata;
    }

    /**
     * Get loser participant ID
     */
    public function getLoserId(): ?int
    {
        $winnerId = $this->getWinnerId();

        if (!$winnerId) {
            return null;
        }

        $p1Id = $this->metadata['participant1_id'] ?? null;
        $p2Id = $this->metadata['participant2_id'] ?? null;

        return $winnerId === $p1Id ? $p2Id : $p1Id;
    }

    /**
     * Get winner participant ID
     */
    public function getWinnerId(): ?int
    {
        if ($this->status !== self::STATUS_FINISHED) {
            return null;
        }

        $p1Wins = $this->matchSets()->where('winner_participant_id', $this->metadata['participant1_id'] ?? 0)->count();
        $p2Wins = $this->matchSets()->where('winner_participant_id', $this->metadata['participant2_id'] ?? 0)->count();

        if ($p1Wins > $p2Wins) {
            return $this->metadata['participant1_id'];
        }

        if ($p2Wins > $p1Wins) {
            return $this->metadata['participant2_id'];
        }

        return null;
    }

    public function matchSets(): HasMany
    {
        return $this->hasMany(OfficialMatchSet::class, 'match_id');
    }

    /**
     * Get match score summary
     */
    public function getScoreSummary(): array
    {
        $p1Id = $this->metadata['participant1_id'] ?? 0;
        $p2Id = $this->metadata['participant2_id'] ?? 0;

        return [
            'participant1' => [
                'id'       => $p1Id,
                'sets_won' => $this->matchSets()->where('winner_participant_id', $p1Id)->count(),
            ],
            'participant2' => [
                'id'       => $p2Id,
                'sets_won' => $this->matchSets()->where('winner_participant_id', $p2Id)->count(),
            ],
        ];
    }

    /**
     * Check if match is ready for scheduling
     */
    public function isReadyForScheduling(): bool
    {
        return $this->canStart() && !$this->scheduled_at;
    }

    /**
     * Check if match can be started
     */
    public function canStart(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->getParticipant1()
            && $this->getParticipant2()
            && !$this->getParticipant1()->isBye()
            && !$this->getParticipant2()->isBye();
    }

    /**
     * Get participant 1
     */
    public function getParticipant1()
    {
        $id = $this->metadata['participant1_id'] ?? null;
        return $id ? OfficialParticipant::find($id) : null;
    }

    /**
     * Get participant 2
     */
    public function getParticipant2()
    {
        $id = $this->metadata['participant2_id'] ?? null;
        return $id ? OfficialParticipant::find($id) : null;
    }
}
