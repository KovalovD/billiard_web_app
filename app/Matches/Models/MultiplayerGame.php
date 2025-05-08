<?php

namespace App\Matches\Models;

use App\Core\Models\Game;
use App\Core\Models\User;
use App\Leagues\Models\League;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MultiplayerGame extends Model
{
    protected $fillable = [
        'league_id',
        'game_id',
        'name',
        'status',
        'initial_lives',
        'max_players',
        'registration_ends_at',
        'started_at',
        'completed_at',
        'moderator_user_id',
        'allow_player_targeting',
        'entrance_fee',
        'first_place_percent',
        'second_place_percent',
        'grand_final_percent',
        'penalty_fee',
        'prize_pool',
    ];

    protected $casts = [
        'registration_ends_at' => 'datetime',
        'started_at'           => 'datetime',
        'completed_at'         => 'datetime',
        'allow_player_targeting' => 'boolean',
        'prize_pool' => 'array',
    ];

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(MultiplayerGamePlayer::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(MultiplayerGameLog::class);
    }

    public function activePlayers(): HasMany
    {
        return $this->players()->whereNull('eliminated_at');
    }

    public function canJoin(User $user): bool
    {
        if (!$this->isRegistrationOpen()) {
            return false;
        }

        $existingPlayer = $this->players()->where('user_id', $user->id)->exists();
        return !$existingPlayer;
    }

    public function isRegistrationOpen(): bool
    {
        if ($this->status !== 'registration') {
            return false;
        }

        if ($this->registration_ends_at && $this->registration_ends_at < now()) {
            return false;
        }

        if ($this->max_players && $this->players()->count() >= $this->max_players) {
            return false;
        }

        return true;
    }

    public function getCurrentTurnPlayerIndex(): ?int
    {
        if ($this->status !== 'in_progress') {
            return null;
        }

        $activePlayers = $this->activePlayers()->orderBy('turn_order')->get();
        if ($activePlayers->isEmpty()) {
            return null;
        }

        // Get the latest log to find out whose turn it was last
        $lastLog = $this
            ->logs()
            ->where('action_type', 'turn')
            ->orderBy('created_at', 'desc')
            ->first()
        ;

        if (!$lastLog) {
            // First turn, return the first player
            return 0;
        }

        // Find the index of the player who took the last turn
        $lastPlayerIndex = $activePlayers->search(function ($player) use ($lastLog) {
            return $player->user_id === $lastLog->user_id;
        });

        if ($lastPlayerIndex === false) {
            // If player not found (was eliminated), return the first player
            return 0;
        }

        // Return the next player index
        return ($lastPlayerIndex + 1) % $activePlayers->count();
    }

    public function isUserModerator(User $user): bool
    {
        return $user->id === $this->moderator_user_id || $user->is_admin;
    }
}
