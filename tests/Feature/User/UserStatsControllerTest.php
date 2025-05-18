<?php

use App\Core\Models\User;
use App\User\Http\Controllers\UserStatsController;
use App\User\Services\UserStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Tests\TestCase;

class UserStatsControllerTest extends TestCase
{
    use RefreshDatabase;

    private $controller;
    private $mockStatsService;

    /** @test */
    public function it_returns_user_ratings()
    {
        // Arrange
        $user = User::factory()->create();
        $mockRatings = collect([
            (object) ['id' => 1, 'league_id' => 1, 'user_id' => $user->id, 'rating' => 1000],
            (object) ['id' => 2, 'league_id' => 2, 'user_id' => $user->id, 'rating' => 1100],
        ]);

        $this->mockAuth::shouldReceive('user')
            ->once()
            ->andReturn($user)
        ;

        $this->mockStatsService
            ->shouldReceive('getUserRatings')
            ->once()
            ->with($user)
            ->andReturn($mockRatings)
        ;

        // Act
        $result = $this->controller->ratings();

        // Assert
        $this->assertInstanceOf(AnonymousResourceCollection::class, $result);
        $this->assertEquals(2, $result->count());
    }

    /** @test */
    public function it_returns_user_matches()
    {
        // Arrange
        $user = User::factory()->create();
        $mockMatches = collect([
            (object) ['id' => 1, 'league_id' => 1, 'status' => 'completed'],
            (object) ['id' => 2, 'league_id' => 1, 'status' => 'in_progress'],
            (object) ['id' => 3, 'league_id' => 2, 'status' => 'completed'],
        ]);

        $this->mockAuth::shouldReceive('user')
            ->once()
            ->andReturn($user)
        ;

        $this->mockStatsService
            ->shouldReceive('getUserMatches')
            ->once()
            ->with($user)
            ->andReturn($mockMatches)
        ;

        // Act
        $result = $this->controller->matches();

        // Assert
        $this->assertInstanceOf(AnonymousResourceCollection::class, $result);
        $this->assertEquals(3, $result->count());
    }

    /** @test */
    public function it_returns_user_stats()
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

        $this->mockAuth::shouldReceive('user')
            ->once()
            ->andReturn($user)
        ;

        $this->mockStatsService
            ->shouldReceive('getUserStats')
            ->once()
            ->with($user)
            ->andReturn($mockStats)
        ;

        // Act
        $result = $this->controller->stats();

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals($mockStats, $data);
    }

    /** @test */
    public function it_returns_game_type_stats()
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

        $this->mockAuth::shouldReceive('user')
            ->once()
            ->andReturn($user)
        ;

        $this->mockStatsService
            ->shouldReceive('getGameTypeStats')
            ->once()
            ->with($user)
            ->andReturn($mockGameTypeStats)
        ;

        // Act
        $result = $this->controller->gameTypeStats();

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals($mockGameTypeStats, $data);
    }

    /** @test */
    public function it_handles_user_with_no_data()
    {
        // Arrange
        $user = User::factory()->create();
        $emptyStats = [
            'total_matches'     => 0,
            'completed_matches' => 0,
            'wins'              => 0,
            'losses'            => 0,
            'win_rate'          => 0,
            'leagues_count'     => 0,
            'highest_rating'    => 0,
            'average_rating'    => 0,
        ];

        $this->mockAuth::shouldReceive('user')
            ->once()
            ->andReturn($user)
        ;

        $this->mockStatsService
            ->shouldReceive('getUserStats')
            ->once()
            ->with($user)
            ->andReturn($emptyStats)
        ;

        // Act
        $result = $this->controller->stats();

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals($emptyStats, $data);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockStatsService = $this->mock(UserStatsService::class);
        $this->controller = new UserStatsController($this->mockStatsService);

        // Mock Auth facade
        $this->mockAuth = $this->mock('Illuminate\Support\Facades\Auth');
    }
}
