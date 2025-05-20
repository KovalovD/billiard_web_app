<?php

namespace Database\Factories;

use App\Core\Models\Club;
use App\Core\Models\Game;
use App\Leagues\Models\League;
use App\Leagues\Models\Rating;
use App\Matches\Enums\GameStatus;
use App\Matches\Models\MatchGame;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class MatchGameFactory extends Factory
{

    protected $model = MatchGame::class;

    public function definition(): array
    {
        return [
            'status'                    => $this->faker->randomElement(GameStatus::cases()),
            'first_user_score'          => $this->faker->randomNumber(),
            'second_user_score'         => $this->faker->randomNumber(),
            'first_rating_id'           => $rating1 = Rating::factory(),
            'second_rating_id'          => $rating2 = Rating::factory(),
            'winner_rating_id'          => $rating1,
            'loser_rating_id'           => $rating2,
            'rating_change_for_winner'  => $this->faker->randomNumber(),
            'rating_change_for_loser'   => $this->faker->randomNumber(),
            'first_rating_before_game'  => $this->faker->randomNumber(),
            'second_rating_before_game' => $this->faker->randomNumber(),
            'stream_url'                => $this->faker->url(),
            'details'                   => $this->faker->word(),
            'invitation_sent_at'        => Carbon::now(),
            'invitation_available_till' => Carbon::now(),
            'invitation_accepted_at'    => Carbon::now(),
            'finished_at'               => Carbon::now(),
            'result_confirmed'          => $this->faker->words(),
            'created_at'                => Carbon::now(),
            'updated_at'                => Carbon::now(),

            'club_id'   => Club::factory(),
            'game_id'   => Game::factory(),
            'league_id' => League::factory(),
        ];
    }
}
