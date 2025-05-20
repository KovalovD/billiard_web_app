<?php

namespace Tests\Feature\User;

use App\Core\Models\User;
use App\User\Http\Controllers\UserStatsController;
use App\User\Services\UserStatsService;
use Auth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserStatsControllerTest extends TestCase
{
    use RefreshDatabase;

    private $controller;
    private $statsService;
    private $authMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock service
        $this->statsService = $this->mock(UserStatsService::class);
        $this->controller = new UserStatsController($this->statsService);

        // Create a separate auth mock
        $this->authMock = $this->mockStaticFacade(Auth::class);
    }

    protected function tearDown(): void
    {
        // Clean up mockery instances
        $this->statsService = null;
        $this->authMock = null;
        $this->controller = null;

        parent::tearDown();
    }

    #[Test] public function it_returns_user_ratings(): void
    {
        // Arrange
        $user = User::factory()->create();
        $mockRatings = collect([
            (object) ['id' => 1, 'league_id' => 1, 'user_id' => $user->id, 'rating' => 1000],
            (object) ['id' => 2, 'league_id' => 2, 'user_id' => $user->id, 'rating' => 1100],
        ]);

        // Use the local auth mock
        $this->authMock->allows('user')->andReturn($user);

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

    #[Test] public function it_returns_user_matches(): void
    {
        // Arrange
        $user = User::factory()->create();
        $mockMatches = collect([
            (object) ['id' => 1, 'league_id' => 1, 'status' => 'completed'],
            (object) ['id' => 2, 'league_id' => 1, 'status' => 'in_progress'],
            (object) ['id' => 3, 'league_id' => 2, 'status' => 'completed'],
        ]);

        // Use the local auth mock
        $this->authMock->allows('user')->andReturn($user);

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

    #[Test] public function it_returns_user_stats(): void
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

        // Use the local auth mock
        $this->authMock->allows('user')->andReturn($user);

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
        $this->assertEquals($mockStats, $result->getData(true));
    }

    #[Test] public function it_returns_game_type_stats(): void
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

        // Use the local auth mock
        $this->authMock->allows('user')->andReturn($user);

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
        $this->assertEquals($mockGameTypeStats, $result->getData(true));
    }
}
