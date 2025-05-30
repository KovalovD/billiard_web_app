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
        'rating_points',
        'prize_amount',
        'status',
        'registered_at',
        'applied_at',
        'confirmed_at',
        'rejected_at',
    ];

    protected $casts = [
        'prize_amount'  => 'decimal:2',
        'registered_at' => 'datetime',
        'applied_at'   => 'datetime',
        'confirmed_at' => 'datetime',
        'rejected_at'  => 'datetime',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

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
}
