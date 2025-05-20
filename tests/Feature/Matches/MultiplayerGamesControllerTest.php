<?php

namespace Tests\Feature\Matches;

use App\Core\Models\User;
use App\Leagues\Models\League;
use App\Matches\Http\Controllers\MultiplayerGamesController;
use App\Matches\Http\Requests\CreateMultiplayerGameRequest;
use App\Matches\Http\Requests\JoinMultiplayerGameRequest;
use App\Matches\Http\Requests\PerformGameActionRequest;
use App\Matches\Models\MultiplayerGame;
use App\Matches\Services\MultiplayerGameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use JsonException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Throwable;

class MultiplayerGamesControllerTest extends TestCase
{
    // Don't use RefreshDatabase trait to avoid transaction issues

    private $controller;
    private $mockMultiplayerGameService;
    private $mockAuth;

    // Set to empty array to prevent transactional behavior that causes issues
    protected array $connectionsToTransact = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure necessary tables exist
        $this->ensureTablesExist();

        // Create mock service
        $this->mockMultiplayerGameService = $this->mock(MultiplayerGameService::class);
        $this->controller = new MultiplayerGamesController($this->mockMultiplayerGameService);

        // Mock Auth facade
        $this->mockAuth = $this->mockStaticFacade(Auth::class);
    }

    protected function tearDown(): void
    {
        // Explicitly clean up mockery
        Mockery::close();

        parent::tearDown();
    }

    /**
     * Create necessary tables if they don't exist
     */
    private function ensureTablesExist(): void
    {
        // Check if the games table exists
        if (!Schema::hasTable('games')) {
            // Create games table for testing
            Schema::create('games', static function ($table) {
                $table->id();
                $table->string('name');
                $table->string('type');
                $table->boolean('is_multiplayer')->default(false);
                $table->json('rules')->nullable();
            });
        }

        // Check if leagues table exists
        if (!Schema::hasTable('leagues')) {
            Schema::create('leagues', static function ($table) {
                $table->id();
                $table->string('name');
                $table->foreignId('game_id')->nullable();
                $table->string('picture')->nullable();
                $table->text('details')->nullable();
                $table->boolean('has_rating')->default(true);
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->integer('start_rating')->default(1000);
                $table->json('rating_change_for_winners_rule')->nullable();
                $table->json('rating_change_for_losers_rule')->nullable();
                $table->string('rating_type')->default('elo');
                $table->integer('max_players')->default(0);
                $table->integer('max_score')->default(7);
                $table->integer('invite_days_expire')->default(2);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Create multiplayer_games table if it doesn't exist
        if (!Schema::hasTable('multiplayer_games')) {
            Schema::create('multiplayer_games', static function ($table) {
                $table->id();
                $table->foreignId('league_id');
                $table->foreignId('game_id');
                $table->string('name');
                $table->string('status')->default('registration');
                $table->integer('initial_lives')->nullable();
                $table->integer('max_players')->nullable();
                $table->timestamp('registration_ends_at')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->foreignId('moderator_user_id')->nullable();
                $table->foreignId('current_player_id')->nullable();
                $table->integer('next_turn_order')->nullable();
                $table->boolean('allow_player_targeting')->default(false);
                $table->integer('entrance_fee')->nullable();
                $table->integer('first_place_percent')->nullable();
                $table->integer('second_place_percent')->nullable();
                $table->integer('grand_final_percent')->nullable();
                $table->integer('penalty_fee')->nullable();
                $table->json('prize_pool')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Helper method to create a mock MultiplayerGame object
     * Fixed to properly handle model attribute access methods used by the resource
     *
     * @param  int  $id  The game ID
     * @param  string  $name  The game name
     * @param  string  $status  The game status
     * @param  array  $additionalProps  Additional properties for the mock
     * @return MockInterface
     */
    private function createMockGame(int $id, string $name, string $status, array $additionalProps = []): MockInterface
    {
        $game = Mockery::mock(MultiplayerGame::class);

        $properties = array_merge([
            'id'                  => $id,
            'name'                => $name,
            'status'              => $status,
            'league_id'           => 1,
            'game_id'             => 1,
            'active_players_count' => 0,
            'total_players_count' => 0,
            'players'             => collect([]),
        ], $additionalProps);

        // Mock all methods needed by the resource
        $game->allows('getAttribute')->andReturnUsing(function ($key) use ($properties) {
            return $properties[$key] ?? null;
        });

        // Fix for the property access checks
        $game->allows('offsetExists')->andReturnUsing(function ($key) use ($properties) {
            return array_key_exists($key, $properties);
        });

        $game->allows('offsetGet')->andReturnUsing(function ($key) use ($properties) {
            return $properties[$key] ?? null;
        });

        $game->allows('__get')->andReturnUsing(function ($key) use ($properties) {
            return $properties[$key] ?? null;
        });

        // Mock common methods
        $game->allows('load')->andReturnSelf();
        $game->allows('players')->andReturn(collect([]));

        // For JSON serialization
        $game->allows('jsonSerialize')->andReturn($properties);
        $game->allows('toArray')->andReturn($properties);

        return $game;
    }

    /**
     * @throws JsonException
     */
    #[Test] public function it_gets_all_multiplayer_games_for_league(): void
    {
        // Arrange
        // Use a proper mocked MultiplayerGame that can be serialized to JSON
        $multiplayerGames = collect([
            $this->createMockGame(1, 'Test Game 1', 'registration'),
            $this->createMockGame(2, 'Test Game 2', 'in_progress'),
        ]);

        // Mock league
        $league = Mockery::mock(League::class);
        $league->allows('getAttribute')->with('id')->andReturns(1);

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
        $gameData = [
            'name'                   => 'New Multiplayer Game',
            'max_players'            => 10,
            'allow_player_targeting' => true,
            'entrance_fee'           => 300,
        ];

        // Create a MultiplayerGame instance that properly works with the resource
        $createdGame = $this->createMockGame(1, 'New Multiplayer Game', 'registration', array_merge([
            'league_id'              => 1,
            'game_id'                => 1,
            'max_players'            => 10,
            'allow_player_targeting' => true,
            'entrance_fee'           => 300,
        ], $gameData));

        // Mock league
        $league = Mockery::mock(League::class);
        $league->allows('getAttribute')->with('id')->andReturns(1);

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
        // Create a better mock object for the MultiplayerGame
        $multiplayerGame = $this->createMockGame(1, 'Test Game', 'in_progress');

        $league = Mockery::mock(League::class);
        $league->allows('getAttribute')->with('id')->andReturns(1);

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
        $user = Mockery::mock(User::class);
        $user->allows('getAttribute')->with('id')->andReturns(1);

        // Create a better mock for MultiplayerGame
        $multiplayerGame = $this->createMockGame(1, 'Test Game', 'registration');

        $league = Mockery::mock(League::class);
        $league->allows('getAttribute')->with('id')->andReturns(1);

        $this->mockAuth
            ->expects('user')
            ->andReturns($user)
        ;

        $request = $this->mock(JoinMultiplayerGameRequest::class);

        $this->mockMultiplayerGameService
            ->expects('join')
            ->with($league, $multiplayerGame, $user)
            ->andReturns($multiplayerGame)
        ;

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
        $user = Mockery::mock(User::class);
        $user->allows('getAttribute')->with('id')->andReturns(1);

        // Create a better mock for MultiplayerGame
        $multiplayerGame = $this->createMockGame(1, 'Test Game', 'registration');

        $league = Mockery::mock(League::class);
        $league->allows('getAttribute')->with('id')->andReturns(1);

        $this->mockAuth
            ->expects('user')
            ->andReturns($user)
        ;

        $this->mockMultiplayerGameService
            ->expects('leave')
            ->with($multiplayerGame, $user)
            ->andReturns($multiplayerGame)
        ;

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
        // Create a more compatible mock object
        $multiplayerGame = $this->createMockGame(1, 'Test Game', 'registration');

        $startedGame = $this->createMockGame(1, 'Test Game', 'in_progress');

        $league = Mockery::mock(League::class);
        $league->allows('getAttribute')->with('id')->andReturns(1);

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
        $multiplayerGame = $this->createMockGame(1, 'Test Game', 'registration');

        $league = Mockery::mock(League::class);
        $league->allows('getAttribute')->with('id')->andReturns(1);

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
        $user = Mockery::mock(User::class);
        $user->allows('getAttribute')->with('id')->andReturns(1);

        $moderatorUser = Mockery::mock(User::class);
        $moderatorUser->allows('getAttribute')->with('id')->andReturns(2);

        // Create a better mock
        $multiplayerGame = $this->createMockGame(1, 'Test Game', 'in_progress');

        // Updated game with moderator
        $updatedGame = $this->createMockGame(1, 'Test Game', 'in_progress', [
            'moderator_user_id' => 2,
        ]);

        $league = Mockery::mock(League::class);
        $league->allows('getAttribute')->with('id')->andReturns(1);

        $this->mockAuth
            ->expects('user')
            ->andReturns($user)
        ;

        $request = Mockery::mock(Request::class);
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
        $user = Mockery::mock(User::class);
        $user->allows('getAttribute')->with('id')->andReturns(1);

        $targetUser = Mockery::mock(User::class);
        $targetUser->allows('getAttribute')->with('id')->andReturns(2);

        // Better MultiplayerGame mock
        $multiplayerGame = $this->createMockGame(1, 'Test Game', 'in_progress');

        $league = Mockery::mock(League::class);
        $league->allows('getAttribute')->with('id')->andReturns(1);

        $this->mockAuth
            ->expects('user')
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
        $user = Mockery::mock(User::class);
        $user->allows('getAttribute')->with('id')->andReturns(1);

        $multiplayerGame = $this->createMockGame(1, 'Test Game', 'completed');

        $league = Mockery::mock(League::class);
        $league->allows('getAttribute')->with('id')->andReturns(1);

        $this->mockAuth
            ->expects('user')
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

    /**
     * @throws JsonException
     */
    #[Test] public function it_gets_financial_summary(): void
    {
        // Arrange
        $multiplayerGame = $this->createMockGame(1, 'Test Game', 'completed');

        $league = Mockery::mock(League::class);
        $league->allows('getAttribute')->with('id')->andReturns(1);

        $financialSummary = [
            'entrance_fee'          => 300,
            'total_prize_pool'      => 1800,
            'first_place_prize'     => 1080,
            'second_place_prize'    => 360,
            'grand_final_fund'      => 360,
            'penalty_fee'           => 50,
            'penalty_players_count' => 3,
            'time_fund_total'       => 150,
        ];

        $this->mockMultiplayerGameService
            ->expects('getFinancialSummary')
            ->with($multiplayerGame)
            ->andReturns($financialSummary)
        ;

        // Act
        $result = $this->controller->getFinancialSummary($league, $multiplayerGame);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());
        $this->assertEquals($financialSummary, json_decode($result->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * @throws JsonException
     */
    #[Test] public function it_gets_rating_summary(): void
    {
        // Arrange
        $multiplayerGame = $this->createMockGame(1, 'Test Game', 'completed');

        $league = Mockery::mock(League::class);
        $league->allows('getAttribute')->with('id')->andReturns(1);

        $ratingSummary = [
            'players'       => [
                [
                    'player_id'       => 1,
                    'user'            => ['id' => 1, 'name' => 'Player 1'],
                    'finish_position' => 1,
                    'rating_points' => 50,
                ],
                [
                    'player_id'       => 2,
                    'user'            => ['id' => 2, 'name' => 'Player 2'],
                    'finish_position' => 2,
                    'rating_points' => 40,
                ],
            ],
            'total_players' => 2,
        ];

        $this->mockMultiplayerGameService
            ->expects('getRatingSummary')
            ->with($multiplayerGame)
            ->andReturns($ratingSummary)
        ;

        // Act
        $result = $this->controller->getRatingSummary($league, $multiplayerGame);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());
        $this->assertEquals($ratingSummary, json_decode($result->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }
}
