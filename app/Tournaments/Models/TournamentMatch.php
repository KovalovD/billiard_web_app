<?php
// app/Tournaments/Models/TournamentMatch.php

namespace App\Tournaments\Models;

use App\Core\Models\ClubTable;
use App\Core\Models\User;
use App\Tournaments\Enums\BracketSide;
use App\Tournaments\Enums\EliminationRound;
use App\Tournaments\Enums\MatchStage;
use App\Tournaments\Enums\MatchStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentMatch extends Model
{
    protected $fillable = [
        'tournament_id',
        'match_code',
        'stage',
        'round',
        'bracket_position',
        'bracket_side',
        'player1_id',
        'player2_id',
        'winner_id',
        'player1_score',
        'player2_score',
        'races_to',
        'previous_match1_id',
        'previous_match2_id',
        'next_match_id',
        'loser_next_match_id',
        'club_table_id',
        'stream_url',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
        'admin_notes',
        'metadata',
    ];

    protected $casts = [
        'scheduled_at'     => 'datetime',
        'metadata'         => 'array',
        'started_at'       => 'datetime',
        'completed_at'     => 'datetime',
        'player1_score'    => 'integer',
        'player2_score'    => 'integer',
        'races_to'         => 'integer',
        'bracket_position' => 'integer',
        'stage'            => MatchStage::class,
        'round'            => EliminationRound::class,
        'bracket_side'     => BracketSide::class,
        'status'           => MatchStatus::class,
    ];

    protected $with = ['tournament'];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function player1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player1_id');
    }

    public function player2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player2_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function previousMatch1(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_match1_id');
    }

    public function previousMatch2(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_match2_id');
    }

    public function nextMatch(): BelongsTo
    {
        return $this->belongsTo(self::class, 'next_match_id');
    }

    public function loserNextMatch(): BelongsTo
    {
        return $this->belongsTo(self::class, 'loser_next_match_id');
    }

    public function clubTable(): BelongsTo
    {
        return $this->belongsTo(ClubTable::class);
    }
}
