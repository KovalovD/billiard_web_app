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
        'matchesAsFirstPlayer',
        'ongoingMatchesAsSecondPlayer',
        'matchesAsSecondPlayer',
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
        return $this
            ->matchesAsFirstPlayer()
            ->whereIn('status', GameStatus::notAllowedToInviteStatuses())
        ;
    }

    public function ongoingMatchesAsSecondPlayer(): HasMany
    {
        return $this
            ->matchesAsSecondPlayer()
            ->whereIn('status', GameStatus::notAllowedToInviteStatuses())
        ;
    }

    /**
     * @return Collection<MatchGame>
     */
    public function ongoingMatches(): Collection
    {
        return $this->ongoingMatchesAsFirstPlayer->merge($this->ongoingMatchesAsSecondPlayer);
    }

    public function matchesAsFirstPlayer(): HasMany
    {
        return $this->hasMany(MatchGame::class, 'first_rating_id')->with('league');
    }

    public function matchesAsSecondPlayer(): HasMany
    {
        return $this->hasMany(MatchGame::class, 'second_rating_id')->with('league');
    }

    /**
     * @return Collection<MatchGame>
     */
    public function matches(): Collection
    {
        return $this->matchesAsFirstPlayer->merge($this->matchesAsSecondPlayer);
    }

    /**
     * @return Collection<MatchGame>
     */
    public function wins(): Collection
    {
        return $this->matches()->where('winner_rating_id', $this->id);
    }

    /**
     * @return Collection<MatchGame>
     */
    public function loses(): Collection
    {
        return $this->matches()->where('loser_rating_id', $this->id);
    }
}
