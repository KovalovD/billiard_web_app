<?php

use App\Core\Models\Game;
use App\Core\Models\User;
use App\Leagues\Enums\RatingType;
use App\Leagues\Models\League;
use App\Leagues\Models\Rating;
use App\Leagues\Services\LeaguesService;
use App\Leagues\Services\MatchGamesService;
use App\Leagues\Services\RatingService;
use App\Matches\Enums\GameStatus;
use App\Matches\Models\MatchGame;
use App\Matches\Models\MultiplayerGame;
use App\Matches\Services\MultiplayerGameService;
use App\User\Services\UserStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompleteRatingSystemTest extends TestCase
{
    use RefreshDatabase;

    private RatingService $ratingService;
    private MatchGamesService $matchGamesService;
    private LeaguesService $leaguesService;
    private MultiplayerGameService $multiplayerGameService;
    private UserStatsService $userStatsService;

    /**
     * This test simulates a complete end-to-end workflow with the rating system
     *
     * @test
     */
    public function complete_rating_system_end_to_end_test()
    {
        // Step 1: Create games with different types
        $poolGame = Game::factory()->create([
            'name'           => 'Pool',
            'type'           => 'pool',
            'is_multiplayer' => false,
        ]);

        $snookerGame = Game::factory()->create([
            'name'           => 'Snooker',
            'type'           => 'snooker',
            'is_multiplayer' => false,
        ]);

        $multiplayerGame = Game::factory()->create([
            'name'           => 'Multiplayer Pool',
            'type'           => 'pool',
            'is_multiplayer' => true,
        ]);

        // Step 2: Create leagues for different game types
        $poolLeague = League::factory()->create([
            'name'                           => 'Pool League',
            'game_id'                        => $poolGame->id,
            'rating_type'                    => RatingType::Elo,
            'start_rating'                   => 1000,
            'max_players'                    => 10,
            'max_score'                      => 7,
            'invite_days_expire'             => 2,
            'rating_change_for_winners_rule' => [
                ['range' => [0, 50], 'strong' => 25, 'weak' => 25],
                ['range' => [51, 100], 'strong' => 20, 'weak' => 30],
                ['range' => [101, 200], 'strong' => 15, 'weak' => 35],
                ['range' => [201, 1000000], 'strong' => 10, 'weak' => 40],
            ],
            'rating_change_for_losers_rule'  => [
                ['range' => [0, 50], 'strong' => -25, 'weak' => -25],
                ['range' => [51, 100], 'strong' => -20, 'weak' => -30],
                ['range' => [101, 200], 'strong' => -15, 'weak' => -35],
                ['range' => [201, 1000000], 'strong' => -10, 'weak' => -40],
            ],
        ]);

        $snookerLeague = League::factory()->create([
            'name'                           => 'Snooker League',
            'game_id'                        => $snookerGame->id,
            'rating_type'                    => RatingType::Elo,
            'start_rating'                   => 1000,
            'max_players'                    => 5,
            'max_score'                      => 9,
            'invite_days_expire'             => 3,
            'rating_change_for_winners_rule' => [
                ['range' => [0, 50], 'strong' => 25, 'weak' => 25],
                ['range' => [51, 100], 'strong' => 20, 'weak' => 30],
                ['range' => [101, 1000000], 'strong' => 15, 'weak' => 35],
            ],
            'rating_change_for_losers_rule'  => [
                ['range' => [0, 50], 'strong' => -25, 'weak' => -25],
                ['range' => [51, 100], 'strong' => -20, 'weak' => -30],
                ['range' => [101, 1000000], 'strong' => -15, 'weak' => -35],
            ],
        ]);

        $killerPoolLeague = League::factory()->create([
            'name'               => 'Killer Pool League',
            'game_id'            => $multiplayerGame->id,
            'rating_type'        => RatingType::KillerPool,
            'start_rating'       => 1000,
            'max_players'        => 20,
            'max_score'          => 0, // Not relevant for multiplayer
            'invite_days_expire' => 7,
        ]);

        // Step 3: Create users that will participate in the leagues
        $users = User::factory()->count(6)->create();

        // Step 4: Users join the leagues and get their initial ratings
        foreach ($users as $i => $user) {
            // First 4 users join Pool League
            if ($i < 4) {
                $this->ratingService->addPlayer($poolLeague, $user);

                // Confirm the players directly (admin would do this)
                Rating::where('league_id', $poolLeague->id)
                    ->where('user_id', $user->id)
                    ->update(['is_confirmed' => true])
                ;
            }

            // Users 2-5 join Snooker League
            if ($i >= 2 && $i <= 5) {
                $this->ratingService->addPlayer($snookerLeague, $user);
                Rating::where('league_id', $snookerLeague->id)
                    ->where('user_id', $user->id)
                    ->update(['is_confirmed' => true])
                ;
            }

            // All users join Killer Pool League
            $this->ratingService->addPlayer($killerPoolLeague, $user);
            Rating::where('league_id', $killerPoolLeague->id)
                ->where('user_id', $user->id)
                ->update(['is_confirmed' => true])
            ;
        }

        // Verify all players were added to the leagues
        $this->assertEquals(4, Rating::where('league_id', $poolLeague->id)->where('is_active', 1)->count());
        $this->assertEquals(4, Rating::where('league_id', $snookerLeague->id)->where('is_active', 1)->count());
        $this->assertEquals(6, Rating::where('league_id', $killerPoolLeague->id)->where('is_active', 1)->count());

        // Step 5: Create and play some matches in the Pool League
        $poolPlayers = Rating::where('league_id', $poolLeague->id)
            ->where('is_active', 1)
            ->orderBy('position')
            ->get()
        ;

        // Match 1: Player 0 vs Player 1 (equal ratings, Player 0 wins)
        $match1 = MatchGame::create([
            'game_id'                   => $poolGame->id,
            'league_id'                 => $poolLeague->id,
            'first_rating_id'           => $poolPlayers[0]->id,
            'second_rating_id'          => $poolPlayers[1]->id,
            'first_user_score'          => 7,
            'second_user_score'         => 5,
            'winner_rating_id'          => $poolPlayers[0]->id,
            'loser_rating_id'           => $poolPlayers[1]->id,
            'status'                    => GameStatus::COMPLETED,
            'invitation_sent_at'        => now()->subDays(2),
            'invitation_accepted_at'    => now()->subDays(2),
            'finished_at'               => now()->subDays(1),
            'first_rating_before_game'  => $poolPlayers[0]->rating,
            'second_rating_before_game' => $poolPlayers[1]->rating,
        ]);

        // Update ratings based on match result
        $newRatings1 = $this->ratingService->updateRatings($match1, $poolPlayers[0]->user_id);

        // Match 2: Player 2 vs Player 3 (equal ratings, Player 3 wins)
        $poolPlayers = Rating::where('league_id', $poolLeague->id)
            ->where('is_active', 1)
            ->orderBy('position')
            ->get()
        ;

        $match2 = MatchGame::create([
            'game_id'                   => $poolGame->id,
            'league_id'                 => $poolLeague->id,
            'first_rating_id'           => $poolPlayers[2]->id,
            'second_rating_id'          => $poolPlayers[3]->id,
            'first_user_score'          => 4,
            'second_user_score'         => 7,
            'winner_rating_id'          => $poolPlayers[3]->id,
            'loser_rating_id'           => $poolPlayers[2]->id,
            'status'                    => GameStatus::COMPLETED,
            'invitation_sent_at'        => now()->subDays(2),
            'invitation_accepted_at'    => now()->subDays(2),
            'finished_at'               => now()->subDays(1),
            'first_rating_before_game'  => $poolPlayers[2]->rating,
            'second_rating_before_game' => $poolPlayers[3]->rating,
        ]);

        // Update ratings based on match result
        $newRatings2 = $this->ratingService->updateRatings($match2, $poolPlayers[3]->user_id);

        // Match 3: Player 0 vs Player 3 (both winners of previous matches, non-equal ratings now)
        $poolPlayers = Rating::where('league_id', $poolLeague->id)
            ->where('is_active', 1)
            ->orderBy('position')
            ->get()
        ;

        $match3 = MatchGame::create([
            'game_id'                   => $poolGame->id,
            'league_id'                 => $poolLeague->id,
            'first_rating_id'           => $poolPlayers[0]->id,
            'second_rating_id'          => $poolPlayers[3]->id,
            'first_user_score'          => 7,
            'second_user_score'         => 6,
            'winner_rating_id'          => $poolPlayers[0]->id,
            'loser_rating_id'           => $poolPlayers[3]->id,
            'status'                    => GameStatus::COMPLETED,
            'invitation_sent_at'        => now()->subDays(1),
            'invitation_accepted_at'    => now()->subDay(),
            'finished_at'               => now()->subHours(12),
            'first_rating_before_game'  => $poolPlayers[0]->rating,
            'second_rating_before_game' => $poolPlayers[3]->rating,
        ]);

        // Update ratings based on match result
        $newRatings3 = $this->ratingService->updateRatings($match3, $poolPlayers[0]->user_id);

        // Step 6: Create and play some matches in the Snooker League
        $snookerPlayers = Rating::where('league_id', $snookerLeague->id)
            ->where('is_active', 1)
            ->orderBy('position')
            ->get()
        ;

        // Match 1: Player 0 vs Player 1 (equal ratings, Player 1 wins)
        $match4 = MatchGame::create([
            'game_id'                   => $snookerGame->id,
            'league_id'                 => $snookerLeague->id,
            'first_rating_id'           => $snookerPlayers[0]->id,
            'second_rating_id'          => $snookerPlayers[1]->id,
            'first_user_score'          => 5,
            'second_user_score'         => 9,
            'winner_rating_id'          => $snookerPlayers[1]->id,
            'loser_rating_id'           => $snookerPlayers[0]->id,
            'status'                    => GameStatus::COMPLETED,
            'invitation_sent_at'        => now()->subDays(2),
            'invitation_accepted_at'    => now()->subDays(2),
            'finished_at'               => now()->subDay(),
            'first_rating_before_game'  => $snookerPlayers[0]->rating,
            'second_rating_before_game' => $snookerPlayers[1]->rating,
        ]);

        // Update ratings based on match result
        $newRatings4 = $this->ratingService->updateRatings($match4, $snookerPlayers[1]->user_id);

        // Step 7: Create and run a multiplayer game in the Killer Pool League
        $mpGame = MultiplayerGame::create([
            'league_id'            => $killerPoolLeague->id,
            'game_id'              => $multiplayerGame->id,
            'name'                 => 'Test Multiplayer Game',
            'status'               => 'registration',
            'max_players'          => 6,
            'entrance_fee'         => 300,
            'first_place_percent'  => 60,
            'second_place_percent' => 20,
            'grand_final_percent'  => 20,
            'penalty_fee'          => 50,
        ]);

        // All 6 users join the multiplayer game
        foreach ($users as $user) {
            $mpGame->players()->create([
                'user_id'   => $user->id,
                'joined_at' => now()->subHours(6),
            ]);
        }

        // Start the game
        $this->multiplayerGameService->start($mpGame);

        // Simulate game play - eliminate players in a specific order
        $mpPlayers = $mpGame->players()->get();

        // Player at index 5 gets 6th place (eliminated first)
        $mpPlayers[5]->update([
            'lives'           => 0,
            'finish_position' => 6,
            'eliminated_at'   => now()->subHours(5),
            'rating_points'   => 1, // Lowest points
        ]);

        // Player at index 4 gets 5th place
        $mpPlayers[4]->update([
            'lives'           => 0,
            'finish_position' => 5,
            'eliminated_at'   => now()->subHours(4),
            'rating_points'   => 2,
        ]);

        // Player at index 3 gets 4th place
        $mpPlayers[3]->update([
            'lives'           => 0,
            'finish_position' => 4,
            'eliminated_at'   => now()->subHours(3),
            'rating_points'   => 5,
        ]);

        // Player at index 2 gets 3rd place
        $mpPlayers[2]->update([
            'lives'           => 0,
            'finish_position' => 3,
            'eliminated_at'   => now()->subHours(2),
            'rating_points'   => 10,
        ]);

        // Player at index 1 gets 2nd place
        $mpPlayers[1]->update([
            'lives'           => 0,
            'finish_position' => 2,
            'eliminated_at'   => now()->subHour(),
            'rating_points'   => 15,
        ]);

        // Player at index 0 gets 1st place
        $mpPlayers[0]->update([
            'lives'           => 0,
            'finish_position' => 1,
            'eliminated_at'   => now(),
            'rating_points'   => 20, // Highest points
        ]);

        // Complete the game
        $mpGame->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        // Calculate prizes
        $this->multiplayerGameService->calculatePrizes($mpGame);

        // Calculate and apply rating points
        $this->multiplayerGameService->calculateRatingPoints($mpGame);
        $this->ratingService->applyRatingPointsForMultiplayerGame($mpGame);

        // Step 8: Verify user stats and ratings after all games

        // First user participated in pool (3 matches) and killer pool (1 game)
        $user0Stats = $this->userStatsService->getUserStats($users[0]);
        $user0TypeStats = $this->userStatsService->getGameTypeStats($users[0]);

        $this->assertEquals(4, $user0Stats['total_matches']);
        $this->assertEquals(4, $user0Stats['completed_matches']);
        $this->assertEquals(3, $user0Stats['wins']); // Won 2 pool matches and finished 1st in killer pool
        $this->assertEquals(1, $user0Stats['losses']);
        $this->assertEquals(75, $user0Stats['win_rate']);
        $this->assertEquals(2, $user0Stats['leagues_count']); // Pool and Killer Pool leagues

        // Should have stats for both pool and killer_pool game types
        $this->assertArrayHasKey('pool', $user0TypeStats);

        // Check that the ratings have been updated correctly
        // Pool league - User 0 should have higher rating after winning 2 matches
        $user0PoolRating = Rating::where('league_id', $poolLeague->id)
            ->where('user_id', $users[0]->id)
            ->first()
        ;

        $this->assertGreaterThan(1000, $user0PoolRating->rating);

        // Killer Pool league - User 0 should have gained rating points from winning multiplayer game
        $user0KillerPoolRating = Rating::where('league_id', $killerPoolLeague->id)
            ->where('user_id', $users[0]->id)
            ->first()
        ;

        $this->assertEquals(1020, $user0KillerPoolRating->rating); // 1000 + 20 points for 1st place

        // Check that positions were correctly rearranged
        $poolLeaguePositions = Rating::where('league_id', $poolLeague->id)
            ->where('is_active', 1)
            ->orderBy('position')
            ->pluck('user_id')
            ->toArray()
        ;

        // User 0 should be first in pool league
        $this->assertEquals($users[0]->id, $poolLeaguePositions[0]);

        $killerPoolLeaguePositions = Rating::where('league_id', $killerPoolLeague->id)
            ->where('is_active', 1)
            ->orderBy('position')
            ->pluck('user_id')
            ->toArray()
        ;

        // The positions in Killer Pool league should reflect the multiplayer game results
        $this->assertEquals($users[0]->id, $killerPoolLeaguePositions[0]); // 1st place
        $this->assertEquals($users[1]->id, $killerPoolLeaguePositions[1]); // 2nd place

        // Verify multiplayer game financial summary
        $financialSummary = $this->multiplayerGameService->getFinancialSummary($mpGame);
        $this->assertEquals(300, $financialSummary['entrance_fee']);
        $this->assertEquals(1800, $financialSummary['total_prize_pool']); // 6 players * 300
        $this->assertEquals(1080, $financialSummary['first_place_prize']); // 60% of 1800
        $this->assertEquals(360, $financialSummary['second_place_prize']); // 20% of 1800
        $this->assertEquals(360, $financialSummary['grand_final_fund']); // 20% of 1800

        // Verify multiplayer game rating summary
        $ratingSummary = $this->multiplayerGameService->getRatingSummary($mpGame);
        $this->assertEquals(6, count($ratingSummary['players']));
        $this->assertEquals(1, $ratingSummary['players'][0]['finish_position']);
        $this->assertEquals(20, $ratingSummary['players'][0]['rating_points']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->ratingService = new RatingService();
        $this->matchGamesService = new MatchGamesService($this->ratingService);
        $this->leaguesService = new LeaguesService();
        $this->multiplayerGameService = new MultiplayerGameService();
        $this->userStatsService = new UserStatsService();
    }
}
