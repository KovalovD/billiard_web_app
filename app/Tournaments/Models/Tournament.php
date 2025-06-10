<?php

namespace App\Tournaments\Models;

use App\Core\Models\City;
use App\Core\Models\Club;
use App\Core\Models\Game;
use App\OfficialRatings\Models\OfficialRating;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    protected $fillable = [
        'name',
        'regulation',
        'details',
        'status',
        'game_id',
        'city_id',
        'club_id',
        'start_date',
        'end_date',
        'application_deadline',
        'max_participants',
        'entry_fee',
        'prize_pool',
        'prize_distribution',
        'organizer',
        'format',
        'is_old',
        'requires_application',
        'auto_approve_applications',
        'tournament_format',
        'seeding_method',
        'number_of_groups',
        'players_per_group',
        'advance_per_group',
        'best_of_rule',
        'has_lower_bracket',
        'is_team_tournament',
        'team_size',
        'bracket_structure',
        'seeding_configuration',
    ];

    protected $casts = [
        'start_date'                => 'datetime',
        'end_date'                  => 'datetime',
        'application_deadline'      => 'datetime',
        'entry_fee'                 => 'decimal:2',
        'prize_pool'                => 'decimal:2',
        'prize_distribution'        => 'array',
        'requires_application'      => 'boolean',
        'auto_approve_applications' => 'boolean',
        'has_lower_bracket'     => 'boolean',
        'is_team_tournament'    => 'boolean',
        'bracket_structure'     => 'array',
        'seeding_configuration' => 'array',
    ];

    protected $withCount = ['players', 'confirmedPlayers', 'pendingApplications'];

    protected $with = ['game', 'city', 'club'];

    // Existing relationships
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(TournamentPlayer::class);
    }

    public function confirmedPlayers(): HasMany
    {
        return $this->players()->where('status', 'confirmed');
    }

    public function pendingApplications(): HasMany
    {
        return $this->players()->where('status', 'applied');
    }

    public function rejectedApplications(): HasMany
    {
        return $this->players()->where('status', 'rejected');
    }

    public function officialRatings(): BelongsToMany
    {
        return $this
            ->belongsToMany(OfficialRating::class, 'official_rating_tournaments')
            ->withPivot(['rating_coefficient', 'is_counting'])
            ->withTimestamps()
        ;
    }

    // New relationships for advanced tournament management
    public function groups(): HasMany
    {
        return $this->hasMany(TournamentGroup::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(TournamentTeam::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class);
    }

    public function brackets(): HasMany
    {
        return $this->hasMany(TournamentBracket::class);
    }

    // Existing methods
    public function isRegistrationOpen(): bool
    {
        return $this->canAcceptApplications();
    }

    public function canAcceptApplications(): bool
    {
        if ($this->status !== 'upcoming') {
            return false;
        }

        if ($this->requires_application) {
            $deadline = $this->application_deadline ?? $this->start_date;
            if ($deadline && $deadline < now()) {
                return false;
            }
        }

        return !($this->max_participants && $this->confirmed_players_count >= $this->max_participants);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status) {
            'upcoming' => 'Upcoming',
            'active' => 'Active',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    public function getWinner(): ?TournamentPlayer
    {
        return $this->players()->where('position', 1)->first();
    }

    public function getTopPlayers(int $limit = 3): Collection
    {
        return $this
            ->players()
            ->whereNotNull('position')
            ->orderBy('position')
            ->limit($limit)
            ->get()
        ;
    }

    // New methods for advanced tournament management

    /**
     * Check if tournament uses groups
     */
    public function hasGroups(): bool
    {
        return in_array($this->tournament_format, ['group_stage', 'group_playoff']);
    }

    /**
     * Check if tournament uses brackets
     */
    public function hasBrackets(): bool
    {
        return in_array($this->tournament_format, ['single_elimination', 'double_elimination', 'group_playoff']);
    }

    /**
     * Check if tournament is team-based
     */
    public function isTeamTournament(): bool
    {
        return $this->is_team_tournament;
    }

    /**
     * Get tournament format display name
     */
    public function getFormatDisplayAttribute(): string
    {
        return match ($this->tournament_format) {
            'single_elimination' => 'Single Elimination',
            'double_elimination' => 'Double Elimination',
            'group_stage' => 'Group Stage (Round Robin)',
            'group_playoff' => 'Group Stage + Playoffs',
            'round_robin' => 'Round Robin',
            default => ucfirst(str_replace('_', ' ', $this->tournament_format)),
        };
    }

    /**
     * Get seeding method display name
     */
    public function getSeedingMethodDisplayAttribute(): string
    {
        return match ($this->seeding_method) {
            'manual' => 'Manual Seeding',
            'random' => 'Random Shuffle',
            'rating_based' => 'Rating-Based',
            default => ucfirst(str_replace('_', ' ', $this->seeding_method)),
        };
    }

    /**
     * Initialize tournament structure
     */
    public function initializeStructure(): void
    {
        if ($this->hasGroups()) {
            $this->createGroups();
        }

        if ($this->hasBrackets()) {
            $this->createBrackets();
        }

        $this->assignParticipants();
    }

    /**
     * Create groups for group stage tournaments
     */
    public function createGroups(): void
    {
        if (!$this->hasGroups() || $this->groups()->exists()) {
            return;
        }

        $numberOfGroups = $this->number_of_groups ?? $this->calculateOptimalGroups();
        $playersPerGroup = $this->players_per_group ?? ceil($this->confirmed_players_count / $numberOfGroups);

        for ($i = 1; $i <= $numberOfGroups; $i++) {
            TournamentGroup::create([
                'tournament_id'    => $this->id,
                'name'             => 'Group '.chr(64 + $i), // Group A, B, C, etc.
                'group_number'     => $i,
                'max_participants' => $playersPerGroup,
                'advance_count'    => $this->advance_per_group ?? 2,
            ]);
        }
    }

    /**
     * Create brackets for elimination tournaments
     */
    public function createBrackets(): void
    {
        if (!$this->hasBrackets() || $this->brackets()->exists()) {
            return;
        }

        // Main bracket
        TournamentBracket::create([
            'tournament_id'      => $this->id,
            'bracket_type'       => $this->tournament_format === 'double_elimination' ? 'upper' : 'main',
            'total_participants' => $this->confirmed_players_count,
            'is_active'          => true,
        ]);

        // Lower bracket for double elimination
        if ($this->tournament_format === 'double_elimination') {
            TournamentBracket::create([
                'tournament_id'      => $this->id,
                'bracket_type'       => 'lower',
                'total_participants' => $this->confirmed_players_count,
                'is_active'          => true,
            ]);
        }
    }

    /**
     * Assign participants to groups or brackets
     */
    public function assignParticipants(): void
    {
        $participants = $this->confirmedPlayers()->get();

        if ($this->hasGroups()) {
            $this->assignToGroups($participants);
        }

        if ($this->hasBrackets() && $this->tournament_format !== 'group_playoff') {
            $this->assignToBrackets($participants);
        }
    }

    /**
     * Assign participants to groups
     */
    protected function assignToGroups(Collection $participants): void
    {
        $groups = $this->groups()->orderBy('group_number')->get();
        $groupCount = $groups->count();

        if ($groupCount === 0) {
            return;
        }

        // Apply seeding
        $seededParticipants = $this->applySeedingToParticipants($participants);

        // Distribute participants across groups
        foreach ($seededParticipants as $index => $participant) {
            $groupIndex = $index % $groupCount;
            $group = $groups[$groupIndex];

            $participant->update([
                'group_id' => $group->id,
                'seed'     => $index + 1,
            ]);
        }

        // Create group matches
        foreach ($groups as $group) {
            $this->createGroupMatches($group);
        }
    }

    /**
     * Assign participants to brackets
     */
    protected function assignToBrackets(Collection $participants): void
    {
        $mainBracket = $this->brackets()->where('bracket_type', 'main')->first()
            ?? $this->brackets()->where('bracket_type', 'upper')->first();

        if (!$mainBracket) {
            return;
        }

        $seededParticipants = $this->applySeedingToParticipants($participants);
        $bracketParticipants = [];

        foreach ($seededParticipants as $index => $participant) {
            $participant->update([
                'seed'             => $index + 1,
                'bracket_position' => $index + 1,
            ]);

            $bracketParticipants[] = [
                'id'   => $participant->id,
                'type' => 'player',
                'seed' => $index + 1,
            ];
        }

        if ($this->tournament_format === 'single_elimination') {
            $mainBracket->initializeSingleElimination($bracketParticipants);
        } elseif ($this->tournament_format === 'double_elimination') {
            $mainBracket->initializeDoubleElimination($bracketParticipants);
        }
    }

    /**
     * Apply seeding to participants
     */
    protected function applySeedingToParticipants(Collection $participants): Collection
    {
        return match ($this->seeding_method) {
            'random' => $participants->shuffle(),
            'rating_based' => $this->seedByRating($participants),
            'manual' => $participants->sortBy('seed'),
            default => $participants,
        };
    }

    /**
     * Seed participants by rating
     */
    protected function seedByRating(Collection $participants): Collection
    {
        // Get ratings from associated official rating or league ratings
        return $participants->sortByDesc(function ($participant) {
            // Implementation would depend on how ratings are stored
            // This is a simplified version
            return $participant->user->ratings()->latest()->first()?->rating ?? 1000;
        });
    }

    /**
     * Create matches for a group (round robin)
     */
    protected function createGroupMatches(TournamentGroup $group): void
    {
        $participants = $group->players()->get();
        $participantCount = $participants->count();

        if ($participantCount < 2) {
            return;
        }

        $matchNumber = 1;

        // Create round-robin matches
        for ($i = 0; $i < $participantCount; $i++) {
            for ($j = $i + 1; $j < $participantCount; $j++) {
                TournamentMatch::create([
                    'tournament_id'      => $this->id,
                    'match_type'         => 'group',
                    'round_number'       => 1, // All group matches are in round 1
                    'match_number'       => $matchNumber,
                    'group_id'           => $group->id,
                    'participant_1_id'   => $participants[$i]->id,
                    'participant_1_type' => 'player',
                    'participant_2_id'   => $participants[$j]->id,
                    'participant_2_type' => 'player',
                    'status'             => 'pending',
                ]);

                $matchNumber++;
            }
        }
    }

    /**
     * Calculate optimal number of groups
     */
    protected function calculateOptimalGroups(): int
    {
        $playerCount = $this->confirmed_players_count;

        if ($playerCount <= 8) {
            return 2;
        } elseif ($playerCount <= 16) {
            return 4;
        } elseif ($playerCount <= 32) {
            return 8;
        }

        return ceil($playerCount / 8); // Max 8 players per group
    }

    /**
     * Check if tournament structure is initialized
     */
    public function isStructureInitialized(): bool
    {
        if ($this->hasGroups() && $this->groups()->count() === 0) {
            return false;
        }

        if ($this->hasBrackets() && $this->brackets()->count() === 0) {
            return false;
        }

        return true;
    }

    /**
     * Get tournament progress summary
     */
    public function getProgressSummary(): array
    {
        $summary = [
            'status'             => $this->status,
            'format'             => $this->tournament_format,
            'is_initialized'     => $this->isStructureInitialized(),
            'total_participants' => $this->confirmed_players_count,
        ];

        if ($this->hasGroups()) {
            $summary['groups'] = [
                'total'     => $this->groups()->count(),
                'completed' => $this->groups()->where('is_completed', true)->count(),
            ];
        }

        if ($this->hasBrackets()) {
            $summary['brackets'] = [
                'total'     => $this->brackets()->count(),
                'completed' => $this->brackets()->where('is_completed', true)->count(),
            ];
        }

        $summary['matches'] = [
            'total'       => $this->matches()->count(),
            'completed'   => $this->matches()->where('status', 'completed')->count(),
            'in_progress' => $this->matches()->where('status', 'in_progress')->count(),
            'pending'     => $this->matches()->where('status', 'pending')->count(),
        ];

        return $summary;
    }
}
