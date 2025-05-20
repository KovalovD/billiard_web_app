<?php

namespace App\Matches\Models;

use App\Core\Models\Game;
use App\Core\Models\User;
use App\Leagues\Models\League;
use Database\Factories\MultiplayerGameFactory;
use Faker\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MultiplayerGame extends Model
{
    use HasFactory;

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
        'current_player_id',
        'next_turn_order',
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
        if ($this->status !== 'in_progress' || !$this->current_player_id) {
            return null;
        }

        $activePlayers = $this->activePlayers()->orderBy('turn_order')->get();
        if ($activePlayers->isEmpty()) {
            return null;
        }

        // Find the index of the current player
        $currentPlayerIndex = $activePlayers->search(function ($player) {
            return $player->user_id === $this->current_player_id;
        });

        // If current player not found (perhaps they were eliminated),
        // just return the first player's index
        if ($currentPlayerIndex === false) {
            return 0;
        }

        return $currentPlayerIndex;
    }
    public function isUserModerator(User $user): bool
    {
        return $user->id === $this->moderator_user_id || $user->is_admin;
    }

    protected static function newFactory(): MultiplayerGameFactory|Factory
    {
        return MultiplayerGameFactory::new();
    }
}
