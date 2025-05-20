<?php

namespace Tests\Feature\Leagues;

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
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use JsonException;
use PHPUnit\Framework\MockObject\Exception;
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
    public function it_returns_all_leagues(): void
    {
        // Arrange
        $leagues = League::factory()->count(3)->create();

        $this->mockLeaguesService
            ->expects('index')
            ->andReturns($leagues)
        ;

        // Act
        $result = $this->controller->index();

        // Assert
        $this->assertInstanceOf(AnonymousResourceCollection::class, $result);
        $this->assertEquals(3, $result->count());
        $this->assertInstanceOf(LeagueResource::class, $result[0]);
    }

    /** @test */
    public function it_returns_single_league(): void
    {
        // Arrange
        $league = League::factory()->create();

        // Act
        $result = $this->controller->show($league);

        // Assert
        $this->assertInstanceOf(LeagueResource::class, $result);
        $this->assertEquals($league->id, $result->resource->id);
    }

    /** @test
     * @throws JsonException|Exception
     */
    public function it_creates_new_league(): void
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
            ], JSON_THROW_ON_ERROR),
            'rating_change_for_losers_rule'  => json_encode([
                ['range' => [0, 100], 'strong' => -20, 'weak' => -30],
            ], JSON_THROW_ON_ERROR),
        ];

        $newLeague = League::factory()->create($leagueData);

        $request = $this->createMock(PutLeagueRequest::class);
        $request->method('validated')->willReturn($leagueData);

        $this->mockLeaguesService
            ->expects('store')
            ->andReturns($newLeague)
        ;

        // Act
        $result = $this->controller->store($request);

        // Assert
        $this->assertInstanceOf(LeagueResource::class, $result);
        $this->assertEquals($newLeague->id, $result->resource->id);
    }

    /** @test
     * @throws JsonException|Exception
     */
    public function it_updates_league(): void
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
            ], JSON_THROW_ON_ERROR),
            'rating_change_for_losers_rule'  => json_encode([
                ['range' => [0, 100], 'strong' => -20, 'weak' => -30],
            ], JSON_THROW_ON_ERROR),
        ];

        $updatedLeague = League::factory()->create(array_merge(
            $league->toArray(),
            ['name' => 'Updated League Name'],
        ));

        $request = $this->createMock(PutLeagueRequest::class);
        $request->method('validated')->willReturn($updateData);

        $this->mockLeaguesService
            ->expects('update')
            ->andReturns($updatedLeague)
        ;

        // Act
        $result = $this->controller->update($request, $league);

        // Assert
        $this->assertInstanceOf(LeagueResource::class, $result);
        $this->assertEquals($updatedLeague->id, $result->resource->id);
        $this->assertEquals('Updated League Name', $result->resource->name);
    }

    /** @test */
    public function it_deletes_league(): void
    {
        // Arrange
        $league = League::factory()->create();

        $this->mockLeaguesService
            ->expects('destroy')
            ->with($league)
        ;

        // Act
        $result = $this->controller->destroy($league);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_league_players(): void
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
            ->expects('getRatingsWithUsers')
            ->with($league)
            ->andReturns($ratings)
        ;

        // Act
        $result = $this->controller->players($league);

        // Assert
        $this->assertInstanceOf(AnonymousResourceCollection::class, $result);
        $this->assertEquals(3, $result->count());
        $this->assertInstanceOf(RatingResource::class, $result[0]);
    }

    /** @test */
    public function it_returns_league_games(): void
    {
        // Arrange
        $league = League::factory()->create();
        $matches = MatchGame::factory()->count(2)->create([
            'league_id' => $league->id,
            'status'    => GameStatus::COMPLETED,
        ]);

        $this->mockLeaguesService
            ->expects('games')
            ->with($league)
            ->andReturns($matches)
        ;

        // Act
        $result = $this->controller->games($league);

        // Assert
        $this->assertInstanceOf(AnonymousResourceCollection::class, $result);
        $this->assertEquals(2, $result->count());
        $this->assertInstanceOf(MatchGameResource::class, $result[0]);
    }

    /** @test
     * @throws Exception
     */
    public function it_returns_user_leagues_and_challenges(): void
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

        // Create mock Auth
        $this->app->instance('auth', $auth = $this->createMock(Guard::class));
        $auth->method('user')->willReturn($user);

        $this->mockLeaguesService
            ->expects('myLeaguesAndChallenges')
            ->with($user)
            ->andReturns($myLeaguesData)
        ;

        // Act
        $result = $this->controller->myLeaguesAndChallenges();

        // Assert
        $this->assertEquals($myLeaguesData, $result->original);
    }

    /** @test
     * @throws Exception
     * @throws Exception
     * @throws Exception
     */
    public function it_loads_user_rating_for_league(): void
    {
        // Arrange
        $user = User::factory()->create();
        $league = League::factory()->create();
        $rating = Rating::factory()->create([
            'league_id' => $league->id,
            'user_id'   => $user->id,
            'is_active' => true,
        ]);

        // Create mock Auth
        $this->app->instance('auth', $auth = $this->createMock(Guard::class));
        $auth->method('user')->willReturn($user);

        // Create a Collection mock that will be returned by activeRatings
        $mockCollection = $this->createMock(Collection::class);
        $mockCollection->method('merge')->willReturn($mockCollection);
        $mockCollection->method('where')->willReturn($mockCollection);
        $mockCollection->method('first')->willReturn(null);

        // Create a HasMany mock for the activeRatings relation
        $mockActiveRatings = $this->createMock(HasMany::class);

        // Set up the method chain for the relationshipe
        $mockActiveRatings
            ->method('with')
            ->willReturnSelf()
        ;
        $mockActiveRatings
            ->method('where')
            ->willReturnSelf()
        ;
        $mockActiveRatings
            ->method('first')
            ->willReturn($rating)
        ;

        // Set up the user to return our mock relation
        $user
            ->method('activeRatings')
            ->willReturn($mockActiveRatings)
        ;

        // Act
        $result = $this->controller->loadUserRating($league);

        // Assert
        $this->assertInstanceOf(RatingResource::class, $result);
        $this->assertEquals($rating->id, $result->resource->id);
    }
}
