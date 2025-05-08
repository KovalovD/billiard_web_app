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
    ];

    protected $casts = [
        'registration_ends_at' => 'datetime',
        'started_at'           => 'datetime',
        'completed_at'         => 'datetime',
    ];

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function eliminatedPlayers()
    {
        return $this->players()->whereNotNull('eliminated_at')->orderBy('finish_position');
    }

    public function players(): HasMany
    {
        return $this->hasMany(MultiplayerGamePlayer::class);
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

    public function startGame(): bool
    {
        if ($this->status !== 'registration') {
            return false;
        }

        // Assign random turn orders
        $players = $this->players()->get();
        $playerCount = $players->count();

        if ($playerCount < 2) {
            return false;
        }

        $turnOrders = range(1, $playerCount);
        shuffle($turnOrders);

        // Set initial lives based on player count
        if ($playerCount <= 5) {
            $initialLives = 6;
        } elseif ($playerCount <= 10) {
            $initialLives = 5;
        } elseif ($playerCount <= 14) {
            $initialLives = 4;
        } else {
            $initialLives = 3;
        }

        // Update each player
        foreach ($players as $index => $player) {
            $player->update([
                'turn_order' => $turnOrders[$index],
                'lives'      => $initialLives,
                'cards'      => ['skip_turn' => true, 'pass_turn' => true, 'hand_shot' => true],
            ]);
        }

        $this->update([
            'status'        => 'in_progress',
            'started_at'    => now(),
            'initial_lives' => $initialLives,
        ]);

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

    public function activePlayers()
    {
        return $this->players()->whereNull('eliminated_at');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(MultiplayerGameLog::class);
    }

    public function eliminatePlayer(MultiplayerGamePlayer $player): void
    {
        $activePlayersCount = $this->activePlayers()->count();

        // Set finish position (players count - active players + 1)
        $finishPosition = $this->players()->count() - $activePlayersCount + 1;

        $player->update([
            'eliminated_at'   => now(),
            'finish_position' => $finishPosition,
        ]);

        // If only one player remains, complete the game
        if ($activePlayersCount === 1) {
            $lastPlayer = $this->activePlayers()->first();
            $lastPlayer->update([
                'finish_position' => 1,
            ]);

            $this->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);
        }
    }
}
