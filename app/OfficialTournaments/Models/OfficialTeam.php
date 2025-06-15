<?php

namespace App\OfficialTournaments\Models;

use App\Core\Models\Club;
use App\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfficialTeam extends Model
{
    use HasFactory;

    protected $table = 'official_teams';

    protected $fillable = [
        'tournament_id',
        'name',
        'club_id',
        'seed',
    ];

    protected $casts = [
        'seed' => 'integer',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(OfficialTournament::class, 'tournament_id');
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(OfficialTeamMember::class, 'team_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(OfficialParticipant::class, 'team_id');
    }

    /**
     * Add a member to the team
     */
    public function addMember(User $user): void
    {
        if (!$this->members()->where('user_id', $user->id)->exists()) {
            $this->members()->attach($user->id);
        }
    }

    public function members(): BelongsToMany
    {
        return $this
            ->belongsToMany(User::class, 'official_team_members', 'team_id', 'user_id')
            ->withTimestamps()
        ;
    }

    /**
     * Remove a member from the team
     */
    public function removeMember(User $user): void
    {
        $this->members()->detach($user->id);
    }

    /**
     * Check if user is a team member
     */
    public function hasMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Get average rating of team members
     */
    public function getAverageRating(): ?float
    {
        $ratings = $this
            ->members()
            ->whereNotNull('rating')
            ->pluck('rating')
        ;

        return $ratings->isNotEmpty() ? $ratings->average() : null;
    }
}
