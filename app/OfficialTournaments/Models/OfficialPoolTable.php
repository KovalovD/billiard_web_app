<?php

namespace App\OfficialTournaments\Models;

use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfficialPoolTable extends Model
{
    use HasFactory;

    protected $table = 'official_pool_tables';

    protected $fillable = [
        'tournament_id',
        'name',
        'cloth_speed',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(OfficialTournament::class, 'tournament_id');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(OfficialMatch::class, 'table_id');
    }

    /**
     * Check if table is available at given time
     */
    public function isAvailableAt(DateTime $startTime, DateTime $endTime): bool
    {
        return !$this
            ->scheduleSlots()
            ->where(function ($query) use ($startTime, $endTime) {
                $query
                    ->whereBetween('start_at', [$startTime, $endTime])
                    ->orWhereBetween('end_at', [$startTime, $endTime])
                    ->orWhere(function ($q) use ($startTime, $endTime) {
                        $q
                            ->where('start_at', '<=', $startTime)
                            ->where('end_at', '>=', $endTime)
                        ;
                    })
                ;
            })
            ->exists()
        ;
    }

    public function scheduleSlots(): HasMany
    {
        return $this->hasMany(OfficialScheduleSlot::class, 'table_id');
    }
}
