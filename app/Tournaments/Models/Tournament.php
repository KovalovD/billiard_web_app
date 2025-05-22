<?php

namespace App\Tournaments\Models;

use App\Core\Models\City;
use App\Core\Models\Club;
use App\Core\Models\Game;
use App\OfficialRatings\Models\OfficialRating;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    protected $fillable = [
        'name',
        'regulation',
        'details',
        'status',
        'game_id',
        'city_id',
        'club_id',
        'start_date',
        'end_date',
        'max_participants',
        'entry_fee',
        'prize_pool',
        'prize_distribution',
        'organizer',
        'format',
    ];

    protected $casts = [
        'start_date'         => 'date',
        'end_date'           => 'date',
        'entry_fee'          => 'decimal:2',
        'prize_pool'         => 'decimal:2',
        'prize_distribution' => 'array',
    ];

    protected $withCount = ['players'];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(TournamentPlayer::class);
    }

    public function confirmedPlayers(): HasMany
    {
        return $this->players()->where('status', 'confirmed');
    }

    public function officialRatings(): BelongsToMany
    {
        return $this
            ->belongsToMany(OfficialRating::class, 'official_rating_tournaments')
            ->withPivot(['rating_coefficient', 'is_counting'])
            ->withTimestamps()
        ;
    }

    public function isRegistrationOpen(): bool
    {
        return $this->status === 'upcoming' &&
            (!$this->max_participants || $this->players_count < $this->max_participants);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status) {
            'upcoming' => 'Upcoming',
            'active' => 'Active',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    public function getWinner(): ?TournamentPlayer
    {
        return $this->players()->where('position', 1)->first();
    }

    public function getTopPlayers(int $limit = 3): Collection
    {
        return $this
            ->players()
            ->whereNotNull('position')
            ->orderBy('position')
            ->limit($limit)
            ->get()
        ;
    }
}
