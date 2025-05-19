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
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class MatchGamesControllerTest extends TestCase
{
    use RefreshDatabase;

    private $controller;
    private $mockMatchGamesService;

    /** @test */
    public function it_sends_match_game(): void
    {
        // Arrange
        $league = League::factory()->create();
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $this->mockAuth::expects('user')
            ->andReturns($sender)
        ;

        $request = $this->mock(SendGameRequest::class);
        $request
            ->expects('validated')
            ->andReturns([
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
            ->expects('send')
            ->with(Mockery::on(static function ($dto) use ($sendGameDto) {
                return $dto->sender === $sendGameDto->sender &&
                    $dto->receiver === $sendGameDto->receiver &&
                    $dto->league === $sendGameDto->league;
            }))
            ->andReturns(true)
        ;

        // Act
        $result = $this->controller->sendMatchGame($league, $receiver, $request);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_accepts_match(): void
    {
        // Arrange
        $league = League::factory()->create();
        $user = User::factory()->create();
        $matchGame = MatchGame::factory()->create([
            'league_id' => $league->id,
            'status'    => GameStatus::PENDING,
        ]);

        $this->mockAuth::expects('user')
            ->andReturns($user)
        ;

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

        $this->mockAuth::expects('user')
            ->andReturns($user)
        ;

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

        $this->mockAuth::expects('user')
            ->andReturns($user)
        ;

        $request = $this->mock(SendResultRequest::class);
        $request
            ->expects('validated')
            ->with('first_user_score')
            ->andReturns(5)
        ;

        $request
            ->expects('validated')
            ->with('second_user_score')
            ->andReturns(3)
        ;

        $resultDto = SendResultDTO::fromArray([
            'first_user_score'  => 5,
            'second_user_score' => 3,
            'matchGame'         => $matchGame,
        ]);

        $this->mockMatchGamesService
            ->expects('sendResult')
            ->with($user, Mockery::on(static function ($dto) use ($resultDto) {
                return $dto->first_user_score === $resultDto->first_user_score &&
                    $dto->second_user_score === $resultDto->second_user_score &&
                    $dto->matchGame === $resultDto->matchGame;
            }))
            ->andReturns(true)
        ;

        // Act
        $result = $this->controller->sendResult($league, $matchGame, $request);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_fails_when_sending_invalid_match_game(): void
    {
        // Arrange
        $league = League::factory()->create();
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $this->mockAuth::expects('user')
            ->andReturns($sender)
        ;

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

        $this->mockAuth::expects('user')
            ->andReturns($user)
        ;

        $request = $this->mock(SendResultRequest::class);
        $request
            ->expects('validated')
            ->with('first_user_score')
            ->andReturns(5)
        ;

        $request
            ->expects('validated')
            ->with('second_user_score')
            ->andReturns(5)
        ; // Equal scores

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

        // Mock Auth facade
        $this->mockAuth = $this->mock(Auth::class);
    }
}
