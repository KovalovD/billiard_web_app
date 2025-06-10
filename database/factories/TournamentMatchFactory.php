<?php

namespace Database\Factories;

use App\Core\Models\Club;
use App\Tournaments\Models\Tournament;
use App\Tournaments\Models\TournamentGroup;
use App\Tournaments\Models\TournamentMatch;
use App\Tournaments\Models\TournamentPlayer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TournamentMatch>
 */
class TournamentMatchFactory extends Factory
{
    protected $model = TournamentMatch::class;

    public function definition(): array
    {
        return [
            'tournament_id'       => Tournament::factory(),
            'match_type'          => $this->faker->randomElement(['group', 'bracket', 'final']),
            'round_number'        => $this->faker->numberBetween(1, 8),
            'match_number'        => $this->faker->numberBetween(1, 16),
            'bracket_type'        => $this->faker->optional()->randomElement(['upper', 'lower', 'main', 'final']),
            'group_id'            => null,
            'participant_1_id'    => TournamentPlayer::factory(),
            'participant_1_type'  => 'player',
            'participant_2_id'    => TournamentPlayer::factory(),
            'participant_2_type'  => 'player',
            'status'              => $this->faker->randomElement(['pending', 'in_progress', 'completed', 'cancelled']),
            'scores'              => null,
            'participant_1_score' => 0,
            'participant_2_score' => 0,
            'winner_id'           => null,
            'winner_type'         => null,
            'scheduled_at'        => $this->faker->optional()->dateTimeBetween('now', '+1 month'),
            'started_at'          => null,
            'completed_at'        => null,
            'table_number'        => $this->faker->optional()->numberBetween(1, 20),
            'club_id'             => $this->faker->optional()->randomElement([Club::factory(), null]),
            'notes'               => $this->faker->optional()->sentence(),
            'referee'             => $this->faker->optional()->name(),
            'match_data'          => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status'       => 'pending',
            'started_at'   => null,
            'completed_at' => null,
            'winner_id'    => null,
            'winner_type'  => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $participant1Score = $this->faker->numberBetween(0, 3);
            $participant2Score = $this->faker->numberBetween(0, 3);

            // Ensure no ties
            if ($participant1Score === $participant2Score) {
                $participant1Score++;
            }

            $winnerId = $participant1Score > $participant2Score
                ? $attributes['participant_1_id']
                : $attributes['participant_2_id'];

            $winnerType = $participant1Score > $participant2Score
                ? $attributes['participant_1_type']
                : $attributes['participant_2_type'];

            return [
                'status'              => 'completed',
                'participant_1_score' => $participant1Score,
                'participant_2_score' => $participant2Score,
                'winner_id'           => $winnerId,
                'winner_type'         => $winnerType,
                'started_at'          => $this->faker->dateTimeBetween('-1 week', '-1 hour'),
                'completed_at'        => $this->faker->dateTimeBetween('-1 hour', 'now'),
                'scores'              => $this->generateDetailedScores($participant1Score, $participant2Score),
            ];
        });
    }

    public function groupMatch(): static
    {
        return $this->state(fn(array $attributes) => [
            'match_type'   => 'group',
            'group_id'     => TournamentGroup::factory(),
            'bracket_type' => null,
        ]);
    }

    public function bracketMatch(): static
    {
        return $this->state(fn(array $attributes) => [
            'match_type'   => 'bracket',
            'group_id'     => null,
            'bracket_type' => $this->faker->randomElement(['upper', 'lower', 'main']),
        ]);
    }

    private function generateDetailedScores(int $participant1Score, int $participant2Score): array
    {
        $scores = [];
        $totalGames = $participant1Score + $participant2Score;
        $p1Games = 0;
        $p2Games = 0;

        for ($game = 1; $game <= $totalGames; $game++) {
            if ($p1Games < $participant1Score && $p2Games < $participant2Score) {
                // Random winner for this game
                $p1Wins = $this->faker->boolean();
            } elseif ($p1Games < $participant1Score) {
                $p1Wins = true;
            } else {
                $p1Wins = false;
            }

            $scores[] = [
                'game_number'         => $game,
                'participant_1_score' => $p1Wins ? 1 : 0,
                'participant_2_score' => $p1Wins ? 0 : 1,
            ];

            if ($p1Wins) {
                $p1Games++;
            } else {
                $p2Games++;
            }
        }

        return $scores;
    }
}
