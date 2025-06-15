<?php

namespace Database\Factories\App\OfficialTournaments\Models;

use App\Core\Models\Club;
use App\OfficialTournaments\Models\OfficialTeam;
use App\OfficialTournaments\Models\OfficialTournament;
use Illuminate\Database\Eloquent\Factories\Factory;

class OfficialTeamFactory extends Factory
{
    protected $model = OfficialTeam::class;

    public function definition()
    {
        return [
            'tournament_id' => OfficialTournament::factory(),
            'name'          => $this->faker->company().' Team',
            'club_id'       => Club::factory(),
            'seed'          => null,
        ];
    }
}
