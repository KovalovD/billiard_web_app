#!/bin/bash

# Create directories
echo "Creating directories..."
mkdir -p app/OfficialTournaments/Models
mkdir -p database/factories/App/OfficialTournaments/Models

# Create migration file
echo "Creating migration..."
cat > database/migrations/2025_06_15_000001_create_official_tournament_tables.php << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Main tournaments table
        Schema::create('official_tournaments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('discipline', 50);
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->foreignId('city_id')->nullable()->constrained('cities');
            $table->foreignId('club_id')->nullable()->constrained('clubs');
            $table->decimal('entry_fee', 10, 2)->default(0);
            $table->decimal('prize_pool', 12, 2)->default(0);
            $table->json('format')->nullable();
            $table->timestamps();
        });

        // Tournament stages (single_elim, double_elim, swiss, group)
        Schema::create('official_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained('official_tournaments')->cascadeOnDelete();
            $table->string('type', 30); // single_elim, double_elim, swiss, group
            $table->integer('number')->default(1);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        // Teams for team tournaments
        Schema::create('official_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained('official_tournaments')->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('club_id')->nullable()->constrained('clubs');
            $table->integer('seed')->nullable();
            $table->timestamps();
        });

        // Team members
        Schema::create('official_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('official_teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();

            $table->unique(['team_id', 'user_id']);
        });

        // Tournament/stage participants
        Schema::create('official_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stage_id')->constrained('official_stages')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('team_id')->nullable()->constrained('official_teams');
            $table->integer('seed')->nullable();
            $table->integer('rating_snapshot')->nullable();
            $table->timestamps();
        });

        // Pool tables for scheduling
        Schema::create('official_pool_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained('official_tournaments')->cascadeOnDelete();
            $table->string('name', 50)->nullable();
            $table->string('cloth_speed', 30)->nullable();
            $table->timestamps();
        });

        // Tournament matches
        Schema::create('official_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stage_id')->constrained('official_stages')->cascadeOnDelete();
            $table->integer('round')->nullable();
            $table->string('bracket', 10)->default('W'); // W: Winner, L: Loser, C: Consolation
            $table->timestamp('scheduled_at')->nullable();
            $table->foreignId('table_id')->nullable()->constrained('official_pool_tables');
            $table->string('status', 20)->default('pending'); // pending, ongoing, finished, walkover
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // Match sets/games
        Schema::create('official_match_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('official_matches')->cascadeOnDelete();
            $table->integer('set_no');
            $table->foreignId('winner_participant_id')->nullable()->constrained('official_participants');
            $table->json('score_json')->nullable();
            $table->timestamps();
        });

        // Schedule time slots
        Schema::create('official_schedule_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained('official_tournaments')->cascadeOnDelete();
            $table->foreignId('table_id')->constrained('official_pool_tables');
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('official_schedule_slots');
        Schema::dropIfExists('official_match_sets');
        Schema::dropIfExists('official_matches');
        Schema::dropIfExists('official_pool_tables');
        Schema::dropIfExists('official_participants');
        Schema::dropIfExists('official_team_members');
        Schema::dropIfExists('official_teams');
        Schema::dropIfExists('official_stages');
        Schema::dropIfExists('official_tournaments');
    }
};
EOF

# Create OfficialTournament model
echo "Creating OfficialTournament model..."
cat > app/OfficialTournaments/Models/OfficialTournament.php << 'EOF'
<?php

namespace App\OfficialTournaments\Models;

use App\Core\Models\City;
use App\Core\Models\Club;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfficialTournament extends Model
{
    use HasFactory;

    protected $table = 'official_tournaments';

    protected $fillable = [
        'name',
        'discipline',
        'start_at',
        'end_at',
        'city_id',
        'club_id',
        'entry_fee',
        'prize_pool',
        'format',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'entry_fee' => 'decimal:2',
        'prize_pool' => 'decimal:2',
        'format' => 'array',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function stages(): HasMany
    {
        return $this->hasMany(OfficialStage::class, 'tournament_id');
    }

    public function teams(): HasMany
    {
        return $this->hasMany(OfficialTeam::class, 'tournament_id');
    }

    public function poolTables(): HasMany
    {
        return $this->hasMany(OfficialPoolTable::class, 'tournament_id');
    }

    public function scheduleSlots(): HasMany
    {
        return $this->hasMany(OfficialScheduleSlot::class, 'tournament_id');
    }

    /**
     * Get all participants across all stages
     */
    public function participants()
    {
        return $this->hasManyThrough(OfficialParticipant::class, OfficialStage::class, 'tournament_id', 'stage_id');
    }

    /**
     * Get all matches across all stages
     */
    public function matches()
    {
        return $this->hasManyThrough(OfficialMatch::class, OfficialStage::class, 'tournament_id', 'stage_id');
    }

    /**
     * Check if tournament has started
     */
    public function hasStarted(): bool
    {
        return $this->start_at && $this->start_at->isPast();
    }

    /**
     * Check if tournament has ended
     */
    public function hasEnded(): bool
    {
        return $this->end_at && $this->end_at->isPast();
    }

    /**
     * Check if tournament is ongoing
     */
    public function isOngoing(): bool
    {
        return $this->hasStarted() && !$this->hasEnded();
    }
}
EOF

# Create OfficialStage model
echo "Creating OfficialStage model..."
cat > app/OfficialTournaments/Models/OfficialStage.php << 'EOF'
<?php

namespace App\OfficialTournaments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfficialStage extends Model
{
    use HasFactory;

    protected $table = 'official_stages';

    const TYPE_SINGLE_ELIM = 'single_elim';
    const TYPE_DOUBLE_ELIM = 'double_elim';
    const TYPE_SWISS = 'swiss';
    const TYPE_GROUP = 'group';
    const TYPE_ROUND_ROBIN = 'round_robin';
    const TYPE_CUSTOM = 'custom';

    protected $fillable = [
        'tournament_id',
        'type',
        'number',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(OfficialTournament::class, 'tournament_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(OfficialParticipant::class, 'stage_id');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(OfficialMatch::class, 'stage_id');
    }

    /**
     * Get setting value by key
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Set setting value
     */
    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;
    }

    /**
     * Check if stage is elimination type
     */
    public function isElimination(): bool
    {
        return in_array($this->type, [self::TYPE_SINGLE_ELIM, self::TYPE_DOUBLE_ELIM]);
    }

    /**
     * Check if stage is group type
     */
    public function isGroup(): bool
    {
        return in_array($this->type, [self::TYPE_GROUP, self::TYPE_ROUND_ROBIN]);
    }

    /**
     * Get total rounds for the stage
     */
    public function getTotalRounds(): int
    {
        if ($this->type === self::TYPE_SINGLE_ELIM) {
            $participants = $this->participants()->count();
            return $participants > 0 ? ceil(log($participants, 2)) : 0;
        }

        if ($this->type === self::TYPE_DOUBLE_ELIM) {
            $participants = $this->participants()->count();
            return $participants > 0 ? ceil(log($participants, 2)) * 2 - 1 : 0;
        }

        return $this->getSetting('total_rounds', 0);
    }
}
EOF

# Create OfficialTeam model
echo "Creating OfficialTeam model..."
cat > app/OfficialTournaments/Models/OfficialTeam.php << 'EOF'
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

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'official_team_members', 'team_id', 'user_id')
            ->withTimestamps();
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
        $ratings = $this->members()
            ->whereNotNull('rating')
            ->pluck('rating');

        return $ratings->isNotEmpty() ? $ratings->average() : null;
    }
}
EOF

# Create OfficialTeamMember model
echo "Creating OfficialTeamMember model..."
cat > app/OfficialTournaments/Models/OfficialTeamMember.php << 'EOF'
<?php

namespace App\OfficialTournaments\Models;

use App\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficialTeamMember extends Model
{
    use HasFactory;

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
EOF

# Create OfficialParticipant model
echo "Creating OfficialParticipant model..."
cat > app/OfficialTournaments/Models/OfficialParticipant.php << 'EOF'
<?php

namespace App\OfficialTournaments\Models;

use App\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfficialParticipant extends Model
{
    use HasFactory;

    protected $table = 'official_participants';

    protected $fillable = [
        'stage_id',
        'user_id',
        'team_id',
        'seed',
        'rating_snapshot',
    ];

    protected $casts = [
        'seed' => 'integer',
        'rating_snapshot' => 'integer',
    ];

    public function stage(): BelongsTo
    {
        return $this->belongsTo(OfficialStage::class, 'stage_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(OfficialTeam::class, 'team_id');
    }

    public function wonSets(): HasMany
    {
        return $this->hasMany(OfficialMatchSet::class, 'winner_participant_id');
    }

    /**
     * Get participant display name
     */
    public function getDisplayName(): string
    {
        if ($this->team) {
            return $this->team->name;
        }

        if ($this->user) {
            return $this->user->full_name ?? $this->user->name;
        }

        return 'BYE';
    }

    /**
     * Check if participant is a bye
     */
    public function isBye(): bool
    {
        return !$this->user_id && !$this->team_id;
    }

    /**
     * Get all matches where this participant is involved
     */
    public function matches()
    {
        return OfficialMatch::query()
            ->where('stage_id', $this->stage_id)
            ->where(function ($query) {
                $query->whereHas('matchSets', function ($q) {
                    $q->where('winner_participant_id', $this->id);
                })
                ->orWhereJsonContains('metadata->participant1_id', $this->id)
                ->orWhereJsonContains('metadata->participant2_id', $this->id);
            });
    }

    /**
     * Get match statistics
     */
    public function getMatchStats(): array
    {
        $won = 0;
        $lost = 0;
        $setsWon = 0;
        $setsLost = 0;

        $matches = $this->matches()->with('matchSets')->get();

        foreach ($matches as $match) {
            if ($match->status !== 'finished') {
                continue;
            }

            $matchWon = $match->getWinnerId() === $this->id;

            if ($matchWon) {
                $won++;
            } else {
                $lost++;
            }

            foreach ($match->matchSets as $set) {
                if ($set->winner_participant_id === $this->id) {
                    $setsWon++;
                } else {
                    $setsLost++;
                }
            }
        }

        return [
            'matches_won' => $won,
            'matches_lost' => $lost,
            'sets_won' => $setsWon,
            'sets_lost' => $setsLost,
            'sets_difference' => $setsWon - $setsLost,
        ];
    }
}
EOF

# Create OfficialMatch model
echo "Creating OfficialMatch model..."
cat > app/OfficialTournaments/Models/OfficialMatch.php << 'EOF'
<?php

namespace App\OfficialTournaments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfficialMatch extends Model
{
    use HasFactory;

    protected $table = 'official_matches';

    const STATUS_PENDING = 'pending';
    const STATUS_ONGOING = 'ongoing';
    const STATUS_FINISHED = 'finished';
    const STATUS_WALKOVER = 'walkover';

    const BRACKET_WINNER = 'W';
    const BRACKET_LOSER = 'L';
    const BRACKET_CONSOLATION = 'C';

    protected $fillable = [
        'stage_id',
        'round',
        'bracket',
        'scheduled_at',
        'table_id',
        'status',
        'metadata',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function stage(): BelongsTo
    {
        return $this->belongsTo(OfficialStage::class, 'stage_id');
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(OfficialPoolTable::class, 'table_id');
    }

    public function matchSets(): HasMany
    {
        return $this->hasMany(OfficialMatchSet::class, 'match_id');
    }

    /**
     * Get participant 1
     */
    public function getParticipant1()
    {
        $id = $this->metadata['participant1_id'] ?? null;
        return $id ? OfficialParticipant::find($id) : null;
    }

    /**
     * Get participant 2
     */
    public function getParticipant2()
    {
        $id = $this->metadata['participant2_id'] ?? null;
        return $id ? OfficialParticipant::find($id) : null;
    }

    /**
     * Set participants
     */
    public function setParticipants(?OfficialParticipant $p1, ?OfficialParticipant $p2): void
    {
        $metadata = $this->metadata ?? [];
        $metadata['participant1_id'] = $p1?->id;
        $metadata['participant2_id'] = $p2?->id;
        $this->metadata = $metadata;
    }

    /**
     * Get winner participant ID
     */
    public function getWinnerId(): ?int
    {
        if ($this->status !== self::STATUS_FINISHED) {
            return null;
        }

        $p1Wins = $this->matchSets()->where('winner_participant_id', $this->metadata['participant1_id'] ?? 0)->count();
        $p2Wins = $this->matchSets()->where('winner_participant_id', $this->metadata['participant2_id'] ?? 0)->count();

        if ($p1Wins > $p2Wins) {
            return $this->metadata['participant1_id'];
        } elseif ($p2Wins > $p1Wins) {
            return $this->metadata['participant2_id'];
        }

        return null;
    }

    /**
     * Get loser participant ID
     */
    public function getLoserId(): ?int
    {
        $winnerId = $this->getWinnerId();

        if (!$winnerId) {
            return null;
        }

        $p1Id = $this->metadata['participant1_id'] ?? null;
        $p2Id = $this->metadata['participant2_id'] ?? null;

        return $winnerId === $p1Id ? $p2Id : $p1Id;
    }

    /**
     * Get match score summary
     */
    public function getScoreSummary(): array
    {
        $p1Id = $this->metadata['participant1_id'] ?? 0;
        $p2Id = $this->metadata['participant2_id'] ?? 0;

        return [
            'participant1' => [
                'id' => $p1Id,
                'sets_won' => $this->matchSets()->where('winner_participant_id', $p1Id)->count(),
            ],
            'participant2' => [
                'id' => $p2Id,
                'sets_won' => $this->matchSets()->where('winner_participant_id', $p2Id)->count(),
            ],
        ];
    }

    /**
     * Check if match can be started
     */
    public function canStart(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->getParticipant1()
            && $this->getParticipant2()
            && !$this->getParticipant1()->isBye()
            && !$this->getParticipant2()->isBye();
    }

    /**
     * Check if match is ready for scheduling
     */
    public function isReadyForScheduling(): bool
    {
        return $this->canStart() && !$this->scheduled_at;
    }
}
EOF

# Create OfficialMatchSet model
echo "Creating OfficialMatchSet model..."
cat > app/OfficialTournaments/Models/OfficialMatchSet.php << 'EOF'
<?php

namespace App\OfficialTournaments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficialMatchSet extends Model
{
    use HasFactory;

    protected $table = 'official_match_sets';

    protected $fillable = [
        'match_id',
        'set_no',
        'winner_participant_id',
        'score_json',
    ];

    protected $casts = [
        'set_no' => 'integer',
        'score_json' => 'array',
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(OfficialMatch::class, 'match_id');
    }

    public function winnerParticipant(): BelongsTo
    {
        return $this->belongsTo(OfficialParticipant::class, 'winner_participant_id');
    }

    /**
     * Get score for participant 1
     */
    public function getParticipant1Score(): ?int
    {
        return $this->score_json['participant1'] ?? null;
    }

    /**
     * Get score for participant 2
     */
    public function getParticipant2Score(): ?int
    {
        return $this->score_json['participant2'] ?? null;
    }

    /**
     * Set scores
     */
    public function setScores(int $p1Score, int $p2Score): void
    {
        $this->score_json = [
            'participant1' => $p1Score,
            'participant2' => $p2Score,
        ];
    }
}
EOF

# Create OfficialPoolTable model
echo "Creating OfficialPoolTable model..."
cat > app/OfficialTournaments/Models/OfficialPoolTable.php << 'EOF'
<?php

namespace App\OfficialTournaments\Models;

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

    public function scheduleSlots(): HasMany
    {
        return $this->hasMany(OfficialScheduleSlot::class, 'table_id');
    }

    /**
     * Check if table is available at given time
     */
    public function isAvailableAt(\DateTime $startTime, \DateTime $endTime): bool
    {
        return !$this->scheduleSlots()
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('start_at', [$startTime, $endTime])
                    ->orWhereBetween('end_at', [$startTime, $endTime])
                    ->orWhere(function ($q) use ($startTime, $endTime) {
                        $q->where('start_at', '<=', $startTime)
                          ->where('end_at', '>=', $endTime);
                    });
            })
            ->exists();
    }
}
EOF

# Create OfficialScheduleSlot model
echo "Creating OfficialScheduleSlot model..."
cat > app/OfficialTournaments/Models/OfficialScheduleSlot.php << 'EOF'
<?php

namespace App\OfficialTournaments\Models;

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
        'end_at' => 'datetime',
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
    public function overlaps(\DateTime $startTime, \DateTime $endTime): bool
    {
        return !($this->end_at <= $startTime || $this->start_at >= $endTime);
    }
}
EOF

# Create OfficialTournamentFactory
echo "Creating OfficialTournamentFactory..."
cat > database/factories/App/OfficialTournaments/Models/OfficialTournamentFactory.php << 'EOF'
<?php

namespace Database\Factories\App\OfficialTournaments\Models;

use App\Core\Models\City;
use App\Core\Models\Club;
use App\OfficialTournaments\Models\OfficialTournament;
use Illuminate\Database\Eloquent\Factories\Factory;

class OfficialTournamentFactory extends Factory
{
    protected $model = OfficialTournament::class;

    public function definition()
    {
        $startDate = $this->faker->dateTimeBetween('+1 week', '+3 months');
        $endDate = (clone $startDate)->modify('+' . rand(1, 3) . ' days');

        return [
            'name' => $this->faker->sentence(3) . ' Championship',
            'discipline' => $this->faker->randomElement(['8-ball', '9-ball', '10-ball', 'snooker']),
            'start_at' => $startDate,
            'end_at' => $endDate,
            'city_id' => City::factory(),
            'club_id' => Club::factory(),
            'entry_fee' => $this->faker->randomElement([0, 100, 200, 500]),
            'prize_pool' => $this->faker->randomElement([1000, 2500, 5000, 10000]),
            'format' => [
                'race_to' => $this->faker->randomElement([5, 7, 9]),
                'alternate_break' => $this->faker->boolean(),
            ],
        ];
    }
}
EOF

# Create OfficialStageFactory
echo "Creating OfficialStageFactory..."
cat > database/factories/App/OfficialTournaments/Models/OfficialStageFactory.php << 'EOF'
<?php

namespace Database\Factories\App\OfficialTournaments\Models;

use App\OfficialTournaments\Models\OfficialStage;
use App\OfficialTournaments\Models\OfficialTournament;
use Illuminate\Database\Eloquent\Factories\Factory;

class OfficialStageFactory extends Factory
{
    protected $model = OfficialStage::class;

    public function definition()
    {
        return [
            'tournament_id' => OfficialTournament::factory(),
            'type' => $this->faker->randomElement(['single_elim', 'double_elim', 'swiss', 'group']),
            'number' => 1,
            'settings' => [
                'best_of' => $this->faker->randomElement([1, 3, 5, 7]),
                'third_place_match' => $this->faker->boolean(),
            ],
        ];
    }
}
EOF

# Create other factories
echo "Creating remaining factories..."
cat > database/factories/App/OfficialTournaments/Models/OfficialTeamFactory.php << 'EOF'
<?php

namespace Database\Factories\App\OfficialTournaments\Models;

use App\Core\Models\Club;
use App\OfficialTournaments\Models\OfficialTeam;
use App\OfficialTournaments\Models\OfficialTournament;
use Illuminate\Database\Eloquent\Factories\Factory;

class OfficialTeamFactory extends Factory
{
    protected $model = OfficialTeam::class;

    public function definition()
    {
        return [
            'tournament_id' => OfficialTournament::factory(),
            'name' => $this->faker->company() . ' Team',
            'club_id' => Club::factory(),
            'seed' => null,
        ];
    }
}
EOF

cat > database/factories/App/OfficialTournaments/Models/OfficialParticipantFactory.php << 'EOF'
<?php

namespace Database\Factories\App\OfficialTournaments\Models;

use App\Core\Models\User;
use App\OfficialTournaments\Models\OfficialParticipant;
use App\OfficialTournaments\Models\OfficialStage;
use App\OfficialTournaments\Models\OfficialTeam;
use Illuminate\Database\Eloquent\Factories\Factory;

class OfficialParticipantFactory extends Factory
{
    protected $model = OfficialParticipant::class;

    public function definition()
    {
        $isTeam = $this->faker->boolean(20); // 20% chance of team

        return [
            'stage_id' => OfficialStage::factory(),
            'user_id' => $isTeam ? null : User::factory(),
            'team_id' => $isTeam ? OfficialTeam::factory() : null,
            'seed' => $this->faker->numberBetween(1, 32),
            'rating_snapshot' => $this->faker->numberBetween(1000, 2500),
        ];
    }
}
EOF

cat > database/factories/App/OfficialTournaments/Models/OfficialPoolTableFactory.php << 'EOF'
<?php

namespace Database\Factories\App\OfficialTournaments\Models;

use App\OfficialTournaments\Models\OfficialPoolTable;
use App\OfficialTournaments\Models\OfficialTournament;
use Illuminate\Database\Eloquent\Factories\Factory;

class OfficialPoolTableFactory extends Factory
{
    protected $model = OfficialPoolTable::class;

    public function definition()
    {
        return [
            'tournament_id' => OfficialTournament::factory(),
            'name' => 'Table ' . $this->faker->numberBetween(1, 10),
            'cloth_speed' => $this->faker->randomElement(['slow', 'medium', 'fast']),
        ];
    }
}
EOF

cat > database/factories/App/OfficialTournaments/Models/OfficialMatchFactory.php << 'EOF'
<?php

namespace Database\Factories\App\OfficialTournaments\Models;

use App\OfficialTournaments\Models\OfficialMatch;
use App\OfficialTournaments\Models\OfficialPoolTable;
use App\OfficialTournaments\Models\OfficialStage;
use Illuminate\Database\Eloquent\Factories\Factory;

class OfficialMatchFactory extends Factory
{
    protected $model = OfficialMatch::class;

    public function definition()
    {
        return [
            'stage_id' => OfficialStage::factory(),
            'round' => $this->faker->numberBetween(1, 5),
            'bracket' => $this->faker->randomElement(['W', 'L']),
            'scheduled_at' => $this->faker->dateTimeBetween('now', '+1 week'),
            'table_id' => OfficialPoolTable::factory(),
            'status' => $this->faker->randomElement(['pending', 'ongoing', 'finished']),
            'metadata' => [],
        ];
    }
}
EOF

cat > database/factories/App/OfficialTournaments/Models/OfficialMatchSetFactory.php << 'EOF'
<?php

namespace Database\Factories\App\OfficialTournaments\Models;

use App\OfficialTournaments\Models\OfficialMatch;
use App\OfficialTournaments\Models\OfficialMatchSet;
use App\OfficialTournaments\Models\OfficialParticipant;
use Illuminate\Database\Eloquent\Factories\Factory;

class OfficialMatchSetFactory extends Factory
{
    protected $model = OfficialMatchSet::class;

    public function definition()
    {
        $score1 = $this->faker->numberBetween(0, 9);
        $score2 = $this->faker->numberBetween(0, 9);

        return [
            'match_id' => OfficialMatch::factory(),
            'set_no' => 1,
            'winner_participant_id' => OfficialParticipant::factory(),
            'score_json' => [
                'participant1' => $score1,
                'participant2' => $score2,
            ],
        ];
    }
}
EOF

cat > database/factories/App/OfficialTournaments/Models/OfficialScheduleSlotFactory.php << 'EOF'
<?php

namespace Database\Factories\App\OfficialTournaments\Models;

use App\OfficialTournaments\Models\OfficialPoolTable;
use App\OfficialTournaments\Models\OfficialScheduleSlot;
use App\OfficialTournaments\Models\OfficialTournament;
use Illuminate\Database\Eloquent\Factories\Factory;

class OfficialScheduleSlotFactory extends Factory
{
    protected $model = OfficialScheduleSlot::class;

    public function definition()
    {
        $startTime = $this->faker->dateTimeBetween('now', '+1 week');
        $duration = $this->faker->randomElement([30, 45, 60, 90]); // minutes
        $endTime = (clone $startTime)->modify("+{$duration} minutes");

        return [
            'tournament_id' => OfficialTournament::factory(),
            'table_id' => OfficialPoolTable::factory(),
            'start_at' => $startTime,
            'end_at' => $endTime,
        ];
    }
}
EOF

# Create OfficialTournamentDemoSeeder
echo "Creating OfficialTournamentDemoSeeder..."
cat > database/seeders/OfficialTournamentDemoSeeder.php << 'EOF'
<?php

namespace Database\Seeders;

use App\Core\Models\City;
use App\Core\Models\Club;
use App\Core\Models\User;
use App\OfficialTournaments\Models\OfficialMatch;
use App\OfficialTournaments\Models\OfficialMatchSet;
use App\OfficialTournaments\Models\OfficialParticipant;
use App\OfficialTournaments\Models\OfficialPoolTable;
use App\OfficialTournaments\Models\OfficialStage;
use App\OfficialTournaments\Models\OfficialTournament;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OfficialTournamentDemoSeeder extends Seeder
{
    public function run()
    {
        DB::transaction(function () {
            // Create demo tournament
            $tournament = OfficialTournament::create([
                'name' => 'WinnerBreak Championship 2025',
                'discipline' => '9-ball',
                'start_at' => now()->addDays(7),
                'end_at' => now()->addDays(9),
                'city_id' => City::first()->id ?? City::factory()->create()->id,
                'club_id' => Club::first()->id ?? Club::factory()->create()->id,
                'entry_fee' => 500,
                'prize_pool' => 50000,
                'format' => [
                    'race_to' => 7,
                    'alternate_break' => true,
                ],
            ]);

            // Create 4 pool tables
            $tables = collect(range(1, 4))->map(function ($i) use ($tournament) {
                return OfficialPoolTable::create([
                    'tournament_id' => $tournament->id,
                    'name' => "Table {$i}",
                    'cloth_speed' => $i <= 2 ? 'fast' : 'medium',
                ]);
            });

            // Stage 1: Group stage (4 groups of 8)
            $groupStage = OfficialStage::create([
                'tournament_id' => $tournament->id,
                'type' => OfficialStage::TYPE_GROUP,
                'number' => 1,
                'settings' => [
                    'groups_count' => 4,
                    'players_per_group' => 8,
                    'advance_per_group' => 2,
                    'race_to' => 5,
                ],
            ]);

            // Stage 2: Single elimination playoff
            $playoffStage = OfficialStage::create([
                'tournament_id' => $tournament->id,
                'type' => OfficialStage::TYPE_SINGLE_ELIM,
                'number' => 2,
                'settings' => [
                    'third_place_match' => true,
                    'race_to' => 7,
                ],
            ]);

            // Create 32 participants for group stage
            $participants = collect(range(1, 32))->map(function ($i) use ($groupStage) {
                $user = User::factory()->create([
                    'firstname' => "Player",
                    'lastname' => "Number{$i}",
                ]);

                return OfficialParticipant::create([
                    'stage_id' => $groupStage->id,
                    'user_id' => $user->id,
                    'seed' => $i,
                    'rating_snapshot' => 2500 - ($i * 30) + rand(-50, 50),
                ]);
            });

            // Distribute participants into groups and create round-robin matches
            $groups = $participants->chunk(8);
            $groupNumber = 0;

            foreach ($groups as $group) {
                $groupNumber++;

                // Create round-robin matches for this group
                $groupParticipants = $group->values();

                for ($i = 0; $i < count($groupParticipants); $i++) {
                    for ($j = $i + 1; $j < count($groupParticipants); $j++) {
                        $match = OfficialMatch::create([
                            'stage_id' => $groupStage->id,
                            'round' => null, // Round-robin doesn't have rounds
                            'bracket' => 'G' . $groupNumber,
                            'scheduled_at' => null,
                            'table_id' => null,
                            'status' => OfficialMatch::STATUS_PENDING,
                            'metadata' => [
                                'group' => $groupNumber,
                                'participant1_id' => $groupParticipants[$i]->id,
                                'participant2_id' => $groupParticipants[$j]->id,
                            ],
                        ]);
                    }
                }
            }

            // Create a few demo matches with scores (simulate some completed matches)
            $demoMatches = OfficialMatch::where('stage_id', $groupStage->id)
                ->limit(10)
                ->get();

            foreach ($demoMatches as $match) {
                $match->update([
                    'status' => OfficialMatch::STATUS_FINISHED,
                    'scheduled_at' => now()->subHours(rand(1, 48)),
                    'table_id' => $tables->random()->id,
                ]);

                // Create match sets
                $p1Id = $match->metadata['participant1_id'];
                $p2Id = $match->metadata['participant2_id'];
                $raceTo = 5;
                $p1Wins = 0;
                $p2Wins = 0;
                $setNo = 1;

                while ($p1Wins < $raceTo && $p2Wins < $raceTo) {
                    $p1Score = rand(0, 9);
                    $p2Score = rand(0, 9);
                    $winnerId = $p1Score > $p2Score ? $p1Id : $p2Id;

                    OfficialMatchSet::create([
                        'match_id' => $match->id,
                        'set_no' => $setNo,
                        'winner_participant_id' => $winnerId,
                        'score_json' => [
                            'participant1' => $p1Score,
                            'participant2' => $p2Score,
                        ],
                    ]);

                    if ($winnerId == $p1Id) {
                        $p1Wins++;
                    } else {
                        $p2Wins++;
                    }

                    $setNo++;
                }
            }

            // Create stage 2 (playoff) participants - top 2 from each group
            $playoffParticipants = collect(range(1, 8))->map(function ($i) use ($playoffStage) {
                return OfficialParticipant::create([
                    'stage_id' => $playoffStage->id,
                    'user_id' => User::inRandomOrder()->first()->id,
                    'seed' => $i,
                    'rating_snapshot' => 2500 - ($i * 50) + rand(-30, 30),
                ]);
            });

            // Create single elimination bracket matches
            // Quarter-finals (4 matches)
            for ($i = 0; $i < 4; $i++) {
                OfficialMatch::create([
                    'stage_id' => $playoffStage->id,
                    'round' => 1,
                    'bracket' => OfficialMatch::BRACKET_WINNER,
                    'scheduled_at' => null,
                    'table_id' => null,
                    'status' => OfficialMatch::STATUS_PENDING,
                    'metadata' => [
                        'match_number' => $i + 1,
                        'participant1_id' => $playoffParticipants[$i * 2]->id,
                        'participant2_id' => $playoffParticipants[$i * 2 + 1]->id,
                    ],
                ]);
            }

            // Semi-finals (2 matches) - participants TBD
            for ($i = 0; $i < 2; $i++) {
                OfficialMatch::create([
                    'stage_id' => $playoffStage->id,
                    'round' => 2,
                    'bracket' => OfficialMatch::BRACKET_WINNER,
                    'scheduled_at' => null,
                    'table_id' => null,
                    'status' => OfficialMatch::STATUS_PENDING,
                    'metadata' => [
                        'match_number' => $i + 1,
                        'participant1_id' => null, // Winner of QF1/QF2
                        'participant2_id' => null, // Winner of QF3/QF4
                    ],
                ]);
            }

            // Final
            OfficialMatch::create([
                'stage_id' => $playoffStage->id,
                'round' => 3,
                'bracket' => OfficialMatch::BRACKET_WINNER,
                'scheduled_at' => null,
                'table_id' => null,
                'status' => OfficialMatch::STATUS_PENDING,
                'metadata' => [
                    'match_number' => 1,
                    'participant1_id' => null, // Winner of SF1
                    'participant2_id' => null, // Winner of SF2
                ],
            ]);

            // Third place match
            OfficialMatch::create([
                'stage_id' => $playoffStage->id,
                'round' => 3,
                'bracket' => OfficialMatch::BRACKET_CONSOLATION,
                'scheduled_at' => null,
                'table_id' => null,
                'status' => OfficialMatch::STATUS_PENDING,
                'metadata' => [
                    'match_number' => 1,
                    'participant1_id' => null, // Loser of SF1
                    'participant2_id' => null, // Loser of SF2
                ],
            ]);

            $this->command->info("Demo tournament created successfully!");
            $this->command->info("Tournament: {$tournament->name}");
            $this->command->info("Participants: 32");
            $this->command->info("Group stage matches: " . OfficialMatch::where('stage_id', $groupStage->id)->count());
            $this->command->info("Playoff matches: " . OfficialMatch::where('stage_id', $playoffStage->id)->count());
            $this->command->info("Completed matches: 10");
        });
    }
}
EOF

echo "All files created successfully!"
echo ""
echo "Next steps:"
echo "1. Run: php artisan migrate"
echo "2. Run: php artisan db:seed --class=OfficialTournamentDemoSeeder"
echo ""
echo "Files created:"
echo "✓ Migration: database/migrations/2025_06_15_000001_create_official_tournament_tables.php"
echo "✓ Models in: app/OfficialTournaments/Models/ (with 'Official' prefix)"
echo "✓ Factories in: database/factories/App/OfficialTournaments/Models/"
echo "✓ Seeder: database/seeders/OfficialTournamentDemoSeeder.php"
echo ""
echo "All tables now have 'official_' prefix:"
echo "  - official_tournaments"
echo "  - official_stages"
echo "  - official_teams"
echo "  - official_team_members"
echo "  - official_participants"
echo "  - official_pool_tables"
echo "  - official_matches"
echo "  - official_match_sets"
echo "  - official_schedule_slots"
