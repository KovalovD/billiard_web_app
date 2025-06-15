<?php

namespace database\seeders;

use App\Core\Models\City;
use App\Core\Models\Club;
use App\Core\Models\User;
use App\OfficialTournaments\Models\OfficialMatch;
use App\OfficialTournaments\Models\OfficialMatchSet;
use App\OfficialTournaments\Models\OfficialParticipant;
use App\OfficialTournaments\Models\OfficialPoolTable;
use App\OfficialTournaments\Models\OfficialStage;
use App\OfficialTournaments\Models\OfficialTournament;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OfficialTournamentDemoSeeder extends Seeder
{
    public function run()
    {
        DB::transaction(function () {
            // Create demo tournament
            $tournament = OfficialTournament::create([
                'name'       => 'WinnerBreak Championship 2025',
                'discipline' => '9-ball',
                'start_at'   => now()->addDays(7),
                'end_at'     => now()->addDays(9),
                'city_id'    => City::first()->id ?? City::factory()->create()->id,
                'club_id'    => Club::first()->id ?? Club::factory()->create()->id,
                'entry_fee'  => 500,
                'prize_pool' => 50000,
                'format'     => [
                    'race_to'         => 7,
                    'alternate_break' => true,
                ],
            ]);

            // Create 4 pool tables
            $tables = collect(range(1, 4))->map(function ($i) use ($tournament) {
                return OfficialPoolTable::create([
                    'tournament_id' => $tournament->id,
                    'name'          => "Table {$i}",
                    'cloth_speed'   => $i <= 2 ? 'fast' : 'medium',
                ]);
            });

            // Stage 1: Group stage (4 groups of 8)
            $groupStage = OfficialStage::create([
                'tournament_id' => $tournament->id,
                'type'          => OfficialStage::TYPE_GROUP,
                'number'        => 1,
                'settings'      => [
                    'groups_count'      => 4,
                    'players_per_group' => 8,
                    'advance_per_group' => 2,
                    'race_to'           => 5,
                ],
            ]);

            // Stage 2: Single elimination playoff
            $playoffStage = OfficialStage::create([
                'tournament_id' => $tournament->id,
                'type'          => OfficialStage::TYPE_SINGLE_ELIM,
                'number'        => 2,
                'settings'      => [
                    'third_place_match' => true,
                    'race_to'           => 7,
                ],
            ]);

            // Create 32 participants for group stage
            $participants = collect(range(1, 32))->map(function ($i) use ($groupStage) {
                $user = User::factory()->create([
                    'firstname' => "Player",
                    'lastname'  => "Number{$i}",
                ]);

                return OfficialParticipant::create([
                    'stage_id'        => $groupStage->id,
                    'user_id'         => $user->id,
                    'seed'            => $i,
                    'rating_snapshot' => 2500 - ($i * 30) + rand(-50, 50),
                ]);
            });

            // Distribute participants into groups and create round-robin matches
            $groups = $participants->chunk(8);
            $groupNumber = 0;

            foreach ($groups as $group) {
                $groupNumber++;

                // Create round-robin matches for this group
                $groupParticipants = $group->values();

                for ($i = 0; $i < count($groupParticipants); $i++) {
                    for ($j = $i + 1; $j < count($groupParticipants); $j++) {
                        $match = OfficialMatch::create([
                            'stage_id'     => $groupStage->id,
                            'round'        => null, // Round-robin doesn't have rounds
                            'bracket'      => 'G'.$groupNumber,
                            'scheduled_at' => null,
                            'table_id'     => null,
                            'status'       => OfficialMatch::STATUS_PENDING,
                            'metadata'     => [
                                'group'           => $groupNumber,
                                'participant1_id' => $groupParticipants[$i]->id,
                                'participant2_id' => $groupParticipants[$j]->id,
                            ],
                        ]);
                    }
                }
            }

            // Create a few demo matches with scores (simulate some completed matches)
            $demoMatches = OfficialMatch::where('stage_id', $groupStage->id)
                ->limit(10)
                ->get()
            ;

            foreach ($demoMatches as $match) {
                $match->update([
                    'status'       => OfficialMatch::STATUS_FINISHED,
                    'scheduled_at' => now()->subHours(rand(1, 48)),
                    'table_id'     => $tables->random()->id,
                ]);

                // Create match sets
                $p1Id = $match->metadata['participant1_id'];
                $p2Id = $match->metadata['participant2_id'];
                $raceTo = 5;
                $p1Wins = 0;
                $p2Wins = 0;
                $setNo = 1;

                while ($p1Wins < $raceTo && $p2Wins < $raceTo) {
                    $p1Score = rand(0, 9);
                    $p2Score = rand(0, 9);
                    $winnerId = $p1Score > $p2Score ? $p1Id : $p2Id;

                    OfficialMatchSet::create([
                        'match_id'              => $match->id,
                        'set_no'                => $setNo,
                        'winner_participant_id' => $winnerId,
                        'score_json'            => [
                            'participant1' => $p1Score,
                            'participant2' => $p2Score,
                        ],
                    ]);

                    if ($winnerId == $p1Id) {
                        $p1Wins++;
                    } else {
                        $p2Wins++;
                    }

                    $setNo++;
                }
            }

            // Create stage 2 (playoff) participants - top 2 from each group
            $playoffParticipants = collect(range(1, 8))->map(function ($i) use ($playoffStage) {
                return OfficialParticipant::create([
                    'stage_id'        => $playoffStage->id,
                    'user_id'         => User::inRandomOrder()->first()->id,
                    'seed'            => $i,
                    'rating_snapshot' => 2500 - ($i * 50) + rand(-30, 30),
                ]);
            });

            // Create single elimination bracket matches
            // Quarter-finals (4 matches)
            for ($i = 0; $i < 4; $i++) {
                OfficialMatch::create([
                    'stage_id'     => $playoffStage->id,
                    'round'        => 1,
                    'bracket'      => OfficialMatch::BRACKET_WINNER,
                    'scheduled_at' => null,
                    'table_id'     => null,
                    'status'       => OfficialMatch::STATUS_PENDING,
                    'metadata'     => [
                        'match_number'    => $i + 1,
                        'participant1_id' => $playoffParticipants[$i * 2]->id,
                        'participant2_id' => $playoffParticipants[$i * 2 + 1]->id,
                    ],
                ]);
            }

            // Semi-finals (2 matches) - participants TBD
            for ($i = 0; $i < 2; $i++) {
                OfficialMatch::create([
                    'stage_id'     => $playoffStage->id,
                    'round'        => 2,
                    'bracket'      => OfficialMatch::BRACKET_WINNER,
                    'scheduled_at' => null,
                    'table_id'     => null,
                    'status'       => OfficialMatch::STATUS_PENDING,
                    'metadata'     => [
                        'match_number'    => $i + 1,
                        'participant1_id' => null, // Winner of QF1/QF2
                        'participant2_id' => null, // Winner of QF3/QF4
                    ],
                ]);
            }

            // Final
            OfficialMatch::create([
                'stage_id'     => $playoffStage->id,
                'round'        => 3,
                'bracket'      => OfficialMatch::BRACKET_WINNER,
                'scheduled_at' => null,
                'table_id'     => null,
                'status'       => OfficialMatch::STATUS_PENDING,
                'metadata'     => [
                    'match_number'    => 1,
                    'participant1_id' => null, // Winner of SF1
                    'participant2_id' => null, // Winner of SF2
                ],
            ]);

            // Third place match
            OfficialMatch::create([
                'stage_id'     => $playoffStage->id,
                'round'        => 3,
                'bracket'      => OfficialMatch::BRACKET_CONSOLATION,
                'scheduled_at' => null,
                'table_id'     => null,
                'status'       => OfficialMatch::STATUS_PENDING,
                'metadata'     => [
                    'match_number'    => 1,
                    'participant1_id' => null, // Loser of SF1
                    'participant2_id' => null, // Loser of SF2
                ],
            ]);
        });
    }
}
