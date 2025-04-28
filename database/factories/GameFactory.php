<?php

namespace Database\Factories;

use App\Core\Models\Game;
use App\Matches\Enums\GameType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Game>
 */
class GameFactory extends Factory
{

    protected $model = Game::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true), // например: "Furious League"
            'type' => $this->faker->randomElement(GameType::cases()),
        ];
    }
}
