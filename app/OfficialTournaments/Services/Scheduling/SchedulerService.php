<?php

namespace App\OfficialTournaments\Services\Scheduling;

use App\OfficialTournaments\Models\OfficialMatch;
use App\OfficialTournaments\Models\OfficialPoolTable;
use App\OfficialTournaments\Models\OfficialScheduleSlot;
use App\OfficialTournaments\Models\OfficialStage;
use App\OfficialTournaments\Models\OfficialTournament;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Service for scheduling tournament matches
 *
 * Handles automatic scheduling with constraints:
 * - Table availability
 * - Player rest time between matches
 * - Concurrent match limits
 * - Priority scheduling for TV tables
 */
class SchedulerService
{
    /**
     * Default match duration in minutes
     */
    protected int $defaultMatchDuration = 45;

    /**
     * Minimum rest time between matches for same player (minutes)
     */
    protected int $minimumRestTime = 30;

    /**
     * Maximum concurrent matches per player
     */
    protected int $maxConcurrentPerPlayer = 1;

    /**
     * Auto-schedule all pending matches for a stage
     *
     * @param  OfficialStage  $stage
     * @param  Carbon  $startTime  When to start scheduling
     * @param  array  $options  Scheduling options
     * @return Collection Scheduled matches
     * @throws Throwable
     */
    public function autoSchedule(OfficialStage $stage, Carbon $startTime, array $options = []): Collection
    {
        $this->applyOptions($options);

        $tournament = $stage->tournament;
        $tables = $tournament->poolTables;

        if ($tables->isEmpty()) {
            throw new Exception('No tables available for scheduling');
        }

        // Get all pending matches ordered by priority
        $pendingMatches = $this->getPendingMatchesOrdered($stage);

        // Initialize scheduling state
        $schedule = collect();
        $playerSchedule = collect(); // Track when each player is busy
        $tableSchedule = $this->initializeTableSchedule($tables, $startTime);

        DB::beginTransaction();
        try {
            foreach ($pendingMatches as $match) {
                $scheduledTime = $this->findEarliestSlot(
                    $match,
                    $tableSchedule,
                    $playerSchedule,
                    $startTime,
                );

                if ($scheduledTime) {
                    $this->scheduleMatch($match, $scheduledTime['table'], $scheduledTime['start']);

                    // Update tracking
                    $schedule->push($match);
                    $this->updatePlayerSchedule($playerSchedule, $match, $scheduledTime);
                    $this->updateTableSchedule($tableSchedule, $scheduledTime);
                }
            }

            DB::commit();
            return $schedule;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Apply scheduling options
     */
    protected function applyOptions(array $options): void
    {
        if (isset($options['match_duration'])) {
            $this->defaultMatchDuration = $options['match_duration'];
        }

        if (isset($options['rest_time'])) {
            $this->minimumRestTime = $options['rest_time'];
        }

        if (isset($options['max_concurrent'])) {
            $this->maxConcurrentPerPlayer = $options['max_concurrent'];
        }
    }

    /**
     * Get pending matches ordered by priority
     */
    protected function getPendingMatchesOrdered(OfficialStage $stage): Collection
    {
        return $stage
            ->matches()
            ->where('status', OfficialMatch::STATUS_PENDING)
            ->whereNotNull('metadata->participant1_id')
            ->whereNotNull('metadata->participant2_id')
            ->orderBy('round')
            ->orderBy('bracket')
            ->get()
        ;
    }

    /**
     * Initialize table schedule tracking
     */
    protected function initializeTableSchedule(Collection $tables, Carbon $startTime): Collection
    {
        return $tables->mapWithKeys(function ($table) use ($startTime) {
            return [
                $table->id => [
                    'table'          => $table,
                    'next_available' => $startTime->copy(),
                ],
            ];
        });
    }

    /**
     * Find earliest available slot for match
     */
    protected function findEarliestSlot(
        OfficialMatch $match,
        Collection $tableSchedule,
        Collection $playerSchedule,
        Carbon $baseStartTime,
    ): ?array {
        $participants = $this->getMatchParticipants($match);

        // Find when all players are available
        $playerAvailableTime = $baseStartTime->copy();

        foreach ($participants as $participant) {
            if ($playerSchedule->has($participant->id)) {
                $playerNext = $playerSchedule[$participant->id]['next_available'];
                if ($playerNext->gt($playerAvailableTime)) {
                    $playerAvailableTime = $playerNext->copy();
                }
            }
        }

        // Try to find optimal table first
        $optimalTable = $this->getOptimalTable($match, $playerAvailableTime);

        if ($optimalTable) {
            return [
                'table' => $optimalTable,
                'start' => $playerAvailableTime,
                'end'   => $playerAvailableTime->copy()->addMinutes($this->defaultMatchDuration),
            ];
        }

        // Find any available table
        foreach ($tableSchedule as $tableInfo) {
            $tableAvailable = $tableInfo['next_available'];
            $matchStart = $playerAvailableTime->gt($tableAvailable) ?
                $playerAvailableTime : $tableAvailable;

            if ($this->isSlotAvailable($tableInfo['table'], $matchStart, $this->defaultMatchDuration)) {
                return [
                    'table' => $tableInfo['table'],
                    'start' => $matchStart,
                    'end'   => $matchStart->copy()->addMinutes($this->defaultMatchDuration),
                ];
            }
        }

        return null;
    }

    /**
     * Get match participants
     */
    protected function getMatchParticipants(OfficialMatch $match): Collection
    {
        $participants = collect();

        if ($p1 = $match->getParticipant1()) {
            $participants->push($p1);
        }

        if ($p2 = $match->getParticipant2()) {
            $participants->push($p2);
        }

        return $participants;
    }

    /**
     * Get optimal table for match (TV table for important matches)
     *
     * @param  OfficialMatch  $match
     * @param  Carbon  $startTime
     * @return OfficialPoolTable|null
     */
    public function getOptimalTable(OfficialMatch $match, Carbon $startTime): ?OfficialPoolTable
    {
        $tournament = $match->stage->tournament;
        $tables = $tournament->poolTables;

        // Prioritize TV/feature tables for later rounds
        $isImportantMatch = $this->isImportantMatch($match);

        if ($isImportantMatch) {
            // Look for tables marked as TV/feature tables
            $tvTables = $tables->filter(function ($table) {
                return str_contains(strtolower($table->name), 'tv') ||
                    str_contains(strtolower($table->name), 'feature') ||
                    $table->id === 1; // Often table 1 is the feature table
            });

            foreach ($tvTables as $table) {
                if ($this->isSlotAvailable($table, $startTime, $this->defaultMatchDuration)) {
                    return $table;
                }
            }
        }

        // Find any available table
        return array_find($tables,
            fn($table) => $this->isSlotAvailable($table, $startTime, $this->defaultMatchDuration));

    }

    /**
     * Check if match is important (for TV table allocation)
     */
    protected function isImportantMatch(OfficialMatch $match): bool
    {
        // Finals and semi-finals
        if ($match->round >= 3 || $match->metadata['is_grand_final'] ?? false) {
            return true;
        }

        // Top seeds in early rounds
        $participants = $this->getMatchParticipants($match);
        $topSeedInvolved = $participants->contains(function ($p) {
            return $p->seed <= 4;
        });

        return $topSeedInvolved && $match->round >= 2;
    }

    /**
     * Check if time slot is available
     */
    protected function isSlotAvailable(OfficialPoolTable $table, Carbon $startTime, int $duration): bool
    {
        $endTime = $startTime->copy()->addMinutes($duration);

        return !OfficialScheduleSlot::where('table_id', $table->id)
            ->where(function ($query) use ($startTime, $endTime) {
                $query
                    ->whereBetween('start_at', [$startTime, $endTime])
                    ->orWhereBetween('end_at', [$startTime, $endTime])
                    ->orWhere(function ($q) use ($startTime, $endTime) {
                        $q
                            ->where('start_at', '<=', $startTime)
                            ->where('end_at', '>=', $endTime)
                        ;
                    })
                ;
            })
            ->exists()
        ;
    }

    /**
     * Schedule a specific match
     *
     * @param  OfficialMatch  $match
     * @param  OfficialPoolTable  $table
     * @param  Carbon  $startTime
     * @return OfficialScheduleSlot
     * @throws Throwable
     */
    public function scheduleMatch(
        OfficialMatch $match,
        OfficialPoolTable $table,
        Carbon $startTime,
    ): OfficialScheduleSlot {
        // Check if slot is available
        if (!$this->isSlotAvailable($table, $startTime, $this->defaultMatchDuration)) {
            throw new RuntimeException('Time slot is not available');
        }

        return DB::transaction(function () use ($match, $table, $startTime) {
            // Create schedule slot
            $slot = OfficialScheduleSlot::create([
                'tournament_id' => $table->tournament_id,
                'table_id'      => $table->id,
                'start_at'      => $startTime,
                'end_at'        => $startTime->copy()->addMinutes($this->defaultMatchDuration),
            ]);

            // Update match
            $match->update([
                'scheduled_at' => $startTime,
                'table_id'     => $table->id,
            ]);

            return $slot;
        });
    }

    /**
     * Update player schedule tracking
     */
    protected function updatePlayerSchedule(
        Collection $playerSchedule,
        OfficialMatch $match,
        array $scheduledTime,
    ): void {
        $participants = $this->getMatchParticipants($match);
        $nextAvailable = $scheduledTime['end']->copy()->addMinutes($this->minimumRestTime);

        foreach ($participants as $participant) {
            $playerSchedule[$participant->id] = [
                'next_available' => $nextAvailable,
                'last_match'     => $match->id,
            ];
        }
    }

    /**
     * Update table schedule tracking
     */
    protected function updateTableSchedule(Collection $tableSchedule, array $scheduledTime): void
    {
        $tableId = $scheduledTime['table']->id;
        $tableSchedule[$tableId] = [
            'table'          => $scheduledTime['table'],
            'next_available' => $scheduledTime['end']->copy(),
        ];
    }

    /**
     * Reschedule a match
     *
     * @param  OfficialMatch  $match
     * @param  OfficialPoolTable  $newTable
     * @param  Carbon  $newStartTime
     * @return void
     * @throws Throwable
     * @throws Throwable
     */
    public function rescheduleMatch(OfficialMatch $match, OfficialPoolTable $newTable, Carbon $newStartTime): void
    {
        DB::transaction(function () use ($match, $newTable, $newStartTime) {
            // Remove old schedule slot if exists
            if ($match->scheduled_at && $match->table_id) {
                OfficialScheduleSlot::where('tournament_id', $newTable->tournament_id)
                    ->where('table_id', $match->table_id)
                    ->where('start_at', $match->scheduled_at)
                    ->delete()
                ;
            }

            // Create new schedule
            $this->scheduleMatch($match, $newTable, $newStartTime);

            // Propagate changes to dependent matches
            $this->propagateScheduleChanges($match);
        });
    }

    /**
     * Propagate schedule changes to dependent matches
     */
    protected function propagateScheduleChanges(OfficialMatch $match): void
    {
        // This would handle updating any matches that depend on this one
        // For example, ensuring next round matches aren't scheduled before this one finishes
    }

    /**
     * Generate schedule slots for tournament
     *
     * @param  OfficialTournament  $tournament
     * @param  Carbon  $startDate
     * @param  Carbon  $endDate
     * @param  array  $dailySchedule  Operating hours per day
     * @return Collection
     */
    public function generateScheduleSlots(
        OfficialTournament $tournament,
        Carbon $startDate,
        Carbon $endDate,
        array $dailySchedule = [],
    ): Collection {
        $defaultSchedule = [
            'start'       => '09:00',
            'end'         => '22:00',
            'break_start' => '13:00',
            'break_end'   => '14:00',
        ];

        $dailySchedule = array_merge($defaultSchedule, $dailySchedule);
        $slots = collect();

        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            // Skip if specific day is marked as off
            if ($this->isDayOff($currentDate, $dailySchedule)) {
                $currentDate->addDay();
                continue;
            }

            foreach ($tournament->poolTables as $table) {
                // Morning session
                $morningStart = $currentDate->copy()->setTimeFromTimeString($dailySchedule['start']);
                $breakStart = $currentDate->copy()->setTimeFromTimeString($dailySchedule['break_start']);

                $this->generateSlotsForPeriod($slots, $table, $morningStart, $breakStart);

                // Afternoon/Evening session
                $breakEnd = $currentDate->copy()->setTimeFromTimeString($dailySchedule['break_end']);
                $dayEnd = $currentDate->copy()->setTimeFromTimeString($dailySchedule['end']);

                $this->generateSlotsForPeriod($slots, $table, $breakEnd, $dayEnd);
            }

            $currentDate->addDay();
        }

        return $slots;
    }

    /**
     * Check if day is off
     */
    protected function isDayOff(Carbon $date, array $schedule): bool
    {
        // Check for specific off days in schedule
        if (isset($schedule['off_days'])) {
            $dayOfWeek = strtolower($date->format('l'));
            return in_array($dayOfWeek, $schedule['off_days'], true);
        }

        return false;
    }

    /**
     * Generate slots for a time period
     */
    protected function generateSlotsForPeriod(
        Collection $slots,
        OfficialPoolTable $table,
        Carbon $start,
        Carbon $end,
    ): void {
        $current = $start->copy();

        while ($current->copy()->addMinutes($this->defaultMatchDuration)->lte($end)) {
            $slots->push([
                'table_id'  => $table->id,
                'start_at'  => $current->copy(),
                'end_at'    => $current->copy()->addMinutes($this->defaultMatchDuration),
                'available' => true,
            ]);

            $current->addMinutes($this->defaultMatchDuration + 15); // 15 min buffer
        }
    }

    /**
     * Find conflicts for a proposed schedule change
     *
     * @param  OfficialMatch  $match
     * @param  Carbon  $proposedTime
     * @param  OfficialPoolTable  $proposedTable
     * @return Collection Conflicts found
     */
    public function findScheduleConflicts(
        OfficialMatch $match,
        Carbon $proposedTime,
        OfficialPoolTable $proposedTable,
    ): Collection {
        $conflicts = collect();
        $endTime = $proposedTime->copy()->addMinutes($this->defaultMatchDuration);

        // Check table conflicts
        $tableConflicts = OfficialScheduleSlot::where('table_id', $proposedTable->id)
            ->where(function ($query) use ($proposedTime, $endTime) {
                $query
                    ->whereBetween('start_at', [$proposedTime, $endTime])
                    ->orWhereBetween('end_at', [$proposedTime, $endTime])
                    ->orWhere(function ($q) use ($proposedTime, $endTime) {
                        $q
                            ->where('start_at', '<=', $proposedTime)
                            ->where('end_at', '>=', $endTime)
                        ;
                    })
                ;
            })
            ->get()
        ;

        if ($tableConflicts->isNotEmpty()) {
            $conflicts->push([
                'type'      => 'table',
                'message'   => 'Table is already booked',
                'conflicts' => $tableConflicts,
            ]);
        }

        // Check player conflicts
        $participants = $this->getMatchParticipants($match);

        foreach ($participants as $participant) {
            $playerConflicts = $this->findPlayerConflicts($participant, $proposedTime, $endTime);

            if ($playerConflicts->isNotEmpty()) {
                $conflicts->push([
                    'type'        => 'player',
                    'participant' => $participant,
                    'message'     => 'Player has conflicting matches',
                    'conflicts'   => $playerConflicts,
                ]);
            }
        }

        return $conflicts;
    }

    /**
     * Find player schedule conflicts
     */
    protected function findPlayerConflicts($participant, Carbon $startTime, Carbon $endTime): Collection
    {
        return OfficialMatch::query()
            ->where(static function ($query) use ($participant) {
                $query
                    ->whereJsonContains('metadata->participant1_id', $participant->id)
                    ->orWhereJsonContains('metadata->participant2_id', $participant->id)
                ;
            })
            ->whereNotNull('scheduled_at')
            ->where(function ($query) use ($startTime, $endTime) {
                $query
                    ->whereBetween('scheduled_at', [$startTime, $endTime])
                    ->orWhere(function ($q) use ($startTime) {
                        $q
                            ->where('scheduled_at', '<=', $startTime)
                            ->where('scheduled_at', '>=', $startTime->copy()->addMinutes($this->defaultMatchDuration))
                        ;
                    })
                ;
            })
            ->get()
        ;
    }
}
