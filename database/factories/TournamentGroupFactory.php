<?php

namespace Database\Factories;

use App\Tournaments\Models\Tournament;
use App\Tournaments\Models\TournamentGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TournamentGroup>
 */
class TournamentGroupFactory extends Factory
{
    protected $model = TournamentGroup::class;

    public function definition(): array
    {
        $groupNumber = $this->faker->numberBetween(1, 8);

        return [
            'tournament_id'    => Tournament::factory(),
            'name'             => 'Group '.chr(64 + $groupNumber), // Group A, B, C, etc.
            'display_name'     => $this->faker->optional()->words(2, true),
            'group_number'     => $groupNumber,
            'max_participants' => $this->faker->numberBetween(4, 8),
            'advance_count'    => $this->faker->numberBetween(1, 3),
            'is_completed'     => $this->faker->boolean(20), // 20% chance of being completed
            'standings_cache'  => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_completed'    => true,
            'standings_cache' => [
                [
                    'participant_id'   => 1,
                    'participant_name' => 'Player One',
                    'position'         => 1,
                    'matches_played'   => 3,
                    'wins'             => 3,
                    'losses'           => 0,
                    'games_for'        => 9,
                    'games_against'    => 2,
                    'games_difference' => 7,
                    'points'           => 9,
                    'win_percentage'   => 100,
                ],
                [
                    'participant_id'   => 2,
                    'participant_name' => 'Player Two',
                    'position'         => 2,
                    'matches_played'   => 3,
                    'wins'             => 2,
                    'losses'           => 1,
                    'games_for'        => 7,
                    'games_against'    => 5,
                    'games_difference' => 2,
                    'points'           => 6,
                    'win_percentage'   => 66.67,
                ],
            ],
        ]);
    }
}
