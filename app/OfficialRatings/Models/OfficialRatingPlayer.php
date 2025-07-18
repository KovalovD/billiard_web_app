<?php

namespace App\OfficialRatings\Models;

use App\Core\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficialRatingPlayer extends Model
{
    protected $fillable = [
        'official_rating_id',
        'user_id',
        'rating_points',
        'position',
        'tournaments_played',
        'tournaments_won',
        'last_tournament_at',
        'is_active',
        'tournament_records',
        'total_bonus_amount',
        'total_achievement_amount',
        'total_prize_amount',
        'total_killer_pool_prize_amount',
    ];

    protected $casts = [
        'last_tournament_at'             => 'datetime',
        'is_active'                      => 'boolean',
        'tournament_records'             => 'array',
        'total_bonus_amount'             => 'decimal:2',
        'total_achievement_amount'       => 'decimal:2',
        'total_prize_amount'             => 'decimal:2',
        'total_killer_pool_prize_amount' => 'decimal:2',
    ];

    protected $with = ['user'];

    public function officialRating(): BelongsTo
    {
        return $this->belongsTo(OfficialRating::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getWinRateAttribute(): float
    {
        if ($this->tournaments_played === 0) {
            return 0;
        }

        return round(($this->tournaments_won / $this->tournaments_played) * 100, 2);
    }

    public function isTopPlayer(): bool
    {
        return $this->position <= 10;
    }

    /**
     * Get total money earned (prize + bonus + achievement + killer pool prize)
     */
    public function getTotalMoneyEarnedAttribute(): float
    {
        return (float) ($this->total_prize_amount + $this->total_achievement_amount + $this->total_killer_pool_prize_amount);
    }

    /**
     * Add or update tournament record with prize, bonus and achievement amounts
     */
    public function addTournament(
        int $tournamentId,
        int $ratingPoints,
        Carbon $tournamentFinishDate,
        bool $won = false,
        float $prizeAmount = 0,
        float $bonusAmount = 0,
        float $achievementAmount = 0,
        float $killerPoolPrizeAmount = 0,
    ): void {
        $records = $this->tournament_records ?? [];

        // Check if tournament already exists
        $existingTournamentIndex = null;
        foreach ($records as $index => $record) {
            if ($record['tournament_id'] === $tournamentId) {
                $existingTournamentIndex = $index;
                break;
            }
        }

        $oldPoints = 0;
        $oldPrizeAmount = 0;
        $oldBonusAmount = 0;
        $oldAchievementAmount = 0;
        $oldKillerPoolPrizeAmount = 0;
        $wasAlreadyWon = false;

        if ($existingTournamentIndex !== null) {
            // Tournament exists, get old values
            $oldRecord = $records[$existingTournamentIndex];
            $oldPoints = $oldRecord['rating_points'];
            $oldPrizeAmount = $oldRecord['prize_amount'] ?? 0;
            $oldBonusAmount = $oldRecord['bonus_amount'] ?? 0;
            $oldAchievementAmount = $oldRecord['achievement_amount'] ?? 0;
            $oldKillerPoolPrizeAmount = $oldRecord['killer_pool_prize_amount'] ?? 0;
            $wasAlreadyWon = $oldRecord['won'];

            // Update existing record
            $records[$existingTournamentIndex] = [
                'tournament_id'            => $tournamentId,
                'rating_points'            => $ratingPoints,
                'prize_amount'             => $prizeAmount,
                'bonus_amount'             => $bonusAmount,
                'achievement_amount'       => $achievementAmount,
                'killer_pool_prize_amount' => $killerPoolPrizeAmount,
                'tournament_date'          => $tournamentFinishDate->format('Y-m-d'),
                'won'                      => $won,
                'updated_at'               => now()->format('Y-m-d H:i:s'),
            ];
        } else {
            // Add new tournament record
            $records[] = [
                'tournament_id'            => $tournamentId,
                'rating_points'            => $ratingPoints,
                'prize_amount'             => $prizeAmount,
                'bonus_amount'             => $bonusAmount,
                'achievement_amount'       => $achievementAmount,
                'killer_pool_prize_amount' => $killerPoolPrizeAmount,
                'tournament_date'          => $tournamentFinishDate->format('Y-m-d'),
                'won'                      => $won,
                'added_at'                 => now()->format('Y-m-d H:i:s'),
            ];

            // Increment tournaments played only if it's a new tournament
            $this->increment('tournaments_played');
        }

        // Update rating points: remove old points and add new points
        $this->rating_points = $this->rating_points - $oldPoints + $ratingPoints;

        // Update prize, bonus and achievement amounts
        $this->total_prize_amount = $this->total_prize_amount - $oldPrizeAmount + $prizeAmount;
        $this->total_bonus_amount = $this->total_bonus_amount - $oldBonusAmount + $bonusAmount;
        $this->total_achievement_amount = $this->total_achievement_amount - $oldAchievementAmount + $achievementAmount;
        $this->total_killer_pool_prize_amount = $this->total_killer_pool_prize_amount - $oldKillerPoolPrizeAmount + $killerPoolPrizeAmount;

        // Update tournaments won count
        if ($won && !$wasAlreadyWon) {
            $this->increment('tournaments_won');
        } elseif (!$won && $wasAlreadyWon) {
            $this->decrement('tournaments_won');
        }

        // Update other fields
        $this->last_tournament_at = $tournamentFinishDate;
        $this->tournament_records = $records;

        $this->save();
    }

    /**
     * Remove tournament record
     */
    public function removeTournament(int $tournamentId): bool
    {
        $records = $this->tournament_records ?? [];

        $foundIndex = null;
        $removedRecord = null;

        foreach ($records as $index => $record) {
            if ($record['tournament_id'] === $tournamentId) {
                $foundIndex = $index;
                $removedRecord = $record;
                break;
            }
        }

        if ($foundIndex === null) {
            return false; // Tournament not found
        }

        // Remove the record
        unset($records[$foundIndex]);
        $records = array_values($records); // Re-index array

        // Update rating points
        $this->rating_points -= $removedRecord['rating_points'];

        // Update prize, bonus and achievement amounts
        $this->total_prize_amount -= $removedRecord['prize_amount'] ?? 0;
        $this->total_bonus_amount -= $removedRecord['bonus_amount'] ?? 0;
        $this->total_achievement_amount -= $removedRecord['achievement_amount'] ?? 0;
        $this->total_killer_pool_prize_amount -= $removedRecord['killer_pool_prize_amount'] ?? 0;

        // Update tournaments count
        $this->decrement('tournaments_played');

        if ($removedRecord['won']) {
            $this->decrement('tournaments_won');
        }

        // Update last tournament date
        if (!empty($records)) {
            $latestTournament = collect($records)->sortByDesc('tournament_date')->first();
            $this->last_tournament_at = Carbon::parse($latestTournament['tournament_date']);
        } else {
            $this->last_tournament_at = null;
        }

        $this->tournament_records = $records;
        $this->save();

        return true;
    }

    /**
     * Check if tournament is already recorded
     */
    public function hasTournamentRecord(int $tournamentId): bool
    {
        return $this->getTournamentRecord($tournamentId) !== null;
    }

    /**
     * Get tournament record by tournament ID
     */
    public function getTournamentRecord(int $tournamentId): ?array
    {
        $records = $this->tournament_records ?? [];

        return array_find($records, static fn($record) => $record['tournament_id'] === $tournamentId);
    }

    /**
     * Recalculate rating from tournament records (for data integrity)
     */
    public function recalculateFromRecords(): void
    {
        $records = $this->tournament_records ?? [];

        if (empty($records)) {
            $this->rating_points = $this->officialRating->initial_rating;
            $this->tournaments_played = 0;
            $this->tournaments_won = 0;
            $this->total_prize_amount = 0;
            $this->total_bonus_amount = 0;
            $this->total_achievement_amount = 0;
            $this->total_killer_pool_prize_amount = 0;
            $this->last_tournament_at = null;
        } else {
            $this->rating_points = $this->officialRating->initial_rating + $this->getTotalPointsFromRecords();
            $this->tournaments_played = count($records);
            $this->tournaments_won = collect($records)->where('won', true)->count();
            $this->total_prize_amount = collect($records)->sum('prize_amount');
            $this->total_bonus_amount = collect($records)->sum('bonus_amount');
            $this->total_achievement_amount = collect($records)->sum('achievement_amount');
            $this->total_killer_pool_prize_amount = collect($records)->sum('killer_pool_prize_amount');
            $this->last_tournament_at = Carbon::parse(collect($records)->max('tournament_date'));
        }

        $this->save();
    }

    /**
     * Get total rating points from all tournament records (for verification)
     */
    public function getTotalPointsFromRecords(): int
    {
        $records = $this->tournament_records ?? [];

        return collect($records)->sum('rating_points');
    }

    /**
     * Get total prize amount from all tournament records
     */
    public function getTotalPrizeFromRecords(): float
    {
        $records = $this->tournament_records ?? [];

        return collect($records)->sum('prize_amount');
    }

    /**
     * Get total bonus amount from all tournament records
     */
    public function getTotalBonusFromRecords(): float
    {
        $records = $this->tournament_records ?? [];

        return collect($records)->sum('bonus_amount');
    }

    /**
     * Get total achievement amount from all tournament records
     */
    public function getTotalAchievementFromRecords(): float
    {
        $records = $this->tournament_records ?? [];

        return collect($records)->sum('achievement_amount');
    }

    public function getDivision(): string
    {
        return match (true) {
            $this->position <= 8 => 'Elite',
            $this->position > 8 && $this->position <= 16 => 'S',
            $this->position > 16 && $this->position <= 24 => 'A',
            $this->position > 24 && $this->position <= 64 => 'B',
            $this->position > 64 => 'C',
        };
    }
}
