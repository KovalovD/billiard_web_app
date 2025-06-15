<?php

namespace App\OfficialTournaments\Models;

use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficialScheduleSlot extends Model
{
    use HasFactory;

    protected $table = 'official_schedule_slots';

    protected $fillable = [
        'tournament_id',
        'table_id',
        'start_at',
        'end_at',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at'   => 'datetime',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(OfficialTournament::class, 'tournament_id');
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(OfficialPoolTable::class, 'table_id');
    }

    /**
     * Get duration in minutes
     */
    public function getDurationInMinutes(): int
    {
        return $this->start_at->diffInMinutes($this->end_at);
    }

    /**
     * Check if slot overlaps with given time period
     */
    public function overlaps(DateTime $startTime, DateTime $endTime): bool
    {
        return !($this->end_at <= $startTime || $this->start_at >= $endTime);
    }
}
