<?php

namespace App\OfficialRatings\Models;

use App\Core\Models\Game;
use App\Tournaments\Models\Tournament;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfficialRating extends Model
{
    protected $fillable = [
        'name',
        'description',
        'game_id',
        'is_active',
        'initial_rating',
        'calculation_method',
        'rating_rules',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'rating_rules' => 'array',
    ];

    protected $withCount = ['players', 'tournaments'];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(OfficialRatingPlayer::class);
    }

    public function activePlayers(): HasMany
    {
        return $this->players()->where('is_active', true);
    }

    public function tournaments(): BelongsToMany
    {
        return $this
            ->belongsToMany(Tournament::class, 'official_rating_tournaments')
            ->withPivot(['rating_coefficient', 'is_counting'])
            ->withTimestamps()
        ;
    }

    public function getTopPlayers(int $limit = 10): Collection
    {
        return $this
            ->activePlayers()
            ->with('user')
            ->orderBy('position')
            ->limit($limit)
            ->get()
        ;
    }

    public function getPlayerRating(int $userId): ?OfficialRatingPlayer
    {
        return $this->players()->where('user_id', $userId)->first();
    }

    public function hasPlayer(int $userId): bool
    {
        return $this->players()->where('user_id', $userId)->exists();
    }
}
