<?php

namespace Database\Factories;

use App\Tournaments\Models\Tournament;
use App\Tournaments\Models\TournamentGroup;
use App\Tournaments\Models\TournamentTeam;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TournamentTeam>
 */
class TournamentTeamFactory extends Factory
{
    protected $model = TournamentTeam::class;

    public function definition(): array
    {
        $teamNames = [
            'Fire Dragons', 'Ice Wolves', 'Thunder Hawks', 'Storm Eagles',
            'Golden Lions', 'Silver Panthers', 'Crimson Bears', 'Azure Tigers',
            'Emerald Serpents', 'Diamond Sharks', 'Ruby Falcons', 'Sapphire Foxes',
        ];

        $teamName = $this->faker->randomElement($teamNames);

        return [
            'tournament_id'    => Tournament::factory(),
            'name'             => $teamName,
            'short_name'       => $this->faker->optional()->lexify('???'),
            'seed'             => $this->faker->optional()->numberBetween(1, 16),
            'group_id'         => null, // Can be set via relationship
            'bracket_position' => $this->faker->optional()->numberBetween(1, 32),
            'is_active'        => $this->faker->boolean(90), // 90% chance of being active
            'roster_data'      => [
                'formation' => $this->faker->randomElement(['2-1', '1-2', 'rotation']),
                'strategy'  => $this->faker->randomElement(['aggressive', 'defensive', 'balanced']),
            ],
        ];
    }

    public function withGroup(): static
    {
        return $this->state(fn(array $attributes) => [
            'group_id' => TournamentGroup::factory(),
        ]);
    }

    public function seeded(int $seed): static
    {
        return $this->state(fn(array $attributes) => [
            'seed' => $seed,
        ]);
    }
}
