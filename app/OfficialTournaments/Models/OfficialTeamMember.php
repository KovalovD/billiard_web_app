<?php

namespace App\OfficialTournaments\Models;

use App\Core\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficialTeamMember extends Model
{
    protected $table = 'official_team_members';

    protected $fillable = [
        'team_id',
        'user_id',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(OfficialTeam::class, 'team_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
