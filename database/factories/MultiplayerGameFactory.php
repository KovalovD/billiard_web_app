<?php

namespace Database\Factories;

use App\Core\Models\Game;
use App\Core\Models\User;
use App\Leagues\Models\League;
use App\Matches\Models\MultiplayerGame;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class MultiplayerGameFactory extends Factory
{
    protected $model = MultiplayerGame::class;

    public function definition(): array
    {
        return [
            'name'                   => $this->faker->name(),
            'status'                 => $this->faker->word(),
            'initial_lives'          => $this->faker->randomNumber(),
            'max_players'            => $this->faker->randomNumber(),
            'registration_ends_at'   => Carbon::now(),
            'started_at'             => Carbon::now(),
            'completed_at'           => Carbon::now(),
            'created_at'             => Carbon::now(),
            'updated_at'             => Carbon::now(),
            'moderator_user_id'      => User::factory(),
            'allow_player_targeting' => $this->faker->boolean(),
            'entrance_fee'           => $this->faker->randomNumber(),
            'first_place_percent'    => $this->faker->randomNumber(),
            'second_place_percent'   => $this->faker->randomNumber(),
            'grand_final_percent'    => $this->faker->randomNumber(),
            'penalty_fee'            => $this->faker->randomNumber(),
            'prize_pool'             => $this->faker->words(),
            'current_player_id'      => User::factory(),
            'next_turn_order'        => $this->faker->randomNumber(),

            'league_id' => League::factory(),
            'game_id'   => Game::factory(),
        ];
    }
}
