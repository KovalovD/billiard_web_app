<?php

namespace Database\Factories;

use App\Core\Models\City;
use App\Core\Models\Club;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Club>
 */
class ClubFactory extends Factory
{

    protected $model = Club::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'    => $this->faker->words(2, true), // например: "Furious League"
            'city_id' => City::factory(),
        ];
    }
}
