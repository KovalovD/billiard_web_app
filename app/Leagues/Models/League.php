<?php

namespace App\Leagues\Models;

use App\Core\Models\Game;
use App\Core\Traits\HasSlug;
use App\Leagues\Enums\RatingType;
use App\Matches\Models\MatchGame;
use App\Matches\Models\MultiplayerGame;
use database\factories\LeagueFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class League extends Model
{
    use SoftDeletes;
    use HasFactory;
    use HasSlug;

    public $timestamps = false;

    protected $with = ['game'];
    protected $withCount = ['matches', 'activeRatings', 'multiplayerGames'];
    protected $fillable = [
        'name',
        'slug',
        'picture',
        'details',
        'has_rating',
        'game_id',
        'start_rating',
        'rating_change_for_winners_rule',
        'rating_change_for_losers_rule',
        'started_at',
        'finished_at',
        'rating_type',
        'max_players',
        'max_score',
        'invite_days_expire',
    ];

    protected $casts = [
        'updated_at' => 'datetime',
        'has_rating'                     => 'boolean',
        'rating_change_for_winners_rule' => 'array',
        'rating_change_for_losers_rule'  => 'array',
        'rating_type'                    => RatingType::class,
    ];

    public static function newFactory(): LeagueFactory|Factory
    {
        return LeagueFactory::new();
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(MatchGame::class);
    }

    public function multiplayerGames(): HasMany
    {
        return $this->hasMany(MultiplayerGame::class);
    }

    public function activeRatings(): HasMany
    {
        return $this->ratings()->where('is_active', true);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }
}
