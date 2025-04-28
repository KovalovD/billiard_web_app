<?php

namespace App\Core\Models;

use App\Matches\Enums\GameType;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'rules',
        'type' => GameType::class,
        'is_multiplayer',
    ];

    protected $casts = [
        'is_multiplayer' => 'boolean',
    ];
}
