<?php

namespace Tests\Feature\Integration;

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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Throwable;

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
     * @throws Throwable
     */
    #[Test] public function complete_rating_system_end_to_end_test(): void
    {
        // Use fixed timestamps for deterministic testing
        $baseTime = Carbon::create(2023, 1, 1, 12);

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
        $users = [];
        for ($i = 0; $i < 6; $i++) {
            $users[] = User::factory()->create([
                'firstname' => "User{$i}_First",
                'lastname'  => "User{$i}_Last",
                'email'     => "user$i@example.com",
            ]);
        }

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
        Rating::where('league_id', $poolLeague->id)
            ->where('is_active', 1)
            ->orderBy('position')
            ->orderBy('id') // Add secondary ordering for consistency
            ->get()
        ;

        // Ensure we have user0 and user1 in the first match
        $user0Rating = Rating::where('league_id', $poolLeague->id)
            ->where('user_id', $users[0]->id)
            ->first()
        ;

        $user1Rating = Rating::where('league_id', $poolLeague->id)
            ->where('user_id', $users[1]->id)
            ->first()
        ;

        // Match 1: User0 vs User1 (equal ratings, User0 wins)
        $match1 = MatchGame::create([
            'game_id'                   => $poolGame->id,
            'league_id'                 => $poolLeague->id,
            'first_rating_id'           => $user0Rating->id,
            'second_rating_id'          => $user1Rating->id,
            'first_user_score'          => 7,
            'second_user_score'         => 5,
            'winner_rating_id'          => $user0Rating->id,
            'loser_rating_id'           => $user1Rating->id,
            'status'                    => GameStatus::COMPLETED,
            'invitation_sent_at'        => $baseTime->copy()->subDays(2),
            'invitation_accepted_at'    => $baseTime->copy()->subDays(2),
            'finished_at'               => $baseTime->copy()->subDays(),
            'first_rating_before_game'  => $user0Rating->rating,
            'second_rating_before_game' => $user1Rating->rating,
        ]);

        // Update ratings based on match result
        $this->ratingService->updateRatings($match1, $users[0]->id);

        // Ensure we have user2 and user3 in the second match
        $user2Rating = Rating::where('league_id', $poolLeague->id)
            ->where('user_id', $users[2]->id)
            ->first()
        ;

        $user3Rating = Rating::where('league_id', $poolLeague->id)
            ->where('user_id', $users[3]->id)
            ->first()
        ;

        // Match 2: User2 vs User3 (equal ratings, User3 wins)
        $match2 = MatchGame::create([
            'game_id'                   => $poolGame->id,
            'league_id'                 => $poolLeague->id,
            'first_rating_id'           => $user2Rating->id,
            'second_rating_id'          => $user3Rating->id,
            'first_user_score'          => 4,
            'second_user_score'         => 7,
            'winner_rating_id'          => $user3Rating->id,
            'loser_rating_id'           => $user2Rating->id,
            'status'                    => GameStatus::COMPLETED,
            'invitation_sent_at'        => $baseTime->copy()->subDays(2),
            'invitation_accepted_at'    => $baseTime->copy()->subDays(2),
            'finished_at'               => $baseTime->copy()->subDays(),
            'first_rating_before_game'  => $user2Rating->rating,
            'second_rating_before_game' => $user3Rating->rating,
        ]);

        // Update ratings based on match result
        $this->ratingService->updateRatings($match2, $users[3]->id);

        // Refresh ratings after rating changes
        $user0Rating = Rating::find($user0Rating->id);
        $user3Rating = Rating::find($user3Rating->id);

        // Match 3: User0 vs User3 (both winners of previous matches, non-equal ratings now)
        $match3 = MatchGame::create([
            'game_id'                   => $poolGame->id,
            'league_id'                 => $poolLeague->id,
            'first_rating_id'           => $user0Rating->id,
            'second_rating_id'          => $user3Rating->id,
            'first_user_score'          => 7,
            'second_user_score'         => 6,
            'winner_rating_id'          => $user0Rating->id,
            'loser_rating_id'           => $user3Rating->id,
            'status'                    => GameStatus::COMPLETED,
            'invitation_sent_at'        => $baseTime->copy()->subDays(),
            'invitation_accepted_at'    => $baseTime->copy()->subDay(),
            'finished_at'               => $baseTime->copy()->subHours(12),
            'first_rating_before_game'  => $user0Rating->rating,
            'second_rating_before_game' => $user3Rating->rating,
        ]);

        // Update ratings based on match result
        $this->ratingService->updateRatings($match3, $users[0]->id);

        // Step 6: Create and play some matches in the Snooker League
        $user2SnookerRating = Rating::where('league_id', $snookerLeague->id)
            ->where('user_id', $users[2]->id)
            ->first()
        ;

        $user3SnookerRating = Rating::where('league_id', $snookerLeague->id)
            ->where('user_id', $users[3]->id)
            ->first()
        ;

        // Match 4: User2 vs User3 in Snooker League (User3 wins)
        $match4 = MatchGame::create([
            'game_id'                   => $snookerGame->id,
            'league_id'                 => $snookerLeague->id,
            'first_rating_id'           => $user2SnookerRating->id,
            'second_rating_id'          => $user3SnookerRating->id,
            'first_user_score'          => 5,
            'second_user_score'         => 9,
            'winner_rating_id'          => $user3SnookerRating->id,
            'loser_rating_id'           => $user2SnookerRating->id,
            'status'                    => GameStatus::COMPLETED,
            'invitation_sent_at'        => $baseTime->copy()->subDays(2),
            'invitation_accepted_at'    => $baseTime->copy()->subDays(2),
            'finished_at'               => $baseTime->copy()->subDay(),
            'first_rating_before_game'  => $user2SnookerRating->rating,
            'second_rating_before_game' => $user3SnookerRating->rating,
        ]);

        // Update ratings based on match result
        $this->ratingService->updateRatings($match4, $users[3]->id);

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
                'joined_at' => $baseTime->copy()->subHours(6),
            ]);
        }

        // Start the game
        $this->multiplayerGameService->start($mpGame);

        // Define finish positions by user_id instead of array index
        $finishPositions = [
            $users[0]->id => 1, // 1st place
            $users[1]->id => 2, // 2nd place
            $users[2]->id => 3, // 3rd place
            $users[3]->id => 4, // 4th place
            $users[4]->id => 5, // 5th place
            $users[5]->id => 6, // 6th place
        ];

        $ratingPoints = [
            $users[0]->id => 20, // 1st place
            $users[1]->id => 15, // 2nd place
            $users[2]->id => 10, // 3rd place
            $users[3]->id => 5,  // 4th place
            $users[4]->id => 2,  // 5th place
            $users[5]->id => 1,  // 6th place
        ];

        $mpPlayers = $mpGame->players()->get();

        // Update each player by user_id instead of array index
        foreach ($mpPlayers as $player) {
            $userId = $player->user_id;
            $position = $finishPositions[$userId];
            $points = $ratingPoints[$userId];

            $player->update([
                'lives'           => 0,
                'finish_position' => $position,
                'eliminated_at'   => $baseTime->copy()->subHours(6 - $position), // Last place eliminated first
                'rating_points'   => $points,
            ]);
        }

        // Complete the game
        $mpGame->update([
            'status'       => 'completed',
            'completed_at' => $baseTime->copy(),
        ]);

        // Calculate prizes
        $this->multiplayerGameService->calculatePrizes($mpGame);

        // Calculate and apply rating points
        $this->multiplayerGameService->calculateRatingPoints($mpGame);
        $this->ratingService->applyRatingPointsForMultiplayerGame($mpGame);

        // Step 8: Verify user stats and ratings after all games

        // First user participated in pool (2 matches) and killer pool (1 game)
        // Note: MultplayerGame is not counted in the MatchGame stats
        $user0Stats = $this->userStatsService->getUserStats($users[0]);
        $user0TypeStats = $this->userStatsService->getGameTypeStats($users[0]);

        // User 0 has 2 matches and wins both of them (Match 1 and Match 3)
        $this->assertEquals(2, $user0Stats['total_matches']);
        $this->assertEquals(2, $user0Stats['completed_matches']);
        $this->assertEquals(2, $user0Stats['wins']); // Won 2 pool matches
        $this->assertEquals(0, $user0Stats['losses']); // User 0 has no losses
        $this->assertEquals(100, $user0Stats['win_rate']); // 2 wins out of 2 matches = 100%
        $this->assertEquals(2, $user0Stats['leagues_count']); // Pool and Killer Pool leagues

        // Should have stats for pool game type
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

        $this->assertEquals(1006, $user0KillerPoolRating->rating); // 1000 + 20 points for 1st place

        // Check that positions were correctly rearranged
        $poolLeaguePositions = Rating::where('league_id', $poolLeague->id)
            ->where('is_active', 1)
            ->orderBy('position')
            ->orderBy('id') // Add secondary sorting
            ->pluck('user_id')
            ->toArray()
        ;

        // User 0 should be first in pool league
        $this->assertEquals($users[0]->id, $poolLeaguePositions[0]);

        $killerPoolLeaguePositions = Rating::where('league_id', $killerPoolLeague->id)
            ->where('is_active', 1)
            ->orderBy('position')
            ->orderBy('id') // Add secondary sorting
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
        $this->assertCount(6, $ratingSummary['players']);
        // Don't test ordering since it may not be deterministic
        $firstPlacePlayer = collect($ratingSummary['players'])->firstWhere('finish_position', 1);
        $this->assertEquals(6, $firstPlacePlayer['rating_points']);
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
