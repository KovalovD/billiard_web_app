<?php

namespace App\OfficialTournaments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfficialStage extends Model
{
    use HasFactory;

    const string TYPE_SINGLE_ELIM = 'single_elim';
    const string TYPE_DOUBLE_ELIM = 'double_elim';
    const string TYPE_SWISS = 'swiss';
    const string TYPE_GROUP = 'group';
    const string TYPE_ROUND_ROBIN = 'round_robin';
    const string TYPE_CUSTOM = 'custom';
    protected $table = 'official_stages';
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

    public function matches(): HasMany
    {
        return $this->hasMany(OfficialMatch::class, 'stage_id');
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
        return in_array($this->type, [self::TYPE_SINGLE_ELIM, self::TYPE_DOUBLE_ELIM], true);
    }

    /**
     * Check if stage is group type
     */
    public function isGroup(): bool
    {
        return in_array($this->type, [self::TYPE_GROUP, self::TYPE_ROUND_ROBIN], true);
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

    public function participants(): HasMany
    {
        return $this->hasMany(OfficialParticipant::class, 'stage_id');
    }

    /**
     * Get setting value by key
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }
}
