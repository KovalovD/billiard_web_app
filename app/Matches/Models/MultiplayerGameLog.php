<?php

namespace App\Matches\Models;

use App\Core\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MultiplayerGameLog extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'multiplayer_game_id',
        'user_id',
        'action_type',
        'action_data',
        'created_at',
    ];
    protected $casts = [
        'action_data' => 'array',
        'created_at'  => 'datetime',
    ];

    public function multiplayerGame(): BelongsTo
    {
        return $this->belongsTo(MultiplayerGame::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
