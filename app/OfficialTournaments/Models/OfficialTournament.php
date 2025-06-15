<?php

namespace App\OfficialTournaments\Models;

use App\Core\Models\City;
use App\Core\Models\Club;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class OfficialTournament extends Model
{
    use HasFactory;

    protected $table = 'official_tournaments';

    protected $fillable = [
        'name',
        'discipline',
        'start_at',
        'end_at',
        'city_id',
        'club_id',
        'entry_fee',
        'prize_pool',
        'format',
    ];

    protected $casts = [
        'start_at'   => 'datetime',
        'end_at'     => 'datetime',
        'entry_fee'  => 'decimal:2',
        'prize_pool' => 'decimal:2',
        'format'     => 'array',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function stages(): HasMany
    {
        return $this->hasMany(OfficialStage::class, 'tournament_id');
    }

    public function teams(): HasMany
    {
        return $this->hasMany(OfficialTeam::class, 'tournament_id');
    }

    public function poolTables(): HasMany
    {
        return $this->hasMany(OfficialPoolTable::class, 'tournament_id');
    }

    public function scheduleSlots(): HasMany
    {
        return $this->hasMany(OfficialScheduleSlot::class, 'tournament_id');
    }

    /**
     * Get all participants across all stages
     */
    public function participants(): HasManyThrough
    {
        return $this->hasManyThrough(OfficialParticipant::class, OfficialStage::class, 'tournament_id', 'stage_id');
    }

    /**
     * Get all matches across all stages
     */
    public function matches(): HasManyThrough
    {
        return $this->hasManyThrough(OfficialMatch::class, OfficialStage::class, 'tournament_id', 'stage_id');
    }

    /**
     * Check if tournament is ongoing
     */
    public function isOngoing(): bool
    {
        return $this->hasStarted() && !$this->hasEnded();
    }

    /**
     * Check if tournament has started
     */
    public function hasStarted(): bool
    {
        return $this->start_at && $this->start_at->isPast();
    }

    /**
     * Check if tournament has ended
     */
    public function hasEnded(): bool
    {
        return $this->end_at && $this->end_at->isPast();
    }
}
