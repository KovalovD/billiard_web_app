<?php

namespace Tests\Feature\Matches;

use App\Core\Models\User;
use App\Leagues\Http\Requests\SendGameRequest;
use App\Leagues\Http\Requests\SendResultRequest;
use App\Leagues\Models\League;
use App\Leagues\Services\MatchGamesService;
use App\Matches\Enums\GameStatus;
use App\Matches\Http\Controllers\MatchGamesController;
use App\Matches\Models\MatchGame;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Throwable;

class MatchGamesControllerTest extends TestCase
{
    use RefreshDatabase;

    private $controller;
    private $mockMatchGamesService;
    private $authMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockMatchGamesService = $this->mock(MatchGamesService::class);
        $this->controller = new MatchGamesController($this->mockMatchGamesService);

        // Create a separate local Auth mock
        $this->authMock = $this->mockStaticFacade(Auth::class);
    }

    protected function tearDown(): void
    {
        // Explicitly unset any mockery instances
        $this->mockMatchGamesService = null;
        $this->authMock = null;
        $this->controller = null;

        parent::tearDown();
    }

    #[Test] public function it_sends_match_game(): void
    {
        // Arrange
        $league = League::factory()->create();
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        // Use the local auth mock
        $this->authMock->allows('user')->andReturn($sender);

        $request = $this->mock(SendGameRequest::class);
        $request
            ->expects('validated')
            ->andReturns([
                'stream_url' => 'https://example.com/stream',
                'details'    => 'Test match details',
                'club_id'    => null,
            ])
        ;

        $this->mockMatchGamesService
            ->expects('send')
            ->andReturns(true)
        ;

        // Act
        $result = $this->controller->sendMatchGame($league, $receiver, $request);

        // Assert
        $this->assertTrue($result);
    }

    #[Test] public function it_accepts_match(): void
    {
        // Arrange
        $league = League::factory()->create();
        $user = User::factory()->create();
        $matchGame = MatchGame::factory()->create([
            'league_id' => $league->id,
            'status'    => GameStatus::PENDING,
        ]);

        // Use the local auth mock
        $this->authMock->allows('user')->andReturn($user);

        $this->mockMatchGamesService
            ->expects('accept')
            ->with($user, $matchGame)
            ->andReturns(true)
        ;

        // Act
        $result = $this->controller->acceptMatch($league, $matchGame);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * @throws Throwable
     */
    #[Test] public function it_declines_match(): void
    {
        // Arrange
        $league = League::factory()->create();
        $user = User::factory()->create();
        $matchGame = MatchGame::factory()->create([
            'league_id' => $league->id,
            'status'    => GameStatus::PENDING,
        ]);

        // Use the local auth mock
        $this->authMock->allows('user')->andReturn($user);

        $this->mockMatchGamesService
            ->expects('decline')
            ->with($user, $matchGame)
            ->andReturns(true)
        ;

        // Act
        $result = $this->controller->declineMatch($league, $matchGame);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * @throws Throwable
     */
    #[Test] public function it_sends_match_result(): void
    {
        // Arrange
        $league = League::factory()->create();
        $user = User::factory()->create();
        $matchGame = MatchGame::factory()->create([
            'league_id' => $league->id,
            'status'    => GameStatus::IN_PROGRESS,
        ]);

        // Use the local auth mock
        $this->authMock->allows('user')->andReturn($user);

        // Create a Mockery mock instead of PHPUnit mock
        $request = Mockery::mock(SendResultRequest::class);

        // Use Mockery's method mocking approach
        $request
            ->allows('validated')
            ->with('first_user_score')
            ->andReturns(5)
        ;

        $request
            ->allows('validated')
            ->with('second_user_score')
            ->andReturns(3)
        ;

        $this->mockMatchGamesService
            ->expects('sendResult')
            ->andReturns(true)
        ;

        // Act
        $result = $this->controller->sendResult($league, $matchGame, $request);

        // Assert
        $this->assertTrue($result);
    }

    #[Test] public function it_fails_when_sending_invalid_match_game(): void
    {
        // Arrange
        $league = League::factory()->create();
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        // Use the local auth mock
        $this->authMock->allows('user')->andReturn($sender);

        $request = $this->mock(SendGameRequest::class);
        $request
            ->expects('validated')
            ->andReturns([
                'stream_url' => 'https://example.com/stream',
                'details'    => 'Test match details',
                'club_id'    => null,
            ])
        ;

        $this->mockMatchGamesService
            ->expects('send')
            ->andReturns(false)
        ; // Service returns false (validation failure)

        // Act
        $result = $this->controller->sendMatchGame($league, $receiver, $request);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @throws Throwable
     */
    #[Test] public function it_fails_when_sending_equal_scores(): void
    {
        // Arrange
        $league = League::factory()->create();
        $user = User::factory()->create();
        $matchGame = MatchGame::factory()->create([
            'league_id' => $league->id,
            'status'    => GameStatus::IN_PROGRESS,
        ]);

        // Use the local auth mock
        $this->authMock->allows('user')->andReturn($user);

        // Create a Mockery mock instead of PHPUnit mock
        $request = Mockery::mock(SendResultRequest::class);

        // Use Mockery's method mocking approach
        $request
            ->allows('validated')
            ->with('first_user_score')
            ->andReturns(5)
        ;

        $request
            ->allows('validated')
            ->with('second_user_score')
            ->andReturns(5)
        ; // Equal scores!

        $this->mockMatchGamesService
            ->expects('sendResult')
            ->andReturns(false)
        ; // Service returns false (validation failure)

        // Act
        $result = $this->controller->sendResult($league, $matchGame, $request);

        // Assert
        $this->assertFalse($result);
    }
}
