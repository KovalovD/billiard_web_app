<?php

namespace App\OfficialRatings\Models;

use App\Core\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficialRatingPlayer extends Model
{
    protected $fillable = [
        'official_rating_id',
        'user_id',
        'rating_points',
        'position',
        'tournaments_played',
        'tournaments_won',
        'last_tournament_at',
        'is_active',
    ];

    protected $casts = [
        'last_tournament_at' => 'datetime',
        'is_active'          => 'boolean',
    ];

    public function officialRating(): BelongsTo
    {
        return $this->belongsTo(OfficialRating::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getWinRateAttribute(): float
    {
        if ($this->tournaments_played === 0) {
            return 0;
        }

        return round(($this->tournaments_won / $this->tournaments_played) * 100, 2);
    }

    public function isTopPlayer(): bool
    {
        return $this->position <= 10;
    }

    public function addTournament(int $ratingPoints, bool $won = false): void
    {
        $this->increment('tournaments_played');
        $this->rating_points += $ratingPoints;
        $this->last_tournament_at = now();

        if ($won) {
            $this->increment('tournaments_won');
        }

        $this->save();
    }
}
