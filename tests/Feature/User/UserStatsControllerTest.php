<?php

namespace Tests\Feature\User;

use App\Core\Models\User;
use App\User\Http\Controllers\UserStatsController;
use App\User\Services\UserStatsService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use JsonException;
use PHPUnit\Framework\MockObject\Exception;
use Tests\TestCase;

class UserStatsControllerTest extends TestCase
{
    use RefreshDatabase;

    private $controller;
    private $statsService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock service
        $this->statsService = $this->mock(UserStatsService::class);
        $this->controller = new UserStatsController($this->statsService);
    }

    /** @test
     * @throws Exception
     */
    public function it_returns_user_ratings(): void
    {
        // Arrange
        $user = User::factory()->create();
        $mockRatings = collect([
            (object) ['id' => 1, 'league_id' => 1, 'user_id' => $user->id, 'rating' => 1000],
            (object) ['id' => 2, 'league_id' => 2, 'user_id' => $user->id, 'rating' => 1100],
        ]);

        // Mock Auth facade with app container to avoid redeclaration issues
        $this->app->instance('auth', $auth = $this->createMock(Guard::class));
        $auth->method('user')->willReturn($user);

        $this->statsService
            ->expects('getUserRatings')
            ->with($user)
            ->andReturns($mockRatings)
        ;

        // Act
        $result = $this->controller->ratings();

        // Assert
        $this->assertInstanceOf(AnonymousResourceCollection::class, $result);
        $this->assertEquals(2, $result->count());
    }

    /** @test
     * @throws Exception
     */
    public function it_returns_user_matches(): void
    {
        // Arrange
        $user = User::factory()->create();
        $mockMatches = collect([
            (object) ['id' => 1, 'league_id' => 1, 'status' => 'completed'],
            (object) ['id' => 2, 'league_id' => 1, 'status' => 'in_progress'],
            (object) ['id' => 3, 'league_id' => 2, 'status' => 'completed'],
        ]);

        // Mock Auth facade with app container
        $this->app->instance('auth', $auth = $this->createMock(Guard::class));
        $auth->method('user')->willReturn($user);

        $this->statsService
            ->expects('getUserMatches')
            ->with($user)
            ->andReturns($mockMatches)
        ;

        // Act
        $result = $this->controller->matches();

        // Assert
        $this->assertInstanceOf(AnonymousResourceCollection::class, $result);
        $this->assertEquals(3, $result->count());
    }

    /** @test
     * @throws JsonException|Exception
     */
    public function it_returns_user_stats(): void
    {
        // Arrange
        $user = User::factory()->create();
        $mockStats = [
            'total_matches'     => 10,
            'completed_matches' => 8,
            'wins'              => 5,
            'losses'            => 3,
            'win_rate'          => 63,
            'leagues_count'     => 2,
            'highest_rating'    => 1200,
            'average_rating'    => 1100,
        ];

        // Mock Auth facade with app container
        $this->app->instance('auth', $auth = $this->createMock(Guard::class));
        $auth->method('user')->willReturn($user);

        $this->statsService
            ->expects('getUserStats')
            ->with($user)
            ->andReturns($mockStats)
        ;

        // Act
        $result = $this->controller->stats();

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());

        $data = json_decode($result->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals($mockStats, $data);
    }

    /** @test
     * @throws JsonException
     * @throws Exception
     */
    public function it_returns_game_type_stats(): void
    {
        // Arrange
        $user = User::factory()->create();
        $mockGameTypeStats = [
            'pool'    => [
                'matches'  => 5,
                'wins'     => 3,
                'losses'   => 2,
                'win_rate' => 60,
            ],
            'snooker' => [
                'matches'  => 3,
                'wins'     => 1,
                'losses'   => 2,
                'win_rate' => 33,
            ],
        ];

        // Mock Auth facade with app container
        $this->app->instance('auth', $auth = $this->createMock(Guard::class));
        $auth->method('user')->willReturn($user);

        $this->statsService
            ->expects('getGameTypeStats')
            ->with($user)
            ->andReturns($mockGameTypeStats)
        ;

        // Act
        $result = $this->controller->gameTypeStats();

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());

        $data = json_decode($result->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals($mockGameTypeStats, $data);
    }
}
