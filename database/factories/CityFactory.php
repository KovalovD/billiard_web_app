<?php

namespace Database\Factories;

use App\Core\Models\City;
use App\Core\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<City>
 */
class CityFactory extends Factory
{

    protected $model = City::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'       => $this->faker->words(2, true), // например: "Furious League"
            'country_id' => Country::factory(),
        ];
    }
}
