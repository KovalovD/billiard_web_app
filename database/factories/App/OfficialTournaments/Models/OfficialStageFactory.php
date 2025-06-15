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
            'type'          => $this->faker->randomElement(['single_elim', 'double_elim', 'swiss', 'group']),
            'number'        => 1,
            'settings'      => [
                'best_of'           => $this->faker->randomElement([1, 3, 5, 7]),
                'third_place_match' => $this->faker->boolean(),
            ],
        ];
    }
}
