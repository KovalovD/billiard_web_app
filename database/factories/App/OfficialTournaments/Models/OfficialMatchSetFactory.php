<?php

namespace Database\Factories\App\OfficialTournaments\Models;

use App\OfficialTournaments\Models\OfficialMatch;
use App\OfficialTournaments\Models\OfficialMatchSet;
use App\OfficialTournaments\Models\OfficialParticipant;
use Illuminate\Database\Eloquent\Factories\Factory;

class OfficialMatchSetFactory extends Factory
{
    protected $model = OfficialMatchSet::class;

    public function definition()
    {
        $score1 = $this->faker->numberBetween(0, 9);
        $score2 = $this->faker->numberBetween(0, 9);

        return [
            'match_id'              => OfficialMatch::factory(),
            'set_no'                => 1,
            'winner_participant_id' => OfficialParticipant::factory(),
            'score_json'            => [
                'participant1' => $score1,
                'participant2' => $score2,
            ],
        ];
    }
}
