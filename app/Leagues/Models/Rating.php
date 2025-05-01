<?php

namespace App\Leagues\Models;

use App\Core\Models\User;
use App\Matches\Enums\GameStatus;
use App\Matches\Models\MatchGame;
use database\factories\RatingFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rating extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'league_id',
        'rating',
        'position',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $with = [
        'ongoingMatchesAsFirstPlayer',
        'ongoingMatchesAsSecondPlayer',
    ];

    public static function newFactory(): RatingFactory|Factory
    {
        return RatingFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function ongoingMatchesAsFirstPlayer(): HasMany
    {
        return $this->hasMany(MatchGame::class, 'first_rating_id')->whereIn('status',
            GameStatus::notAllowedToInviteStatuses());
    }

    public function ongoingMatchesAsSecondPlayer(): HasMany
    {
        return $this->hasMany(MatchGame::class, 'second_rating_id')->whereIn('status',
            GameStatus::notAllowedToInviteStatuses());
    }

    public function ongoingMatches(): Collection
    {
        return $this->ongoingMatchesAsFirstPlayer->merge($this->ongoingMatchesAsSecondPlayer);
    }
}
