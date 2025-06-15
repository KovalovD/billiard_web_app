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
        $endDate = (clone $startDate)->modify('+'.rand(1, 3).' days');

        return [
            'name'       => $this->faker->sentence(3).' Championship',
            'discipline' => $this->faker->randomElement(['8-ball', '9-ball', '10-ball', 'snooker']),
            'start_at'   => $startDate,
            'end_at'     => $endDate,
            'city_id'    => City::factory(),
            'club_id'    => Club::factory(),
            'entry_fee'  => $this->faker->randomElement([0, 100, 200, 500]),
            'prize_pool' => $this->faker->randomElement([1000, 2500, 5000, 10000]),
            'format'     => [
                'race_to'         => $this->faker->randomElement([5, 7, 9]),
                'alternate_break' => $this->faker->boolean(),
            ],
        ];
    }
}
