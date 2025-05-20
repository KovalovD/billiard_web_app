<?php

namespace Tests\Feature\Matches;

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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use JsonException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Throwable;

class MultiplayerGamesControllerTest extends TestCase
{
    use RefreshDatabase;

    private $controller;
    private $mockMultiplayerGameService;

    /**
     * @throws JsonException
     */
    #[Test] public function it_gets_all_multiplayer_games_for_league(): void
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
            ->expects('getAll')
            ->with($league)
            ->andReturns($multiplayerGames)
        ;

        // Act
        $result = $this->controller->index($league);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());

        $data = json_decode($result->getContent(), false, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(2, $data);
        $this->assertEquals('Test Game 1', $data[0]->name);
        $this->assertEquals('Test Game 2', $data[1]->name);
    }

    /**
     * @throws JsonException
     */
    #[Test] public function it_creates_multiplayer_game(): void
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
            ->expects('validated')
            ->andReturns($gameData)
        ;

        $this->mockMultiplayerGameService
            ->expects('create')
            ->with($league, $gameData)
            ->andReturns($createdGame)
        ;

        // Act
        $result = $this->controller->store($request, $league);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());

        $data = json_decode($result->getContent(), false, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('New Multiplayer Game', $data->name);
        $this->assertEquals(10, $data->max_players);
        $this->assertTrue($data->allow_player_targeting);
    }

    /**
     * @throws JsonException
     */
    #[Test] public function it_shows_specific_multiplayer_game(): void
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

        $data = json_decode($result->getContent(), false, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('Test Game', $data->name);
        $this->assertEquals('in_progress', $data->status);
    }

    /**
     * @throws JsonException
     */
    #[Test] public function it_joins_multiplayer_game(): void
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

        $this->mockAuth::expects('user')
            ->andReturns($user)
        ;

        $request = $this->mock(JoinMultiplayerGameRequest::class);

        $this->mockMultiplayerGameService
            ->expects('join')
            ->with($league, $multiplayerGame, $user)
            ->andReturns($multiplayerGame)
        ; // Return updated game

        // Act
        $result = $this->controller->join($request, $league, $multiplayerGame);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());

        $data = json_decode($result->getContent(), false, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('Test Game', $data->name);
    }

    /**
     * @throws JsonException
     */
    #[Test] public function it_leaves_multiplayer_game(): void
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

        $this->mockAuth::expects('user')
            ->andReturns($user)
        ;

        $this->mockMultiplayerGameService
            ->expects('leave')
            ->with($multiplayerGame, $user)
            ->andReturns($multiplayerGame)
        ; // Return updated game

        // Act
        $result = $this->controller->leave($league, $multiplayerGame);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());

        $data = json_decode($result->getContent(), false, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('Test Game', $data->name);
    }

    /**
     * @throws JsonException
     */
    #[Test] public function it_starts_multiplayer_game(): void
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
            ->expects('start')
            ->with($multiplayerGame)
            ->andReturns($startedGame)
        ;

        // Act
        $result = $this->controller->start($league, $multiplayerGame);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());

        $data = json_decode($result->getContent(), false, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('in_progress', $data->status);
    }

    /**
     * @throws JsonException
     */
    #[Test] public function it_cancels_multiplayer_game(): void
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
            ->expects('cancel')
            ->with($multiplayerGame)
        ;

        // Act
        $result = $this->controller->cancel($league, $multiplayerGame);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());

        $data = json_decode($result->getContent(), false, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('Game cancelled successfully.', $data->message);
    }

    /**
     * @throws JsonException
     */
    #[Test] public function it_sets_moderator(): void
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

        $this->mockAuth::expects('user')
            ->andReturns($user)
        ;

        $updatedGame = MultiplayerGame::factory()->make([
            'league_id'         => $league->id,
            'game_id'           => $game->id,
            'name'              => 'Test Game',
            'status'            => 'in_progress',
            'moderator_user_id' => $moderatorUser->id,
        ]);

        $request = $this->mock(Request::class);
        $request->user_id = $moderatorUser->id;

        $this->mockMultiplayerGameService
            ->expects('setModerator')
            ->with($multiplayerGame, $moderatorUser->id, $user)
            ->andReturns($updatedGame)
        ;

        // Act
        $result = $this->controller->setModerator($request, $league, $multiplayerGame);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());

        $data = json_decode($result->getContent(), false, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals($moderatorUser->id, $data->moderator_user_id);
    }

    /**
     * @throws Throwable
     */
    #[Test] public function it_performs_game_action(): void
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

        $this->mockAuth::expects('user')
            ->andReturns($user)
        ;

        $request = $this->mock(PerformGameActionRequest::class);
        $request
            ->expects('validated')
            ->with('action')
            ->andReturns('decrement_lives')
        ;

        $request
            ->expects('validated')
            ->with('target_user_id')
            ->andReturns($targetUser->id)
        ;

        $request
            ->expects('validated')
            ->with('card_type')
            ->andReturns(null)
        ;

        $request
            ->expects('validated')
            ->with('acting_user_id')
            ->andReturns(null)
        ;

        $this->mockMultiplayerGameService
            ->expects('performAction')
            ->with($user, $multiplayerGame, 'decrement_lives', $targetUser->id, null, null)
            ->andReturns($multiplayerGame)
        ;

        // Act
        $result = $this->controller->performAction($request, $league, $multiplayerGame);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());
    }

    #[Test] public function it_finishes_game(): void
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

        $this->mockAuth::expects('user')
            ->andReturns($user)
        ;

        $this->mockMultiplayerGameService
            ->expects('finishGame')
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
        $this->mockAuth = $this->mockStaticFacade(Auth::class);
    }
}
