<?php
// app/Tournaments/Models/TournamentGroup.php

namespace App\Tournaments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentGroup extends Model
{
    protected $fillable = [
        'tournament_id',
        'group_code',
        'group_size',
        'advance_count',
        'is_completed',
    ];

    protected $casts = [
        'group_size'    => 'integer',
        'advance_count' => 'integer',
        'is_completed'  => 'boolean',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function players(): HasMany
    {
        return $this
            ->hasMany(TournamentPlayer::class, 'tournament_id', 'tournament_id')
            ->where('group_code', $this->group_code)
        ;
    }

    public function matches(): HasMany
    {
        return $this
            ->hasMany(TournamentMatch::class, 'tournament_id', 'tournament_id')
            ->where('stage', 'group')
            ->where('match_code', 'LIKE', $this->group_code.'%')
        ;
    }
}
