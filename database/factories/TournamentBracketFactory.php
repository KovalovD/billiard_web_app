<?php

namespace Database\Factories;

use App\Tournaments\Models\Tournament;
use App\Tournaments\Models\TournamentBracket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TournamentBracket>
 */
class TournamentBracketFactory extends Factory
{
    protected $model = TournamentBracket::class;

    public function definition(): array
    {
        $participantCount = $this->faker->randomElement([8, 16, 32]);
        $rounds = ceil(log($participantCount, 2));

        return [
            'tournament_id'         => Tournament::factory(),
            'bracket_type'          => $this->faker->randomElement(['main', 'upper', 'lower', 'consolation']),
            'total_rounds'          => $rounds,
            'total_participants'    => $participantCount,
            'is_active'             => $this->faker->boolean(80),
            'bracket_structure'     => $this->generateBracketStructure($rounds),
            'participant_positions' => $this->generateParticipantPositions($participantCount),
            'advancement_rules'     => [
                'elimination_type' => 'single',
                'auto_advance'     => true,
            ],
            'current_round'         => $this->faker->numberBetween(1, $rounds),
            'is_completed'          => $this->faker->boolean(20),
            'started_at'            => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
            'completed_at'          => $this->faker->optional()->dateTimeBetween('now', '+1 month'),
        ];
    }

    public function singleElimination(): static
    {
        return $this->state(fn(array $attributes) => [
            'bracket_type'      => 'main',
            'advancement_rules' => [
                'elimination_type' => 'single',
                'auto_advance'     => true,
            ],
        ]);
    }

    public function upperBracket(): static
    {
        return $this->state(fn(array $attributes) => [
            'bracket_type'      => 'upper',
            'advancement_rules' => [
                'elimination_type'  => 'double',
                'auto_advance'      => true,
                'loser_destination' => 'lower_bracket',
            ],
        ]);
    }

    public function lowerBracket(): static
    {
        return $this->state(fn(array $attributes) => [
            'bracket_type'      => 'lower',
            'advancement_rules' => [
                'elimination_type' => 'single',
                'auto_advance'     => true,
                'feeds_from'       => 'upper_bracket',
            ],
        ]);
    }

    private function generateBracketStructure(int $rounds): array
    {
        $structure = [];

        for ($round = 1; $round <= $rounds; $round++) {
            $structure[$round] = [
                'round_number'  => $round,
                'matches_count' => ceil(pow(2, $rounds - $round)),
                'is_completed'  => $this->faker->boolean(30),
            ];
        }

        return $structure;
    }

    private function generateParticipantPositions(int $participantCount): array
    {
        $positions = [];

        for ($i = 1; $i <= $participantCount; $i++) {
            $positions[$i] = [
                'participant_id'   => $this->faker->numberBetween(1, 100),
                'participant_type' => 'player',
                'seed'             => $i,
            ];
        }

        return $positions;
    }
}
