<?php

namespace App\Core\Models;

use App\Matches\Enums\GameType;
use Database\Factories\GameFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

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

    public static function newFactory(): GameFactory|Factory
    {
        return GameFactory::new();
    }
}
