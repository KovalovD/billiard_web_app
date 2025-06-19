<?php
// app/Core/Models/ClubTable.php

namespace App\Core\Models;

use App\Tournaments\Models\TournamentMatch;
use App\Tournaments\Models\TournamentTableWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClubTable extends Model
{
    protected $fillable = [
        'club_id',
        'name',
        'stream_url',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function activeMatch(): HasMany
    {
        return $this->hasMany(TournamentMatch::class)->where('status', 'in_progress');
    }

    public function tournamentTableWidgets(): HasMany
    {
        return $this->hasMany(TournamentTableWidget::class);
    }
}
