<?php

use App\Core\Http\Resources\UserResource;
use App\Core\Models\City;
use App\Core\Models\Club;
use App\Core\Models\Country;
use App\Core\Models\Game;
use App\Core\Models\User;
use App\Leagues\Http\Resources\ClubResource;
use App\Leagues\Http\Resources\LeagueResource;
use App\Leagues\Http\Resources\MatchGameResource;
use App\Leagues\Http\Resources\RatingResource;
use App\Leagues\Models\League;
use App\Leagues\Models\Rating;
use App\Matches\Http\Resources\MultiplayerGameResource;
use App\Matches\Models\MatchGame;
use App\Matches\Models\MultiplayerGame;
use App\Matches\Models\MultiplayerGamePlayer;
use App\User\Http\Resources\CityResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourcesTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_resource_transforms_correctly()
    {
        // Arrange
        $user = User::factory()->create([
            'firstname' => 'John',
            'lastname'  => 'Doe',
            'email'     => 'john.doe@example.com',
            'phone'     => '123-456-7890',
            'is_admin'  => true,
        ]);

        // Create related models that should be conditionally included
        $city = City::factory()->create();
        $club = Club::factory()->create();

        $user->home_city_id = $city->id;
        $user->home_club_id = $club->id;
        $user->save();

        // Load relationships
        $user->load('homeCity', 'homeClub');

        // Act
        $resource = new UserResource($user);
        $transformed = $resource->toArray($this->createMock(\Illuminate\Http\Request::class));

        // Assert
        $this->assertEquals($user->id, $transformed['id']);
        $this->assertEquals('John', $transformed['firstname']);
        $this->assertEquals('Doe', $transformed['lastname']);
        $this->assertEquals('john.doe@example.com', $transformed['email']);
        $this->assertEquals('123-456-7890', $transformed['phone']);
        $this->assertTrue($transformed['is_admin']);

        // Related resources
        $this->assertArrayHasKey('home_city', $transformed);
        $this->assertArrayHasKey('home_club', $transformed);
        $this->assertEquals($city->id, $transformed['home_city']['id']);
        $this->assertEquals($club->id, $transformed['home_club']['id']);
    }

    /** @test */
    public function rating_resource_transforms_correctly()
    {
        // Arrange
        $user = User::factory()->create();
        $league = League::factory()->create();

        $rating = Rating::create([
            'league_id'    => $league->id,
            'user_id'      => $user->id,
            'rating'       => 1200,
            'position'     => 1,
            'is_active'    => true,
            'is_confirmed' => true,
        ]);

        // Create some matches to test hasOngoingMatches
        MatchGame::factory()->create([
            'first_rating_id' => $rating->id,
            'status'          => 'in_progress',
        ]);

        // Load relationships
        $rating->load('league', 'user');

        // Act
        $resource = new RatingResource($rating);
        $transformed = $resource->toArray($this->createMock(\Illuminate\Http\Request::class));

        // Assert
        $this->assertEquals($rating->id, $transformed['id']);
        $this->assertEquals($user->id, $transformed['player']['id']);
        $this->assertEquals($user->full_name, $transformed['player']['name']);
        $this->assertEquals(1200, $transformed['rating']);
        $this->assertEquals(1, $transformed['position']);
        $this->assertTrue($transformed['is_active']);
        $this->assertTrue($transformed['is_confirmed']);
        $this->assertEquals($league->id, $transformed['league_id']);
        $this->assertEquals($user->id, $transformed['user_id']);
        $this->assertTrue($transformed['hasOngoingMatches']);
    }

    /** @test */
    public function league_resource_transforms_correctly()
    {
        // Arrange
        $game = Game::factory()->create([
            'name'           => 'Test Game',
            'type'           => 'pool',
            'is_multiplayer' => true,
        ]);

        $league = League::factory()->create([
            'name'               => 'Test League',
            'game_id'            => $game->id,
            'has_rating'         => true,
            'start_rating'       => 1000,
            'max_players'        => 16,
            'max_score'          => 7,
            'invite_days_expire' => 2,
        ]);

        // Create active ratings to test counts
        Rating::factory()->count(3)->create([
            'league_id' => $league->id,
            'is_active' => true,
        ]);

        // Create matches to test counts
        MatchGame::factory()->count(2)->create([
            'league_id' => $league->id,
        ]);

        // Load relationships
        $league->load('game');

        // Act
        $resource = new LeagueResource($league);
        $transformed = $resource->toArray($this->createMock(\Illuminate\Http\Request::class));

        // Assert
        $this->assertEquals($league->id, $transformed['id']);
        $this->assertEquals('Test League', $transformed['name']);
        $this->assertTrue($transformed['has_rating']);
        $this->assertEquals(1000, $transformed['start_rating']);
        $this->assertEquals(16, $transformed['max_players']);
        $this->assertEquals(7, $transformed['max_score']);
        $this->assertEquals(2, $transformed['invite_days_expire']);
        $this->assertEquals(3, $transformed['active_players']);
        $this->assertEquals($game->id, $transformed['game_id']);

        // Loaded relationships
        $this->assertEquals('Test Game', $transformed['game']);
        $this->assertEquals('pool', $transformed['game_type']);
        $this->assertTrue($transformed['game_multiplayer']);
    }

    /** @test */
    public function match_game_resource_transforms_correctly()
    {
        // Arrange
        $game = Game::factory()->create();
        $league = League::factory()->create(['game_id' => $game->id]);
        $club = Club::factory()->create();

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $rating1 = Rating::create([
            'league_id'    => $league->id,
            'user_id'      => $user1->id,
            'rating'       => 1000,
            'position'     => 1,
            'is_active'    => true,
            'is_confirmed' => true,
        ]);

        $rating2 = Rating::create([
            'league_id'    => $league->id,
            'user_id'      => $user2->id,
            'rating'       => 1100,
            'position'     => 2,
            'is_active'    => true,
            'is_confirmed' => true,
        ]);

        $matchGame = MatchGame::create([
            'game_id'                   => $game->id,
            'league_id'                 => $league->id,
            'first_rating_id'           => $rating1->id,
            'second_rating_id'          => $rating2->id,
            'first_user_score'          => 5,
            'second_user_score'         => 3,
            'winner_rating_id'          => $rating1->id,
            'loser_rating_id'           => $rating2->id,
            'status'                    => 'completed',
            'stream_url'                => 'https://example.com/stream',
            'details'                   => 'Test match details',
            'club_id'                   => $club->id,
            'rating_change_for_winner'  => 20,
            'rating_change_for_loser'   => -20,
            'first_rating_before_game'  => 1000,
            'second_rating_before_game' => 1100,
            'invitation_sent_at'        => now()->subDay(),
            'invitation_accepted_at'    => now()->subHours(23),
            'finished_at'               => now()->subHours(22),
            'result_confirmed'          => [
                ['key' => $rating1->id, 'score' => '5-3'],
                ['key' => $rating2->id, 'score' => '5-3'],
            ],
        ]);

        // Load relationships
        $matchGame->load('firstRating.user', 'secondRating.user', 'club', 'league');

        // Act
        $resource = new MatchGameResource($matchGame);
        $transformed = $resource->toArray($this->createMock(\Illuminate\Http\Request::class));

        // Assert
        $this->assertEquals($matchGame->id, $transformed['id']);
        $this->assertEquals('completed', $transformed['status']);
        $this->assertEquals($league->id, $transformed['league_id']);
        $this->assertEquals('https://example.com/stream', $transformed['stream_url']);
        $this->assertEquals('Test match details', $transformed['details']);
        $this->assertEquals($rating1->id, $transformed['first_rating_id']);
        $this->assertEquals($rating2->id, $transformed['second_rating_id']);
        $this->assertEquals(5, $transformed['first_user_score']);
        $this->assertEquals(3, $transformed['second_user_score']);
        $this->assertEquals($rating1->id, $transformed['winner_rating_id']);
        $this->assertEquals($rating2->id, $transformed['loser_rating_id']);
        $this->assertEquals(20, $transformed['rating_change_for_winner']);
        $this->assertEquals(-20, $transformed['rating_change_for_loser']);
        $this->assertEquals(1000, $transformed['first_rating_before_game']);
        $this->assertEquals(1100, $transformed['second_rating_before_game']);

        // Players data
        $this->assertEquals($user1->id, $transformed['firstPlayer']['user']['id']);
        $this->assertEquals($user2->id, $transformed['secondPlayer']['user']['id']);

        // Related resources
        $this->assertEquals($club->id, $transformed['club']['id']);
        $this->assertEquals($league->id, $transformed['league']['id']);
    }

    /** @test */
    public function multiplayer_game_resource_transforms_correctly()
    {
        // Arrange
        $game = Game::factory()->create(['is_multiplayer' => true]);
        $league = League::factory()->create(['game_id' => $game->id]);
        $moderator = User::factory()->create();

        $multiplayerGame = MultiplayerGame::create([
            'league_id'              => $league->id,
            'game_id'                => $game->id,
            'name'                   => 'Test Multiplayer Game',
            'status'                 => 'in_progress',
            'initial_lives'          => 3,
            'max_players'            => 10,
            'moderator_user_id'      => $moderator->id,
            'current_player_id'      => $moderator->id,
            'next_turn_order'        => 2,
            'allow_player_targeting' => true,
            'entrance_fee'           => 300,
            'first_place_percent'    => 60,
            'second_place_percent'   => 20,
            'grand_final_percent'    => 20,
            'penalty_fee'            => 50,
            'started_at'             => now()->subHours(2),
            'registration_ends_at'   => now()->subHours(3),
        ]);

        // Create some players
        $users = User::factory()->count(3)->create();
        $players = [];

        foreach ($users as $i => $user) {
            $players[] = MultiplayerGamePlayer::create([
                'multiplayer_game_id' => $multiplayerGame->id,
                'user_id'             => $user->id,
                'lives'               => 3,
                'turn_order'          => $i + 1,
                'cards'               => [
                    'skip_turn' => true,
                    'pass_turn' => true,
                    'hand_shot' => true,
                ],
                'joined_at'           => now()->subHours(4),
            ]);
        }

        // Make one player eliminated
        $players[2]->update([
            'lives'           => 0,
            'finish_position' => 3,
            'eliminated_at'   => now()->subHour(),
            'rating_points'   => 10,
            'prize_amount'    => 0,
            'penalty_paid'    => true,
        ]);

        // Load relationships
        $multiplayerGame->load('players.user');

        // Mock auth user to be the moderator for "is_current_user_moderator" check
        $this->actingAs($moderator);

        // Act
        $resource = new MultiplayerGameResource($multiplayerGame);
        $transformed = $resource->toArray(request());

        // Assert
        $this->assertEquals($multiplayerGame->id, $transformed['id']);
        $this->assertEquals($league->id, $transformed['league_id']);
        $this->assertEquals($game->id, $transformed['game_id']);
        $this->assertEquals('Test Multiplayer Game', $transformed['name']);
        $this->assertEquals('in_progress', $transformed['status']);
        $this->assertEquals(3, $transformed['initial_lives']);
        $this->assertEquals(10, $transformed['max_players']);
        $this->assertEquals(2, $transformed['active_players_count']);
        $this->assertEquals(3, $transformed['total_players_count']);
        $this->assertEquals($moderator->id, $transformed['current_turn_player_id']);
        $this->assertFalse($transformed['is_registration_open']);
        $this->assertEquals($moderator->id, $transformed['moderator_user_id']);
        $this->assertTrue($transformed['allow_player_targeting']);
        $this->assertTrue($transformed['is_current_user_moderator']);
        $this->assertEquals(300, $transformed['entrance_fee']);
        $this->assertEquals(60, $transformed['first_place_percent']);
        $this->assertEquals(20, $transformed['second_place_percent']);
        $this->assertEquals(20, $transformed['grand_final_percent']);
        $this->assertEquals(50, $transformed['penalty_fee']);

        // Players arrays
        $this->assertCount(2, $transformed['active_players']);
        $this->assertCount(1, $transformed['eliminated_players']);

        // Verify active players data
        $this->assertEquals($users[0]->id, $transformed['active_players'][0]['user']['id']);
        $this->assertEquals(3, $transformed['active_players'][0]['lives']);
        $this->assertEquals(1, $transformed['active_players'][0]['turn_order']);
        $this->assertTrue($transformed['active_players'][0]['is_current_turn']);

        // Verify eliminated players data
        $this->assertEquals($users[2]->id, $transformed['eliminated_players'][0]['user']['id']);
        $this->assertEquals(0, $transformed['eliminated_players'][0]['lives']);
        $this->assertEquals(3, $transformed['eliminated_players'][0]['finish_position']);
        $this->assertEquals(10, $transformed['eliminated_players'][0]['rating_points']);
        $this->assertEquals(0, $transformed['eliminated_players'][0]['prize_amount']);
        $this->assertTrue($transformed['eliminated_players'][0]['penalty_paid']);
        $this->assertEquals(50, $transformed['eliminated_players'][0]['time_fund_contribution']);
    }

    /** @test */
    public function city_resource_transforms_correctly()
    {
        // Arrange
        $country = Country::factory()->create([
            'name' => 'Test Country',
        ]);

        $city = City::factory()->create([
            'name'       => 'Test City',
            'country_id' => $country->id,
        ]);

        // Load relationships
        $city->load('country');

        // Act
        $resource = new CityResource($city);
        $transformed = $resource->toArray($this->createMock(\Illuminate\Http\Request::class));

        // Assert
        $this->assertEquals($city->id, $transformed['id']);
        $this->assertEquals('Test City', $transformed['name']);
        $this->assertEquals($country->id, $transformed['country']['id']);
        $this->assertEquals('Test Country', $transformed['country']['name']);
    }

    /** @test */
    public function club_resource_transforms_correctly()
    {
        // Arrange
        $country = Country::factory()->create([
            'name' => 'Test Country',
        ]);

        $city = City::factory()->create([
            'name'       => 'Test City',
            'country_id' => $country->id,
        ]);

        $club = Club::factory()->create([
            'name'    => 'Test Club',
            'city_id' => $city->id,
        ]);

        // Load relationships
        $club->load('city.country');

        // Act
        $resource = new ClubResource($club);
        $transformed = $resource->toArray($this->createMock(\Illuminate\Http\Request::class));

        // Assert
        $this->assertEquals($club->id, $transformed['id']);
        $this->assertEquals('Test Club', $transformed['name']);
        $this->assertEquals('Test City', $transformed['city']);
        $this->assertEquals('Test Country', $transformed['country']);
    }
}
