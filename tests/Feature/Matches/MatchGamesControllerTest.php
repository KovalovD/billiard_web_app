<?php

use App\Core\Models\User;
use App\Leagues\DataTransferObjects\SendGameDTO;
use App\Leagues\DataTransferObjects\SendResultDTO;
use App\Leagues\Http\Requests\SendGameRequest;
use App\Leagues\Http\Requests\SendResultRequest;
use App\Leagues\Models\League;
use App\Leagues\Services\MatchGamesService;
use App\Matches\Enums\GameStatus;
use App\Matches\Http\Controllers\MatchGamesController;
use App\Matches\Models\MatchGame;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchGamesControllerTest extends TestCase
{
    use RefreshDatabase;

    private $controller;
    private $mockMatchGamesService;

    /** @test */
    public function it_sends_match_game()
    {
        // Arrange
        $league = League::factory()->create();
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $this->mockAuth::shouldReceive('user')
            ->once()
            ->andReturn($sender)
        ;

        $request = $this->mock(SendGameRequest::class);
        $request
            ->shouldReceive('validated')
            ->once()
            ->andReturn([
                'stream_url' => 'https://example.com/stream',
                'details'    => 'Test match details',
                'club_id'    => null,
            ])
        ;

        $sendGameDto = SendGameDTO::fromArray([
            'sender'     => $sender,
            'receiver'   => $receiver,
            'league'     => $league,
            'stream_url' => 'https://example.com/stream',
            'details'    => 'Test match details',
            'club_id'    => null,
        ]);

        $this->mockMatchGamesService
            ->shouldReceive('send')
            ->once()
            ->with(Mockery::on(function ($dto) use ($sendGameDto) {
                return $dto->sender === $sendGameDto->sender &&
                    $dto->receiver === $sendGameDto->receiver &&
                    $dto->league === $sendGameDto->league;
            }))
            ->andReturn(true)
        ;

        // Act
        $result = $this->controller->sendMatchGame($league, $receiver, $request);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_accepts_match()
    {
        // Arrange
        $league = League::factory()->create();
        $user = User::factory()->create();
        $matchGame = MatchGame::factory()->create([
            'league_id' => $league->id,
            'status'    => GameStatus::PENDING,
        ]);

        $this->mockAuth::shouldReceive('user')
            ->once()
            ->andReturn($user)
        ;

        $this->mockMatchGamesService
            ->shouldReceive('accept')
            ->once()
            ->with($user, $matchGame)
            ->andReturn(true)
        ;

        // Act
        $result = $this->controller->acceptMatch($league, $matchGame);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_declines_match()
    {
        // Arrange
        $league = League::factory()->create();
        $user = User::factory()->create();
        $matchGame = MatchGame::factory()->create([
            'league_id' => $league->id,
            'status'    => GameStatus::PENDING,
        ]);

        $this->mockAuth::shouldReceive('user')
            ->once()
            ->andReturn($user)
        ;

        $this->mockMatchGamesService
            ->shouldReceive('decline')
            ->once()
            ->with($user, $matchGame)
            ->andReturn(true)
        ;

        // Act
        $result = $this->controller->declineMatch($league, $matchGame);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_sends_match_result()
    {
        // Arrange
        $league = League::factory()->create();
        $user = User::factory()->create();
        $matchGame = MatchGame::factory()->create([
            'league_id' => $league->id,
            'status'    => GameStatus::IN_PROGRESS,
        ]);

        $this->mockAuth::shouldReceive('user')
            ->once()
            ->andReturn($user)
        ;

        $request = $this->mock(SendResultRequest::class);
        $request
            ->shouldReceive('validated')
            ->once()
            ->with('first_user_score')
            ->andReturn(5)
        ;

        $request
            ->shouldReceive('validated')
            ->once()
            ->with('second_user_score')
            ->andReturn(3)
        ;

        $resultDto = SendResultDTO::fromArray([
            'first_user_score'  => 5,
            'second_user_score' => 3,
            'matchGame'         => $matchGame,
        ]);

        $this->mockMatchGamesService
            ->shouldReceive('sendResult')
            ->once()
            ->with($user, Mockery::on(function ($dto) use ($resultDto) {
                return $dto->first_user_score === $resultDto->first_user_score &&
                    $dto->second_user_score === $resultDto->second_user_score &&
                    $dto->matchGame === $resultDto->matchGame;
            }))
            ->andReturn(true)
        ;

        // Act
        $result = $this->controller->sendResult($league, $matchGame, $request);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_fails_when_sending_invalid_match_game()
    {
        // Arrange
        $league = League::factory()->create();
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $this->mockAuth::shouldReceive('user')
            ->once()
            ->andReturn($sender)
        ;

        $request = $this->mock(SendGameRequest::class);
        $request
            ->shouldReceive('validated')
            ->once()
            ->andReturn([
                'stream_url' => 'https://example.com/stream',
                'details'    => 'Test match details',
                'club_id'    => null,
            ])
        ;

        $this->mockMatchGamesService
            ->shouldReceive('send')
            ->once()
            ->andReturn(false)
        ; // Service returns false (validation failure)

        // Act
        $result = $this->controller->sendMatchGame($league, $receiver, $request);

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function it_fails_when_sending_equal_scores()
    {
        // Arrange
        $league = League::factory()->create();
        $user = User::factory()->create();
        $matchGame = MatchGame::factory()->create([
            'league_id' => $league->id,
            'status'    => GameStatus::IN_PROGRESS,
        ]);

        $this->mockAuth::shouldReceive('user')
            ->once()
            ->andReturn($user)
        ;

        $request = $this->mock(SendResultRequest::class);
        $request
            ->shouldReceive('validated')
            ->once()
            ->with('first_user_score')
            ->andReturn(5)
        ;

        $request
            ->shouldReceive('validated')
            ->once()
            ->with('second_user_score')
            ->andReturn(5)
        ; // Equal scores

        $this->mockMatchGamesService
            ->shouldReceive('sendResult')
            ->once()
            ->andReturn(false)
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

        // Mock Auth facade
        $this->mockAuth = $this->mock('Illuminate\Support\Facades\Auth');
    }
}
