<?php

namespace App\Matches\Models;

use App\Core\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MultiplayerGamePlayer extends Model
{
    protected $fillable = [
        'multiplayer_game_id',
        'user_id',
        'lives',
        'turn_order',
        'finish_position',
        'cards',
        'joined_at',
        'eliminated_at',
        'rating_points',
        'prize_amount',
        'penalty_paid',
        'rebuy_count',
        'rounds_played',
        'total_paid',
        'game_stats',
        'is_rebuy',
        'last_rebuy_at',
    ];

    protected $casts = [
        'cards'         => 'array',
        'joined_at'     => 'datetime',
        'eliminated_at' => 'datetime',
        'penalty_paid'  => 'boolean',
        'is_rebuy'      => 'boolean',
        'last_rebuy_at' => 'datetime',
        'game_stats'    => 'array',
        'total_paid'    => 'decimal:2',
    ];

    protected $with = [
        'multiplayerGame',
    ];


    public function multiplayerGame(): BelongsTo
    {
        return $this->belongsTo(MultiplayerGame::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function useCard(string $cardType): bool
    {
        if (!$this->hasCard($cardType)) {
            return false;
        }

        $cards = $this->cards;
        $cards[$cardType] = false;
        $this->update(['cards' => $cards]);

        return true;
    }

    public function hasCard(string $cardType): bool
    {
        return isset($this->cards[$cardType]) && $this->cards[$cardType] === true;
    }

    /**
     * Get total time fund contribution the player has to pay
     */
    public function getTimeFundContribution(): int
    {
        // Only players with penalty_paid true have to pay
        if (!$this->penalty_paid) {
            return 0;
        }

        return $this->multiplayerGame->penalty_fee ?? 50;
    }
}
