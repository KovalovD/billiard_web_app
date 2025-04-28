<?php

namespace database\factories;

use App\Core\Models\User;
use App\Leagues\Models\League;
use App\Leagues\Models\Rating;
use Illuminate\Database\Eloquent\Factories\Factory;


/**
 * @extends Factory<Rating>
 */
class RatingFactory extends Factory
{
    protected $model = Rating::class;

    public function definition(): array
    {
        return [
            'league_id' => League::factory(),
            'user_id'   => User::factory(),
            'rating'    => 1000,
            'position'  => 1,
            'is_active' => true,
        ];
    }
}
