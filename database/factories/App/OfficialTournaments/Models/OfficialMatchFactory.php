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
            'stage_id'     => OfficialStage::factory(),
            'round'        => $this->faker->numberBetween(1, 5),
            'bracket'      => $this->faker->randomElement(['W', 'L']),
            'scheduled_at' => $this->faker->dateTimeBetween('now', '+1 week'),
            'table_id'     => OfficialPoolTable::factory(),
            'status'       => $this->faker->randomElement(['pending', 'ongoing', 'finished']),
            'metadata'     => [],
        ];
    }
}
