<?php

namespace App\Tournaments\Models;

use App\Core\Models\Club;
use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentMatch extends Model
{
    protected $fillable = [
        'tournament_id',
        'match_type',
        'round_number',
        'match_number',
        'bracket_type',
        'group_id',
        'participant_1_id',
        'participant_1_type',
        'participant_2_id',
        'participant_2_type',
        'status',
        'scores',
        'participant_1_score',
        'participant_2_score',
        'winner_id',
        'winner_type',
        'scheduled_at',
        'started_at',
        'completed_at',
        'table_number',
        'club_id',
        'notes',
        'referee',
        'match_data',
    ];

    protected $casts = [
        'scores'       => 'array',
        'scheduled_at' => 'datetime',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
        'match_data'   => 'array',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(TournamentGroup::class, 'group_id');
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * Get participant 1 (player or team)
     */
    public function participant1(): BelongsTo
    {
        if ($this->participant_1_type === 'team') {
            return $this->belongsTo(TournamentTeam::class, 'participant_1_id');
        }
        return $this->belongsTo(TournamentPlayer::class, 'participant_1_id');
    }

    /**
     * Get participant 2 (player or team)
     */
    public function participant2(): BelongsTo
    {
        if ($this->participant_2_type === 'team') {
            return $this->belongsTo(TournamentTeam::class, 'participant_2_id');
        }
        return $this->belongsTo(TournamentPlayer::class, 'participant_2_id');
    }

    /**
     * Get winner (player or team)
     */
    public function winner(): ?BelongsTo
    {
        if (!$this->winner_id) {
            return null;
        }

        if ($this->winner_type === 'team') {
            return $this->belongsTo(TournamentTeam::class, 'winner_id');
        }
        return $this->belongsTo(TournamentPlayer::class, 'winner_id');
    }

    /**
     * Check if match is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if match is in progress
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Check if match is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Get match display name
     */
    public function getDisplayName(): string
    {
        $participant1Name = $this->getParticipantName(1);
        $participant2Name = $this->getParticipantName(2);

        if ($this->match_type === 'group') {
            return "Group {$this->group?->name}: $participant1Name vs $participant2Name";
        }

        return "Round $this->round_number: $participant1Name vs $participant2Name";
    }

    /**
     * Get participant name by position
     */
    public function getParticipantName(int $position): string
    {
        $participantId = $position === 1 ? $this->participant_1_id : $this->participant_2_id;
        $participantType = $position === 1 ? $this->participant_1_type : $this->participant_2_type;

        if (!$participantId) {
            return 'TBD';
        }

        if ($participantType === 'team') {
            $team = TournamentTeam::find($participantId);
            return $team?->getDisplayName() ?? 'Unknown Team';
        }

        $player = TournamentPlayer::with('user')->find($participantId);
        if ($player && $player->user) {
            return $player->user->firstname.' '.$player->user->lastname;
        }

        return 'Unknown Player';
    }

    /**
     * Start the match
     */
    public function start(): void
    {
        $this->update([
            'status'     => 'in_progress',
            'started_at' => now(),
        ]);
    }

    /**
     * Complete the match with results
     */
    public function complete(array $result): void
    {
        $participant1Score = $result['participant_1_score'] ?? 0;
        $participant2Score = $result['participant_2_score'] ?? 0;

        // Determine winner
        $winnerId = null;
        $winnerType = null;

        if ($participant1Score > $participant2Score) {
            $winnerId = $this->participant_1_id;
            $winnerType = $this->participant_1_type;
        } elseif ($participant2Score > $participant1Score) {
            $winnerId = $this->participant_2_id;
            $winnerType = $this->participant_2_type;
        }

        $this->update([
            'status'              => 'completed',
            'participant_1_score' => $participant1Score,
            'participant_2_score' => $participant2Score,
            'winner_id'           => $winnerId,
            'winner_type'         => $winnerType,
            'scores'              => $result['scores'] ?? [],
            'completed_at'        => now(),
        ]);

        // Update group standings if this is a group match
        if ($this->match_type === 'group' && $this->group) {
            $this->group->updateStandings();
            $this->group->checkCompletion();
        }
    }

    /**
     * Cancel the match
     */
    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'notes' => $reason ? "Cancelled: $reason" : 'Cancelled',
        ]);
    }

    /**
     * Reschedule the match
     */
    public function reschedule(DateTime $newTime, ?int $tableNumber = null): void
    {
        $this->update([
            'scheduled_at' => $newTime,
            'table_number' => $tableNumber ?? $this->table_number,
        ]);
    }

    /**
     * Get match status display name
     */
    public function getStatusDisplay(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get match result summary
     */
    public function getResultSummary(): array
    {
        if (!$this->isCompleted()) {
            return [
                'status' => $this->status,
                'winner' => null,
                'score'  => null,
            ];
        }

        return [
            'status'          => 'completed',
            'winner'          => [
                'id'   => $this->winner_id,
                'type' => $this->winner_type,
                'name' => $this->winner_id ? $this->getParticipantName(
                    $this->winner_id === $this->participant_1_id ? 1 : 2,
                ) : null,
            ],
            'score'           => [
                'participant_1' => $this->participant_1_score,
                'participant_2' => $this->participant_2_score,
            ],
            'detailed_scores' => $this->scores,
        ];
    }
}
