<?php

namespace Database\Factories\App\OfficialTournaments\Models;

use App\Core\Models\User;
use App\OfficialTournaments\Models\OfficialParticipant;
use App\OfficialTournaments\Models\OfficialStage;
use App\OfficialTournaments\Models\OfficialTeam;
use Illuminate\Database\Eloquent\Factories\Factory;

class OfficialParticipantFactory extends Factory
{
    protected $model = OfficialParticipant::class;

    public function definition()
    {
        $isTeam = $this->faker->boolean(20); // 20% chance of team

        return [
            'stage_id'        => OfficialStage::factory(),
            'user_id'         => $isTeam ? null : User::factory(),
            'team_id'         => $isTeam ? OfficialTeam::factory() : null,
            'seed'            => $this->faker->numberBetween(1, 32),
            'rating_snapshot' => $this->faker->numberBetween(1000, 2500),
        ];
    }
}
