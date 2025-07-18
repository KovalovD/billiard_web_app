<?php

namespace App\Tournaments\Models;

use App\Core\Models\User;
use App\OfficialRatings\Models\OfficialRatingPlayer;
use App\Tournaments\Enums\EliminationRound;
use App\Tournaments\Enums\TournamentPlayerStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class TournamentPlayer extends Model
{
    protected $fillable = [
        'tournament_id',
        'user_id',
        'position',
        'seed_number',
        'group_code',
        'group_position',
        'group_wins',
        'group_losses',
        'group_games_diff',
        'elimination_round',
        'rating_points',
        'prize_amount',
        'bonus_amount',
        'achievement_amount',
        'status',
        'registered_at',
        'applied_at',
        'confirmed_at',
        'rejected_at',
        'tiebreaker_wins',
        'tiebreaker_games_diff',
        'temp_wins',
        'temp_games_diff',
    ];

    protected $casts = [
        'prize_amount'       => 'decimal:2',
        'bonus_amount'       => 'decimal:2',
        'achievement_amount' => 'decimal:2',
        'registered_at'      => 'datetime',
        'applied_at'         => 'datetime',
        'confirmed_at'       => 'datetime',
        'rejected_at'        => 'datetime',
        'position'           => 'integer',
        'seed_number'        => 'integer',
        'group_position'     => 'integer',
        'group_wins'         => 'integer',
        'group_losses'       => 'integer',
        'group_games_diff'   => 'integer',
        'rating_points'      => 'integer',
        'status'             => TournamentPlayerStatus::class,
        'elimination_round'  => EliminationRound::class,
    ];

    protected $with = ['tournament.officialRatings.players', 'user'];

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

    public function isConfirmed(): bool
    {
        return $this->status->value === 'confirmed';
    }

    public function isPending(): bool
    {
        return $this->status->value === 'applied';
    }

    public function isRejected(): bool
    {
        return $this->status->value === 'rejected';
    }

    public function getTotalAmountAttribute(): float
    {
        return (float) ($this->prize_amount + $this->achievement_amount);
    }

    public function getRating(?Collection $ratingPlayers): string
    {
        if (!$ratingPlayers) {
            return 0;
        }

        /** @var OfficialRatingPlayer $ratingPlayer */
        $ratingPlayer = $ratingPlayers->firstWhere('user_id', $this->user_id);

        return $ratingPlayer->rating_points ?? 0;
    }
}
