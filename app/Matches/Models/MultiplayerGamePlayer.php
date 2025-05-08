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
    ];

    protected $with = [
        'multiplayerGame',
    ];

    protected $casts = [
        'cards'         => 'array',
        'joined_at'     => 'datetime',
        'eliminated_at' => 'datetime',
        'penalty_paid' => 'boolean',
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

    public function decrementLives(): void
    {
        $this->decrement('lives');

        if ($this->lives <= 0) {
            $this->multiplayerGame->eliminatePlayer($this);

            // Check if game should be completed (only one active player left)
            $activePlayersCount = $this->multiplayerGame->activePlayers()->count();
            if ($activePlayersCount === 1) {
                // Set the winner (last remaining player)
                $winner = $this->multiplayerGame->activePlayers()->first();
                if ($winner) {
                    $winner->update([
                        'finish_position' => 1,
                        'eliminated_at' => now(),
                    ]);
                }

                // Mark game as completed
                $this->multiplayerGame->update([
                    'status'       => 'completed',
                    'completed_at' => now(),
                ]);

                // Calculate prizes and rating points
                $this->multiplayerGame->calculatePrizes();
                $this->multiplayerGame->calculateRatingPoints();
            }
        }
    }

    public function incrementLives(): void
    {
        $this->increment('lives');
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
