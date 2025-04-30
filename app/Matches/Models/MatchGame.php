<?php

namespace App\Matches\Models;

use App\Core\Models\Club;
use App\Core\Models\Game;
use App\Leagues\Models\League;
use App\Leagues\Models\Rating;
use App\Matches\Enums\GameStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchGame extends Model
{
    protected $fillable = [
        'game_id',
        'first_rating_id',
        'second_rating_id',
        'first_user_score',
        'second_user_score',
        'stream_url',
        'winner_rating_id',
        'loser_rating_id',
        'details',
        'club_id',
        'status',
        'league_id',
        'rating_change_for_winner',
        'rating_change_for_loser',
        'invitation_sent_at',
        'invitation_available_till',
        'invitation_accepted_at',
        'finished_at',
        'first_rating_before_game',
        'second_rating_before_game',
    ];

    protected $casts = [
        'status'                    => GameStatus::class,
        'invitation_sent_at'        => 'timestamp',
        'invitation_available_till' => 'timestamp',
        'invitation_accepted_at'    => 'timestamp',
        'finished_at'               => 'timestamp',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function firstRating(): BelongsTo
    {
        return $this->belongsTo(Rating::class, 'first_rating_id');
    }

    public function secondRating(): BelongsTo
    {
        return $this->belongsTo(Rating::class, 'second_rating_id');
    }

    /**
     * @return array<Rating>
     */
    public function getRatings(): array
    {
        return $this->game->is_multiplayer
            ? []
            : [$this->firstRating, $this->secondRating];
    }
}
