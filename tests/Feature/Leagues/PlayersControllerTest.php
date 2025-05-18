<?php

use App\Core\Models\User;
use App\Leagues\Http\Controllers\PlayersController;
use App\Leagues\Models\League;
use App\Leagues\Models\Rating;
use App\Leagues\Services\RatingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayersControllerTest extends TestCase
{
    use RefreshDatabase;

    private $controller;
    private $mockRatingService;

    /** @test */
    public function it_enters_user_to_league()
    {
        // Arrange
        $league = League::factory()->create();
        $user = User::factory()->create();

        $this->mockAuth::shouldReceive('user')
            ->once()
            ->andReturn($user)
        ;

        $this->mockRatingService
            ->shouldReceive('addPlayer')
            ->once()
            ->with($league, $user)
            ->andReturn(true)
        ;

        // Act
        $result = $this->controller->enter($league);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_enters_user_to_league_with_failure()
    {
        // Arrange
        $league = League::factory()->create([
            'max_players' => 10, // Maximum players allowed
        ]);
        $user = User::factory()->create();

        $this->mockAuth::shouldReceive('user')
            ->once()
            ->andReturn($user)
        ;

        $this->mockRatingService
            ->shouldReceive('addPlayer')
            ->once()
            ->with($league, $user)
            ->andReturn(false)
        ; // League is full or other failure

        // Act
        $result = $this->controller->enter($league);

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function it_leaves_league()
    {
        // Arrange
        $league = League::factory()->create();
        $user = User::factory()->create();

        // Create an active rating for the user
        $rating = Rating::create([
            'league_id'    => $league->id,
            'user_id'      => $user->id,
            'rating'       => 1000,
            'position'     => 1,
            'is_active'    => true,
            'is_confirmed' => true,
        ]);

        $this->mockAuth::shouldReceive('user')
            ->once()
            ->andReturn($user)
        ;

        $this->mockRatingService
            ->shouldReceive('disablePlayer')
            ->once()
            ->with($league, $user)
        ;

        // Act
        $result = $this->controller->leave($league);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_handles_invalid_league_or_user()
    {
        // Arrange
        $league = League::factory()->create();
        $user = User::factory()->create();

        $this->mockAuth::shouldReceive('user')
            ->andReturn($user)
        ;

        $this->mockRatingService
            ->shouldReceive('addPlayer')
            ->once()
            ->with($league, $user)
            ->andThrow(new Exception('Invalid operation'))
        ;

        // Act & Assert
        $this->expectException(Exception::class);
        $this->controller->enter($league);
    }

    /** @test */
    public function it_prevents_operations_on_closed_leagues()
    {
        // Arrange
        $league = League::factory()->create([
            'finished_at' => now()->subDay(), // League is already finished
        ]);
        $user = User::factory()->create();

        $this->mockAuth::shouldReceive('user')
            ->andReturn($user)
        ;

        $this->mockRatingService
            ->shouldReceive('addPlayer')
            ->once()
            ->with($league, $user)
            ->andReturn(false)
        ; // Shouldn't be able to join finished league

        // Act
        $result = $this->controller->enter($league);

        // Assert
        $this->assertFalse($result);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRatingService = $this->mock(RatingService::class);
        $this->controller = new PlayersController($this->mockRatingService);

        // Mock Auth facade
        $this->mockAuth = $this->mock('Illuminate\Support\Facades\Auth');
    }
}
