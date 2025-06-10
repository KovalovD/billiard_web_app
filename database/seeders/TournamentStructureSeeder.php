<?php

namespace Database\Seeders;

use App\Core\Models\Game;
use App\Core\Models\User;
use App\Tournaments\Enums\TournamentFormat;
use App\Tournaments\Enums\SeedingMethod;
use App\Tournaments\Enums\BestOfRule;
use App\Tournaments\Models\Tournament;
use App\Tournaments\Models\TournamentPlayer;
use Illuminate\Database\Seeder;

class TournamentStructureSeeder extends Seeder
{
    public function run(): void
    {
        // Create sample tournaments with different formats
        $this->createSingleEliminationTournament();
        $this->createGroupStageTournament();
        $this->createTeamTournament();
    }

    private function createSingleEliminationTournament(): void
    {
        $game = Game::where('name', 'Пул 8')->first();
        if (!$game) {
            return;
        }

        $tournament = Tournament::create([
            'name'                      => 'Spring Championship - Single Elimination',
            'status'                    => 'upcoming',
            'game_id'                   => $game->id,
            'start_date'                => now()->addDays(7),
            'end_date'                  => now()->addDays(9),
            'max_participants'          => 16,
            'entry_fee'                 => 100,
            'prize_pool'                => 1500,
            'organizer'                 => 'B2B League',
            'format'                    => 'Single elimination tournament with seeding',
            'tournament_format'         => TournamentFormat::SingleElimination->value,
            'seeding_method'            => SeedingMethod::RatingBased->value,
            'best_of_rule'              => BestOfRule::BestOf3->value,
            'has_lower_bracket'         => false,
            'is_team_tournament'        => false,
            'requires_application'      => true,
            'auto_approve_applications' => false,
        ]);

        // Add some players
        $users = User::limit(12)->get();
        foreach ($users as $index => $user) {
            TournamentPlayer::create([
                'tournament_id' => $tournament->id,
                'user_id'       => $user->id,
                'status'        => 'confirmed',
                'seed'          => $index + 1,
                'registered_at' => now(),
                'applied_at'    => now(),
                'confirmed_at'  => now(),
            ]);
        }
    }

    private function createGroupStageTournament(): void
    {
        $game = Game::where('name', 'Пул 9')->first();
        if (!$game) {
            return;
        }

        $tournament = Tournament::create([
            'name'                      => 'Summer Cup - Group Stage',
            'status'                    => 'upcoming',
            'game_id'                   => $game->id,
            'start_date'                => now()->addDays(14),
            'end_date'                  => now()->addDays(16),
            'max_participants'          => 16,
            'entry_fee'                 => 150,
            'prize_pool'                => 2000,
            'organizer'                 => 'B2B League',
            'format'                    => 'Group stage followed by knockout playoffs',
            'tournament_format'         => TournamentFormat::GroupPlayoff->value,
            'seeding_method'            => SeedingMethod::Manual->value,
            'number_of_groups'          => 4,
            'players_per_group'         => 4,
            'advance_per_group'         => 2,
            'best_of_rule'              => BestOfRule::BestOf5->value,
            'has_lower_bracket'         => false,
            'is_team_tournament'        => false,
            'requires_application'      => true,
            'auto_approve_applications' => true,
        ]);

        // Add some players
        $users = User::skip(12)->limit(16)->get();
        foreach ($users as $index => $user) {
            TournamentPlayer::create([
                'tournament_id' => $tournament->id,
                'user_id'       => $user->id,
                'status'        => 'confirmed',
                'seed'          => $index + 1,
                'registered_at' => now(),
                'applied_at'    => now(),
                'confirmed_at'  => now(),
            ]);
        }
    }

    private function createTeamTournament(): void
    {
        $game = Game::where('name', 'Пул 10')->first();
        if (!$game) {
            return;
        }

        $tournament = Tournament::create([
            'name'                      => 'Team Championship - Doubles',
            'status'                    => 'upcoming',
            'game_id'                   => $game->id,
            'start_date'                => now()->addDays(21),
            'end_date'                  => now()->addDays(23),
            'max_participants'          => 16, // 8 teams of 2 players each
            'entry_fee'                 => 200,
            'prize_pool'                => 3000,
            'organizer'                 => 'B2B League',
            'format'                    => 'Team tournament with pairs',
            'tournament_format'         => TournamentFormat::DoubleElimination->value,
            'seeding_method'            => SeedingMethod::Random->value,
            'best_of_rule'              => BestOfRule::BestOf3->value,
            'has_lower_bracket'         => true,
            'is_team_tournament'        => true,
            'team_size'                 => 2,
            'requires_application'      => false,
            'auto_approve_applications' => true,
        ]);

        // Note: Team creation and player assignment would be handled in the service layer
        // This seeder just creates the tournament structure
    }
}
