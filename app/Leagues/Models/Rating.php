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

    private ?int $last_player_rating_id = null;

    protected $fillable = [
        'user_id',
        'league_id',
        'rating',
        'position',
        'is_active',
        'is_confirmed',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_confirmed' => 'boolean',
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
        $matches = $this->ongoingMatchesAsFirstPlayer->merge($this->ongoingMatchesAsSecondPlayer);

        // Define a fixed priority order by using an array for consistent test results
        return $matches->sortBy(function (MatchGame $match) {
            // Define a fixed priority order for statuses for consistent test results
            $priorities = [
                GameStatus::MUST_BE_CONFIRMED->value => 1,
                GameStatus::IN_PROGRESS->value       => 2,
                GameStatus::PENDING->value           => 3,
            ];

            return $priorities[$match->status->value] ?? 4;
        });
    }

    public function matchesAsFirstPlayer(): HasMany
    {
        return $this->hasMany(MatchGame::class, 'first_rating_id')->orderBy('finished_at', 'desc')->with('league');
    }

    public function matchesAsSecondPlayer(): HasMany
    {
        return $this->hasMany(MatchGame::class, 'second_rating_id')->orderBy('finished_at', 'desc')->with('league');
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

    public function setLastPlayerRatingId(?int $id): self
    {
        $this->last_player_rating_id = $id;

        return $this;
    }

    public function getLastPlayerRatingId(): ?int
    {
        return $this->last_player_rating_id;
    }
}
