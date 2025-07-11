<?php
// app/Tournaments/Models/TournamentBracket.php

namespace App\Tournaments\Models;

use App\Tournaments\Enums\BracketType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentBracket extends Model
{
    protected $fillable = [
        'tournament_id',
        'bracket_type',
        'total_rounds',
        'players_count',
        'bracket_structure',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'total_rounds'      => 'integer',
        'players_count'     => 'integer',
        'bracket_structure' => 'array',
        'bracket_type'      => BracketType::class,
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function matches(): HasMany
    {
        return $this
            ->hasMany(TournamentMatch::class, 'tournament_id', 'tournament_id')
            ->where('bracket_side', $this->bracket_type)
        ;
    }
}
