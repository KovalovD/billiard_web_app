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
            'name'          => 'Table '.$this->faker->numberBetween(1, 10),
            'cloth_speed'   => $this->faker->randomElement(['slow', 'medium', 'fast']),
        ];
    }
}
