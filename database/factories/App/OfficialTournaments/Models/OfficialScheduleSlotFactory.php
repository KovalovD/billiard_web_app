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
            'table_id'      => OfficialPoolTable::factory(),
            'start_at'      => $startTime,
            'end_at'        => $endTime,
        ];
    }
}
