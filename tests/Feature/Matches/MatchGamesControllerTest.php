<?php

namespace Tests\Feature\Matches;

use App\Core\Models\User;
use App\Leagues\DataTransferObjects\SendGameDTO;
use App\Leagues\Http\Requests\SendGameRequest;
use App\Leagues\Http\Requests\SendResultRequest;
use App\Leagues\Models\League;
use App\Leagues\Services\MatchGamesService;
use App\Matches\Enums\GameStatus;
use App\Matches\Http\Controllers\MatchGamesController;
use App\Matches\Models\MatchGame;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\MockObject\Exception;
use Tests\TestCase;
use Throwable;

class MatchGamesControllerTest extends TestCase
{
    use RefreshDatabase;

    private $controller;
    private $mockMatchGamesService;

    /** @test
     * @throws Exception
     */
    public function it_sends_match_game(): void
    {
        // Arrange
        $league = League::factory()->create();
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        // Create mock Auth
        $this->app->instance('auth', $auth = $this->createMock(Guard::class));
        $auth->method('user')->willReturn($sender);

        $request = $this->mock(SendGameRequest::class);
        $request
            ->expects('validated')
            ->andReturns([
                'stream_url' => 'https://example.com/stream',
                'details'    => 'Test match details',
                'club_id'    => null,
            ])
        ;

        // Create expected DTO
        $dto = new SendGameDTO([
            'sender'     => $sender,
            'receiver'   => $receiver,
            'league'     => $league,
            'stream_url' => 'https://example.com/stream',
            'details'    => 'Test match details',
            'club_id'    => null,
        ]);

        $this->mockMatchGamesService
            ->expects('send')
            ->withArgs(function ($arg) use ($dto) {
                // Verify the important properties match
                return $arg->sender === $dto->sender &&
                    $arg->receiver === $dto->receiver &&
                    $arg->league === $dto->league;
            })
            ->andReturns(true)
        ;

        // Act
        $result = $this->controller->sendMatchGame($league, $receiver, $request);

        // Assert
        $this->assertTrue($result);
    }

    /** @test
     * @throws Exception
     */
    public function it_accepts_match(): void
    {
        // Arrange
        $league = League::factory()->create();
        $user = User::factory()->create();
        $matchGame = MatchGame::factory()->create([
            'league_id' => $league->id,
            'status'    => GameStatus::PENDING,
        ]);

        // Create mock Auth
        $this->app->instance('auth', $auth = $this->createMock(Guard::class));
        $auth->method('user')->willReturn($user);

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

    /** @test
     * @throws Throwable
     */
    public function it_declines_match(): void
    {
        // Arrange
        $league = League::factory()->create();
        $user = User::factory()->create();
        $matchGame = MatchGame::factory()->create([
            'league_id' => $league->id,
            'status'    => GameStatus::PENDING,
        ]);

        // Create mock Auth
        $this->app->instance('auth', $auth = $this->createMock(Guard::class));
        $auth->method('user')->willReturn($user);

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

    /** @test
     * @throws Throwable
     */
    public function it_sends_match_result(): void
    {
        // Arrange
        $league = League::factory()->create();
        $user = User::factory()->create();
        $matchGame = MatchGame::factory()->create([
            'league_id' => $league->id,
            'status'    => GameStatus::IN_PROGRESS,
        ]);

        // Create mock Auth
        $this->app->instance('auth', $auth = $this->createMock(Guard::class));
        $auth->method('user')->willReturn($user);

        $request = $this->createMock(SendResultRequest::class);

        // Mock the validated calls with specific parameters
        $request
            ->method('validated')
            ->willReturnMap([
                ['first_user_score', null, 5],
                ['second_user_score', null, 3],
            ])
        ;

        $this->mockMatchGamesService
            ->expects('sendResult')
            ->withArgs(function ($actualUser, $dto) use ($user, $matchGame) {
                return $actualUser === $user &&
                    $dto->first_user_score === 5 &&
                    $dto->second_user_score === 3 &&
                    $dto->matchGame === $matchGame;
            })
            ->andReturns(true)
        ;

        // Act
        $result = $this->controller->sendResult($league, $matchGame, $request);

        // Assert
        $this->assertTrue($result);
    }

    /** @test
     * @throws Exception
     */
    public function it_fails_when_sending_invalid_match_game(): void
    {
        // Arrange
        $league = League::factory()->create();
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        // Create mock Auth
        $this->app->instance('auth', $auth = $this->createMock(Guard::class));
        $auth->method('user')->willReturn($sender);

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

    /** @test
     * @throws Throwable
     */
    public function it_fails_when_sending_equal_scores(): void
    {
        // Arrange
        $league = League::factory()->create();
        $user = User::factory()->create();
        $matchGame = MatchGame::factory()->create([
            'league_id' => $league->id,
            'status'    => GameStatus::IN_PROGRESS,
        ]);

        // Create mock Auth
        $this->app->instance('auth', $auth = $this->createMock(Guard::class));
        $auth->method('user')->willReturn($user);

        $request = $this->createMock(SendResultRequest::class);

        // Mock the validated calls with specific parameters
        $request
            ->method('validated')
            ->willReturnMap([
                ['first_user_score', null, 5],
                ['second_user_score', null, 5], // Equal scores
            ])
        ;

        $this->mockMatchGamesService
            ->expects('sendResult')
            ->andReturns(false)
        ; // Service returns false (validation failure)

        // Act
        $result = $this->controller->sendResult($league, $matchGame, $request);

        // Assert
        $this->assertFalse($result);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockMatchGamesService = $this->mock(MatchGamesService::class);
        $this->controller = new MatchGamesController($this->mockMatchGamesService);
    }
}
