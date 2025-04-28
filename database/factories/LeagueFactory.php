<?php

namespace database\factories;

use App\Core\Models\Game;
use App\Leagues\Models\League;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<League>
 */
class LeagueFactory extends Factory
{

    protected $model = League::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_id'                        => Game::factory()->create(),
            'name'                           => $this->faker->words(2, true), // например: "Furious League"
            'start_rating'                   => 1000,
            'rating_type'                    => 'elo',
            'rating_change_for_winners_rule' => [
                ['range' => [0, 50], 'strong' => +25, 'weak' => +25],
                ['range' => [51, 100], 'strong' => +20, 'weak' => +30],
                ['range' => [101, 200], 'strong' => +15, 'weak' => +35],
                ['range' => [201, 10000], 'strong' => +10, 'weak' => +40],
            ],
            'rating_change_for_losers_rule'  => [
                ['range' => [0, 50], 'strong' => -25, 'weak' => -25],
                ['range' => [51, 100], 'strong' => -20, 'weak' => -30],
                ['range' => [101, 200], 'strong' => -15, 'weak' => -35],
                ['range' => [201, 10000], 'strong' => -10, 'weak' => -40],
            ],
        ];
    }
}
