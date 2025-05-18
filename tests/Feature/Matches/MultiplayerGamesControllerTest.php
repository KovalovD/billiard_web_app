<?php

use App\Core\Models\Game;
use App\Core\Models\User;
use App\Leagues\Enums\RatingType;
use App\Leagues\Models\League;
use App\Matches\Http\Controllers\MultiplayerGamesController;
use App\Matches\Http\Requests\CreateMultiplayerGameRequest;
use App\Matches\Http\Requests\JoinMultiplayerGameRequest;
use App\Matches\Http\Requests\PerformGameActionRequest;
use App\Matches\Models\MultiplayerGame;
use App\Matches\Services\MultiplayerGameService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

class MultiplayerGamesControllerTest extends TestCase
{
    use RefreshDatabase;

    private $controller;
    private $mockMultiplayerGameService;

    /** @test */
    public function it_gets_all_multiplayer_games_for_league()
    {
        // Arrange
        $game = Game::factory()->create(['is_multiplayer' => true]);
        $league = League::factory()->create([
            'game_id'     => $game->id,
            'rating_type' => RatingType::KillerPool,
        ]);

        $multiplayerGames = collect([
            MultiplayerGame::factory()->create([
                'league_id' => $league->id,
                'game_id'   => $game->id,
                'name'      => 'Test Game 1',
                'status'    => 'registration',
            ]),
            MultiplayerGame::factory()->create([
                'league_id' => $league->id,
                'game_id'   => $game->id,
                'name'      => 'Test Game 2',
                'status'    => 'in_progress',
            ]),
        ]);

        $this->mockMultiplayerGameService
            ->shouldReceive('getAll')
            ->once()
            ->with($league)
            ->andReturn($multiplayerGames)
        ;

        // Act
        $result = $this->controller->index($league);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());

        $data = json_decode($result->getContent());
        $this->assertCount(2, $data);
        $this->assertEquals('Test Game 1', $data[0]->name);
        $this->assertEquals('Test Game 2', $data[1]->name);
    }

    /** @test */
    public function it_creates_multiplayer_game()
    {
        // Arrange
        $game = Game::factory()->create(['is_multiplayer' => true]);
        $league = League::factory()->create([
            'game_id'     => $game->id,
            'rating_type' => RatingType::KillerPool,
        ]);

        $gameData = [
            'name'                   => 'New Multiplayer Game',
            'max_players'            => 10,
            'allow_player_targeting' => true,
            'entrance_fee'           => 300,
        ];

        $createdGame = MultiplayerGame::factory()->create(array_merge(
            [
                'league_id' => $league->id,
                'game_id'   => $game->id,
                'status'    => 'registration',
            ],
            $gameData,
        ));

        $request = $this->mock(CreateMultiplayerGameRequest::class);
        $request
            ->shouldReceive('validated')
            ->once()
            ->andReturn($gameData)
        ;

        $this->mockMultiplayerGameService
            ->shouldReceive('create')
            ->once()
            ->with($league, $gameData)
            ->andReturn($createdGame)
        ;

        // Act
        $result = $this->controller->store($request, $league);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());

        $data = json_decode($result->getContent());
        $this->assertEquals('New Multiplayer Game', $data->name);
        $this->assertEquals(10, $data->max_players);
        $this->assertEquals(true, $data->allow_player_targeting);
    }

    /** @test */
    public function it_shows_specific_multiplayer_game()
    {
        // Arrange
        $game = Game::factory()->create(['is_multiplayer' => true]);
        $league = League::factory()->create([
            'game_id'     => $game->id,
            'rating_type' => RatingType::KillerPool,
        ]);

        $multiplayerGame = MultiplayerGame::factory()->create([
            'league_id' => $league->id,
            'game_id'   => $game->id,
            'name'      => 'Test Game',
            'status'    => 'in_progress',
        ]);

        // Act
        $result = $this->controller->show($league, $multiplayerGame);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());

        $data = json_decode($result->getContent());
        $this->assertEquals('Test Game', $data->name);
        $this->assertEquals('in_progress', $data->status);
    }

    /** @test */
    public function it_joins_multiplayer_game()
    {
        // Arrange
        $game = Game::factory()->create(['is_multiplayer' => true]);
        $league = League::factory()->create([
            'game_id'     => $game->id,
            'rating_type' => RatingType::KillerPool,
        ]);

        $multiplayerGame = MultiplayerGame::factory()->create([
            'league_id' => $league->id,
            'game_id'   => $game->id,
            'name'      => 'Test Game',
            'status'    => 'registration',
        ]);

        $user = User::factory()->create();

        $this->mockAuth::shouldReceive('user')
            ->once()
            ->andReturn($user)
        ;

        $request = $this->mock(JoinMultiplayerGameRequest::class);

        $this->mockMultiplayerGameService
            ->shouldReceive('join')
            ->once()
            ->with($league, $multiplayerGame, $user)
            ->andReturn($multiplayerGame)
        ; // Return updated game

        // Act
        $result = $this->controller->join($request, $league, $multiplayerGame);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());

        $data = json_decode($result->getContent());
        $this->assertEquals('Test Game', $data->name);
    }

    /** @test */
    public function it_leaves_multiplayer_game()
    {
        // Arrange
        $game = Game::factory()->create(['is_multiplayer' => true]);
        $league = League::factory()->create([
            'game_id'     => $game->id,
            'rating_type' => RatingType::KillerPool,
        ]);

        $multiplayerGame = MultiplayerGame::factory()->create([
            'league_id' => $league->id,
            'game_id'   => $game->id,
            'name'      => 'Test Game',
            'status'    => 'registration',
        ]);

        $user = User::factory()->create();

        $this->mockAuth::shouldReceive('user')
            ->once()
            ->andReturn($user)
        ;

        $this->mockMultiplayerGameService
            ->shouldReceive('leave')
            ->once()
            ->with($multiplayerGame, $user)
            ->andReturn($multiplayerGame)
        ; // Return updated game

        // Act
        $result = $this->controller->leave($league, $multiplayerGame);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());

        $data = json_decode($result->getContent());
        $this->assertEquals('Test Game', $data->name);
    }

    /** @test */
    public function it_starts_multiplayer_game()
    {
        // Arrange
        $game = Game::factory()->create(['is_multiplayer' => true]);
        $league = League::factory()->create([
            'game_id'     => $game->id,
            'rating_type' => RatingType::KillerPool,
        ]);

        $multiplayerGame = MultiplayerGame::factory()->create([
            'league_id' => $league->id,
            'game_id'   => $game->id,
            'name'      => 'Test Game',
            'status'    => 'registration',
        ]);

        $startedGame = MultiplayerGame::factory()->create([
            'league_id'  => $league->id,
            'game_id'    => $game->id,
            'name'       => 'Test Game',
            'status'     => 'in_progress',
            'started_at' => now(),
        ]);

        $this->mockMultiplayerGameService
            ->shouldReceive('start')
            ->once()
            ->with($multiplayerGame)
            ->andReturn($startedGame)
        ;

        // Act
        $result = $this->controller->start($league, $multiplayerGame);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());

        $data = json_decode($result->getContent());
        $this->assertEquals('in_progress', $data->status);
    }

    /** @test */
    public function it_cancels_multiplayer_game()
    {
        // Arrange
        $game = Game::factory()->create(['is_multiplayer' => true]);
        $league = League::factory()->create([
            'game_id'     => $game->id,
            'rating_type' => RatingType::KillerPool,
        ]);

        $multiplayerGame = MultiplayerGame::factory()->create([
            'league_id' => $league->id,
            'game_id'   => $game->id,
            'name'      => 'Test Game',
            'status'    => 'registration',
        ]);

        $this->mockMultiplayerGameService
            ->shouldReceive('cancel')
            ->once()
            ->with($multiplayerGame)
        ;

        // Act
        $result = $this->controller->cancel($league, $multiplayerGame);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());

        $data = json_decode($result->getContent());
        $this->assertEquals('Game cancelled successfully.', $data->message);
    }

    /** @test */
    public function it_sets_moderator()
    {
        // Arrange
        $game = Game::factory()->create(['is_multiplayer' => true]);
        $league = League::factory()->create([
            'game_id'     => $game->id,
            'rating_type' => RatingType::KillerPool,
        ]);

        $multiplayerGame = MultiplayerGame::factory()->create([
            'league_id' => $league->id,
            'game_id'   => $game->id,
            'name'      => 'Test Game',
            'status'    => 'in_progress',
        ]);

        $user = User::factory()->create();
        $moderatorUser = User::factory()->create();

        $this->mockAuth::shouldReceive('user')
            ->once()
            ->andReturn($user)
        ;

        $updatedGame = MultiplayerGame::factory()->make([
            'league_id'         => $league->id,
            'game_id'           => $game->id,
            'name'              => 'Test Game',
            'status'            => 'in_progress',
            'moderator_user_id' => $moderatorUser->id,
        ]);

        $request = $this->mock(\Illuminate\Http\Request::class);
        $request->user_id = $moderatorUser->id;

        $this->mockMultiplayerGameService
            ->shouldReceive('setModerator')
            ->once()
            ->with($multiplayerGame, $moderatorUser->id, $user)
            ->andReturn($updatedGame)
        ;

        // Act
        $result = $this->controller->setModerator($request, $league, $multiplayerGame);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());

        $data = json_decode($result->getContent());
        $this->assertEquals($moderatorUser->id, $data->moderator_user_id);
    }

    /** @test */
    public function it_performs_game_action()
    {
        // Arrange
        $game = Game::factory()->create(['is_multiplayer' => true]);
        $league = League::factory()->create([
            'game_id'     => $game->id,
            'rating_type' => RatingType::KillerPool,
        ]);

        $multiplayerGame = MultiplayerGame::factory()->create([
            'league_id' => $league->id,
            'game_id'   => $game->id,
            'name'      => 'Test Game',
            'status'    => 'in_progress',
        ]);

        $user = User::factory()->create();
        $targetUser = User::factory()->create();

        $this->mockAuth::shouldReceive('user')
            ->once()
            ->andReturn($user)
        ;

        $request = $this->mock(PerformGameActionRequest::class);
        $request
            ->shouldReceive('validated')
            ->once()
            ->with('action')
            ->andReturn('decrement_lives')
        ;

        $request
            ->shouldReceive('validated')
            ->once()
            ->with('target_user_id')
            ->andReturn($targetUser->id)
        ;

        $request
            ->shouldReceive('validated')
            ->once()
            ->with('card_type')
            ->andReturn(null)
        ;

        $request
            ->shouldReceive('validated')
            ->once()
            ->with('acting_user_id')
            ->andReturn(null)
        ;

        $this->mockMultiplayerGameService
            ->shouldReceive('performAction')
            ->once()
            ->with($user, $multiplayerGame, 'decrement_lives', $targetUser->id, null, null)
            ->andReturn($multiplayerGame)
        ;

        // Act
        $result = $this->controller->performAction($request, $league, $multiplayerGame);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());
    }

    /** @test */
    public function it_finishes_game()
    {
        // Arrange
        $game = Game::factory()->create(['is_multiplayer' => true]);
        $league = League::factory()->create([
            'game_id'     => $game->id,
            'rating_type' => RatingType::KillerPool,
        ]);

        $multiplayerGame = MultiplayerGame::factory()->create([
            'league_id' => $league->id,
            'game_id'   => $game->id,
            'name'      => 'Test Game',
            'status'    => 'completed',
        ]);

        $user = User::factory()->create();

        $this->mockAuth::shouldReceive('user')
            ->once()
            ->andReturn($user)
        ;

        $this->mockMultiplayerGameService
            ->shouldReceive('finishGame')
            ->once()
            ->with($multiplayerGame, $user)
        ;

        // Act - This should not throw an exception
        $this->controller->finish($league, $multiplayerGame);

        // Assert - No assertions needed since method has void return type
        $this->addToAssertionCount(1);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockMultiplayerGameService = $this->mock(MultiplayerGameService::class);
        $this->controller = new MultiplayerGamesController($this->mockMultiplayerGameService);

        // Mock Auth facade
        $this->mockAuth = $this->mock('Illuminate\Support\Facades\Auth');
    }
}
