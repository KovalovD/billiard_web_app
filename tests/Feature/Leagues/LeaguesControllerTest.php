<?php

use App\Core\Models\Game;
use App\Core\Models\User;
use App\Leagues\Http\Controllers\LeaguesController;
use App\Leagues\Http\Requests\PutLeagueRequest;
use App\Leagues\Http\Resources\LeagueResource;
use App\Leagues\Http\Resources\MatchGameResource;
use App\Leagues\Http\Resources\RatingResource;
use App\Leagues\Models\League;
use App\Leagues\Models\Rating;
use App\Leagues\Services\LeaguesService;
use App\Leagues\Services\RatingService;
use App\Matches\Enums\GameStatus;
use App\Matches\Models\MatchGame;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Tests\TestCase;

class LeaguesControllerTest extends TestCase
{
    use RefreshDatabase;

    private $controller;
    private $mockLeaguesService;
    private $mockRatingService;

    public function setUp(): void
    {
        parent::setUp();

        $this->mockLeaguesService = $this->mock(LeaguesService::class);
        $this->mockRatingService = $this->mock(RatingService::class);

        $this->controller = new LeaguesController(
            $this->mockLeaguesService,
            $this->mockRatingService,
        );
    }

    /** @test */
    public function it_returns_all_leagues()
    {
        // Arrange
        $leagues = League::factory()->count(3)->create();

        $this->mockLeaguesService
            ->shouldReceive('index')
            ->once()
            ->andReturn($leagues)
        ;

        // Act
        $result = $this->controller->index();

        // Assert
        $this->assertInstanceOf(AnonymousResourceCollection::class, $result);
        $this->assertEquals(3, $result->count());
        $this->assertInstanceOf(LeagueResource::class, $result[0]);
    }

    /** @test */
    public function it_returns_single_league()
    {
        // Arrange
        $league = League::factory()->create();

        // Act
        $result = $this->controller->show($league);

        // Assert
        $this->assertInstanceOf(LeagueResource::class, $result);
        $this->assertEquals($league->id, $result->resource->id);
    }

    /** @test */
    public function it_creates_new_league()
    {
        // Arrange
        $game = Game::factory()->create();
        $leagueData = [
            'name'                           => 'Test League',
            'game_id'                        => $game->id,
            'picture'                        => 'https://example.com/image.jpg',
            'details'                        => 'Test details',
            'has_rating'                     => true,
            'start_rating'                   => 1000,
            'max_players'                    => 16,
            'max_score'                      => 7,
            'invite_days_expire'             => 2,
            'rating_change_for_winners_rule' => json_encode([
                ['range' => [0, 100], 'strong' => 20, 'weak' => 30],
            ]),
            'rating_change_for_losers_rule'  => json_encode([
                ['range' => [0, 100], 'strong' => -20, 'weak' => -30],
            ]),
        ];

        $newLeague = League::factory()->create($leagueData);

        $request = $this->mock(PutLeagueRequest::class);
        $request->shouldReceive('validated')->andReturn($leagueData);

        $this->mockLeaguesService
            ->shouldReceive('store')
            ->once()
            ->andReturn($newLeague)
        ;

        // Act
        $result = $this->controller->store($request);

        // Assert
        $this->assertInstanceOf(LeagueResource::class, $result);
        $this->assertEquals($newLeague->id, $result->resource->id);
    }

    /** @test */
    public function it_updates_league()
    {
        // Arrange
        $league = League::factory()->create();
        $updateData = [
            'name'                           => 'Updated League Name',
            'game_id'                        => $league->game_id,
            'has_rating'                     => true,
            'start_rating'                   => 1000,
            'max_players'                    => 16,
            'max_score'                      => 7,
            'invite_days_expire'             => 2,
            'rating_change_for_winners_rule' => json_encode([
                ['range' => [0, 100], 'strong' => 20, 'weak' => 30],
            ]),
            'rating_change_for_losers_rule'  => json_encode([
                ['range' => [0, 100], 'strong' => -20, 'weak' => -30],
            ]),
        ];

        $updatedLeague = League::factory()->create(array_merge(
            $league->toArray(),
            ['name' => 'Updated League Name'],
        ));

        $request = $this->mock(PutLeagueRequest::class);
        $request->shouldReceive('validated')->andReturn($updateData);

        $this->mockLeaguesService
            ->shouldReceive('update')
            ->once()
            ->andReturn($updatedLeague)
        ;

        // Act
        $result = $this->controller->update($request, $league);

        // Assert
        $this->assertInstanceOf(LeagueResource::class, $result);
        $this->assertEquals($updatedLeague->id, $result->resource->id);
        $this->assertEquals('Updated League Name', $result->resource->name);
    }

    /** @test */
    public function it_deletes_league()
    {
        // Arrange
        $league = League::factory()->create();

        $this->mockLeaguesService
            ->shouldReceive('destroy')
            ->once()
            ->with($league)
        ;

        // Act
        $result = $this->controller->destroy($league);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_league_players()
    {
        // Arrange
        $league = League::factory()->create();
        $users = User::factory()->count(3)->create();
        $ratings = collect();

        foreach ($users as $i => $user) {
            $ratings->push(Rating::create([
                'league_id'    => $league->id,
                'user_id'      => $user->id,
                'rating'       => 1000 + ($i * 100),
                'position'     => $i + 1,
                'is_active'    => true,
                'is_confirmed' => true,
            ]));
        }

        $this->mockRatingService
            ->shouldReceive('getRatingsWithUsers')
            ->once()
            ->with($league)
            ->andReturn($ratings)
        ;

        // Act
        $result = $this->controller->players($league);

        // Assert
        $this->assertInstanceOf(AnonymousResourceCollection::class, $result);
        $this->assertEquals(3, $result->count());
        $this->assertInstanceOf(RatingResource::class, $result[0]);
    }

    /** @test */
    public function it_returns_league_games()
    {
        // Arrange
        $league = League::factory()->create();
        $matches = MatchGame::factory()->count(2)->create([
            'league_id' => $league->id,
            'status'    => GameStatus::COMPLETED,
        ]);

        $this->mockLeaguesService
            ->shouldReceive('games')
            ->once()
            ->with($league)
            ->andReturn($matches)
        ;

        // Act
        $result = $this->controller->games($league);

        // Assert
        $this->assertInstanceOf(AnonymousResourceCollection::class, $result);
        $this->assertEquals(2, $result->count());
        $this->assertInstanceOf(MatchGameResource::class, $result[0]);
    }

    /** @test */
    public function it_returns_user_leagues_and_challenges()
    {
        // Arrange
        $user = User::factory()->create();
        $leagues = League::factory()->count(2)->create();
        $myLeaguesData = [
            $leagues[0]->id => [
                'league'        => $leagues[0],
                'rating'        => Rating::factory()->create([
                    'league_id' => $leagues[0]->id,
                    'user_id'   => $user->id,
                ]),
                'activeMatches' => collect([
                    MatchGame::factory()->create([
                        'league_id' => $leagues[0]->id,
                        'status'    => GameStatus::IN_PROGRESS,
                    ]),
                ]),
            ],
            $leagues[1]->id => [
                'league'        => $leagues[1],
                'rating'        => Rating::factory()->create([
                    'league_id' => $leagues[1]->id,
                    'user_id'   => $user->id,
                ]),
                'activeMatches' => collect([]),
            ],
        ];

        $this->actingAs($user);

        $this->mockLeaguesService
            ->shouldReceive('myLeaguesAndChallenges')
            ->once()
            ->with($user)
            ->andReturn($myLeaguesData)
        ;

        // Act
        $result = $this->controller->myLeaguesAndChallenges();

        // Assert
        $this->assertEquals($myLeaguesData, $result->original);
    }

    /** @test */
    public function it_loads_user_rating_for_league()
    {
        // Arrange
        $user = User::factory()->create();
        $league = League::factory()->create();
        $rating = Rating::factory()->create([
            'league_id' => $league->id,
            'user_id'   => $user->id,
            'is_active' => true,
        ]);

        $this->actingAs($user);

        // Mock the user's relationship
        $mockCollection = $this->mock('Illuminate\Database\Eloquent\Collection');
        $mockRelation = $this->mock('Illuminate\Database\Eloquent\Relations\HasMany');

        $mockRelation
            ->shouldReceive('with->where->first')
            ->once()
            ->andReturn($rating)
        ;

        $user
            ->shouldReceive('activeRatings')
            ->once()
            ->andReturn($mockRelation)
        ;

        // Act
        $result = $this->controller->loadUserRating($league);

        // Assert
        $this->assertInstanceOf(RatingResource::class, $result);
        $this->assertEquals($rating->id, $result->resource->id);
    }
}
