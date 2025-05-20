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
     * @throws JsonException
     */
    #[Test] public function it_gets_all_multiplayer_games_for_league(): void
    {
        // Arrange
        $multiplayerGames = collect([
            (object) ['id' => 1, 'name' => 'Test Game 1', 'status' => 'registration'],
            (object) ['id' => 2, 'name' => 'Test Game 2', 'status' => 'in_progress'],
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

        $createdGame = (object) array_merge(
            [
                'id'        => 1,
                'league_id' => 1,
                'game_id'   => 1,
                'status'    => 'registration',
            ],
            $gameData,
        );

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
        $multiplayerGame = Mockery::mock(MultiplayerGame::class);
        $multiplayerGame->allows('getAttribute')->with('id')->andReturns(1);
        $multiplayerGame->allows('getAttribute')->with('name')->andReturns('Test Game');
        $multiplayerGame->allows('getAttribute')->with('status')->andReturns('in_progress');
        $multiplayerGame->allows('getAttribute')->with('game_id')->andReturns(1);
        $multiplayerGame->allows('load')->andReturnSelf();

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

        $multiplayerGame = Mockery::mock(MultiplayerGame::class);
        $multiplayerGame->allows('getAttribute')->with('id')->andReturns(1);
        $multiplayerGame->allows('getAttribute')->with('name')->andReturns('Test Game');
        $multiplayerGame->allows('getAttribute')->with('status')->andReturns('registration');

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

        $multiplayerGame = Mockery::mock(MultiplayerGame::class);
        $multiplayerGame->allows('getAttribute')->with('id')->andReturns(1);
        $multiplayerGame->allows('getAttribute')->with('name')->andReturns('Test Game');

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
        $multiplayerGame = Mockery::mock(MultiplayerGame::class);
        $multiplayerGame->allows('getAttribute')->with('id')->andReturns(1);

        $startedGame = Mockery::mock(MultiplayerGame::class);
        $startedGame->allows('getAttribute')->with('id')->andReturns(1);
        $startedGame->allows('getAttribute')->with('name')->andReturns('Test Game');
        $startedGame->allows('getAttribute')->with('status')->andReturns('in_progress');

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
        $multiplayerGame = Mockery::mock(MultiplayerGame::class);
        $multiplayerGame->allows('getAttribute')->with('id')->andReturns(1);

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

        $multiplayerGame = Mockery::mock(MultiplayerGame::class);
        $multiplayerGame->allows('getAttribute')->with('id')->andReturns(1);

        $updatedGame = Mockery::mock(MultiplayerGame::class);
        $updatedGame->allows('getAttribute')->with('id')->andReturns(1);
        $updatedGame->allows('getAttribute')->with('name')->andReturns('Test Game');
        $updatedGame->allows('getAttribute')->with('moderator_user_id')->andReturns($moderatorUser->id);

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

        $multiplayerGame = Mockery::mock(MultiplayerGame::class);
        $multiplayerGame->allows('getAttribute')->with('id')->andReturns(1);
        $multiplayerGame->allows('getAttribute')->with('name')->andReturns('Test Game');

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

        $multiplayerGame = Mockery::mock(MultiplayerGame::class);
        $multiplayerGame->allows('getAttribute')->with('id')->andReturns(1);
        $multiplayerGame->allows('getAttribute')->with('status')->andReturns('completed');

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
}
