<?php

namespace Database\Factories;

use App\Core\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{

    protected $model = Country::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'      => $this->faker->words(2, true), // например: "Furious League"
            'flag_path' => $this->faker->imageUrl(64, 64),
        ];
    }
}
