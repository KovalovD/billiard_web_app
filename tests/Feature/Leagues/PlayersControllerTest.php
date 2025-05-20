<?php

namespace Tests\Feature\Leagues;

use App\Core\Models\User;
use App\Leagues\Http\Controllers\PlayersController;
use App\Leagues\Models\League;
use App\Leagues\Models\Rating;
use App\Leagues\Services\RatingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Throwable;

class PlayersControllerTest extends TestCase
{
    use RefreshDatabase;

    private $controller;
    private $mockRatingService;
    private $authMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRatingService = $this->mock(RatingService::class);
        $this->controller = new PlayersController($this->mockRatingService);

        // Create local auth mock
        $this->authMock = $this->mockStaticFacade(Auth::class);
    }

    protected function tearDown(): void
    {
        // Clean up mockery instances
        $this->mockRatingService = null;
        $this->authMock = null;
        $this->controller = null;

        parent::tearDown();
    }

    /**
     * @throws Throwable
     */
    #[Test] public function it_enters_user_to_league(): void
    {
        // Arrange
        $league = League::factory()->create();
        $user = User::factory()->create();

        // Use the local auth mock
        $this->authMock->allows('user')->andReturn($user);

        $this->mockRatingService
            ->expects('addPlayer')
            ->with($league, $user)
            ->andReturns(true)
        ;

        // Act
        $result = $this->controller->enter($league);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * @throws Throwable
     */
    #[Test] public function it_enters_user_to_league_with_failure(): void
    {
        // Arrange
        $league = League::factory()->create([
            'max_players' => 10, // Maximum players allowed
        ]);
        $user = User::factory()->create();

        // Use the local auth mock
        $this->authMock->allows('user')->andReturn($user);

        $this->mockRatingService
            ->expects('addPlayer')
            ->with($league, $user)
            ->andReturns(false)
        ; // League is full or other failure

        // Act
        $result = $this->controller->enter($league);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @throws Throwable
     */
    #[Test] public function it_leaves_league(): void
    {
        // Arrange
        $league = League::factory()->create();
        $user = User::factory()->create();

        // Create an active rating for the user
        Rating::create([
            'league_id'    => $league->id,
            'user_id'      => $user->id,
            'rating'       => 1000,
            'position'     => 1,
            'is_active'    => true,
            'is_confirmed' => true,
        ]);

        // Use the local auth mock
        $this->authMock->allows('user')->andReturn($user);

        $this->mockRatingService
            ->expects('disablePlayer')
            ->with($league, $user)
        ;

        // Act
        $result = $this->controller->leave($league);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * @throws Throwable
     */
    #[Test] public function it_prevents_operations_on_closed_leagues(): void
    {
        // Arrange
        $league = League::factory()->create([
            'finished_at' => now()->subDay(), // League is already finished
        ]);
        $user = User::factory()->create();

        // Use the local auth mock
        $this->authMock->allows('user')->andReturn($user);

        $this->mockRatingService
            ->expects('addPlayer')
            ->with($league, $user)
            ->andReturns(false)
        ; // Shouldn't be able to join finished league

        // Act
        $result = $this->controller->enter($league);

        // Assert
        $this->assertFalse($result);
    }
}
