<?php

namespace App\Tournaments\Models;

use App\Core\Models\City;
use App\Core\Models\Club;
use App\Core\Models\Game;
use App\Core\Traits\HasSlug;
use App\OfficialRatings\Models\OfficialRating;
use App\Tournaments\Enums\SeedingMethod;
use App\Tournaments\Enums\TournamentStage;
use App\Tournaments\Enums\TournamentStatus;
use App\Tournaments\Enums\TournamentType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    use HasSlug;

    protected $fillable = [
        'name',
        'slug',
        'regulation',
        'details',
        'status',
        'stage',
        'game_id',
        'city_id',
        'club_id',
        'start_date',
        'end_date',
        'application_deadline',
        'max_participants',
        'entry_fee',
        'prize_pool',
        'prize_distribution',
        'place_prizes',
        'place_bonuses',
        'place_rating_points',
        'organizer',
        'format',
        'tournament_type',
        'group_size_min',
        'group_size_max',
        'playoff_players_per_group',
        'races_to',
        'has_third_place_match',
        'seeding_method',
        'is_old',
        'requires_application',
        'auto_approve_applications',
        'seeding_completed_at',
        'groups_completed_at',
        'seeding_completed',
        'brackets_generated',
    ];

    protected $casts = [
        'updated_at'         => 'datetime',
        'start_date'                => 'datetime',
        'end_date'                  => 'datetime',
        'application_deadline'      => 'datetime',
        'seeding_completed_at'      => 'datetime',
        'groups_completed_at'       => 'datetime',
        'entry_fee'                 => 'decimal:2',
        'prize_pool'         => 'string',
        'prize_distribution'        => 'array',
        'place_prizes'              => 'array',
        'place_bonuses'             => 'array',
        'place_rating_points'       => 'array',
        'requires_application'      => 'boolean',
        'auto_approve_applications' => 'boolean',
        'has_third_place_match'     => 'boolean',
        'seeding_completed'  => 'boolean',
        'brackets_generated' => 'boolean',
        'is_old'                    => 'boolean',
        'races_to'                  => 'integer',
        'group_size_min'            => 'integer',
        'group_size_max'            => 'integer',
        'playoff_players_per_group' => 'integer',
        'status'                    => TournamentStatus::class,
        'stage'                     => TournamentStage::class,
        'tournament_type'           => TournamentType::class,
        'seeding_method'            => SeedingMethod::class,
    ];

    protected $withCount = ['players', 'confirmedPlayers', 'pendingApplications'];

    protected $with = ['game', 'city', 'club'];

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

    public function confirmedPlayers(): HasMany
    {
        return $this->players()->where('status', 'confirmed');
    }

    public function players(): HasMany
    {
        return $this->hasMany(TournamentPlayer::class);
    }

    public function pendingApplications(): HasMany
    {
        return $this->players()->where('status', 'applied');
    }

    public function isRegistrationOpen(): bool
    {
        return $this->canAcceptApplications();
    }

    public function canAcceptApplications(): bool
    {
        if ($this->status !== TournamentStatus::UPCOMING) {
            return false;
        }

        if ($this->requires_application) {
            $deadline = $this->application_deadline ?? $this->start_date;
            if ($deadline && $deadline < now()) {
                return false;
            }
        }

        return !($this->max_participants && $this->confirmed_players_count >= $this->max_participants);
    }

    public function isActive(): bool
    {
        return $this->status === TournamentStatus::ACTIVE;
    }

    public function isCompleted(): bool
    {
        return $this->status === TournamentStatus::COMPLETED;
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

    public function matches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(TournamentGroup::class);
    }

    public function brackets(): HasMany
    {
        return $this->hasMany(TournamentBracket::class);
    }

    public function tableWidgets(): HasMany
    {
        return $this->hasMany(TournamentTableWidget::class);
    }

    public function officialRatings(): BelongsToMany
    {
        return $this
            ->belongsToMany(OfficialRating::class, 'official_rating_tournaments')
            ->withPivot(['rating_coefficient', 'is_counting'])
            ->withTimestamps()
        ;
    }
}
