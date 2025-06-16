<?php
// app/Tournaments/Models/TournamentTableWidget.php

namespace App\Tournaments\Models;

use App\Core\Models\ClubTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentTableWidget extends Model
{
    protected $fillable = [
        'tournament_id',
        'club_table_id',
        'current_match_id',
        'widget_url',
        'player_widget_url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function clubTable(): BelongsTo
    {
        return $this->belongsTo(ClubTable::class);
    }

    public function currentMatch(): BelongsTo
    {
        return $this->belongsTo(TournamentMatch::class, 'current_match_id');
    }
}
