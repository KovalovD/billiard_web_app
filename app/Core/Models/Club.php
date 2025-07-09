<?php
// app/Core/Models/Club.php

namespace App\Core\Models;

use App\Leagues\Models\League;
use App\Matches\Models\MatchGame;
use App\Tournaments\Models\Tournament;
use Database\Factories\ClubFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Club extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'city_id',
    ];

    protected $withCount = ['tournaments', 'tables'];

    protected $with = ['city'];

    public static function newFactory(): ClubFactory|Factory
    {
        return ClubFactory::new();
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function leagues(): HasMany
    {
        return $this->hasMany(League::class);
    }

    public function matchGames(): HasMany
    {
        return $this->hasMany(MatchGame::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'home_club_id');
    }

    public function tournaments(): HasMany
    {
        return $this->hasMany(Tournament::class);
    }

    public function tables(): HasMany
    {
        return $this->hasMany(ClubTable::class);
    }
}
