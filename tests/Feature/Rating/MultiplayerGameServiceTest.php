<?php

use App\Core\Models\Game;
use App\Core\Models\User;
use App\Leagues\Enums\RatingType;
use App\Leagues\Models\League;
use App\Leagues\Models\Rating;
use App\Leagues\Services\RatingService;
use App\Matches\Models\MultiplayerGame;
use App\Matches\Models\MultiplayerGamePlayer;
use App\Matches\Services\MultiplayerGameService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->mockRatingService = $this->mock(RatingService::class);
    $this->service = new MultiplayerGameService();
});

it('can create a multiplayer game with valid input', function () {
    // Create a league with KillerPool rating type
    $game = Game::factory()->create(['is_multiplayer' => true]);
    $league = League::factory()->create([
        'game_id'     => $game->id,
        'rating_type' => RatingType::KillerPool,
    ]);

    // Create game input data
    $gameData = [
        'name'                   => 'Test Multiplayer Game',
        'max_players'            => 10,
        'allow_player_targeting' => true,
        'entrance_fee'           => 300,
        'first_place_percent'    => 60,
        'second_place_percent'   => 20,
        'grand_final_percent'    => 20,
        'penalty_fee'            => 50,
    ];

    // Create the game
    $multiplayerGame = $this->service->create($league, $gameData);

    expect($multiplayerGame)->not
        ->toBeNull()
        ->and($multiplayerGame->name)->toBe('Test Multiplayer Game')
        ->and($multiplayerGame->max_players)->toBe(10)
        ->and($multiplayerGame->allow_player_targeting)->toBeTrue()
        ->and($multiplayerGame->entrance_fee)->toBe(300)
        ->and($multiplayerGame->first_place_percent)->toBe(60)
        ->and($multiplayerGame->second_place_percent)->toBe(20)
        ->and($multiplayerGame->grand_final_percent)->toBe(20)
        ->and($multiplayerGame->penalty_fee)->toBe(50)
        ->and($multiplayerGame->status)->toBe('registration')
    ;
});

it('can join a multiplayer game with active league membership', function () {
    // Create a league with a game
    $game = Game::factory()->create(['is_multiplayer' => true]);
    $league = League::factory()->create([
        'game_id'     => $game->id,
        'rating_type' => RatingType::KillerPool,
    ]);

    // Create a user with an active rating
    $user = User::factory()->create();
    Rating::create([
        'league_id'    => $league->id,
        'user_id'      => $user->id,
        'rating'       => 1000,
        'position'     => 1,
        'is_active'    => true,
        'is_confirmed' => true,
    ]);

    // Create a multiplayer game
    $multiplayerGame = MultiplayerGame::create([
        'league_id' => $league->id,
        'game_id'   => $game->id,
        'name'      => 'Test Game',
        'status'    => 'registration',
    ]);

    // Join the game
    $updatedGame = $this->service->join($league, $multiplayerGame, $user);

    expect($updatedGame->players()->count())
        ->toBe(1)
        ->and($updatedGame->players()->first()->user_id)->toBe($user->id)
    ;
});

it('prevents joining a multiplayer game without league membership', function () {
    // Create a league with a game
    $game = Game::factory()->create(['is_multiplayer' => true]);
    $league = League::factory()->create([
        'game_id'     => $game->id,
        'rating_type' => RatingType::KillerPool,
    ]);

    // Create a user with no rating in the league
    $user = User::factory()->create();

    // Create a multiplayer game
    $multiplayerGame = MultiplayerGame::create([
        'league_id' => $league->id,
        'game_id'   => $game->id,
        'name'      => 'Test Game',
        'status'    => 'registration',
    ]);

    // Attempt to join the game should throw exception
    expect(fn() => $this->service->join($league, $multiplayerGame, $user))
        ->toThrow(RuntimeException::class, 'You must be an active player in this league to join.')
    ;
});

it('can leave a multiplayer game during registration', function () {
    // Create a league with a game
    $game = Game::factory()->create(['is_multiplayer' => true]);
    $league = League::factory()->create([
        'game_id'     => $game->id,
        'rating_type' => RatingType::KillerPool,
    ]);

    // Create users
    $user = User::factory()->create();

    // Create a multiplayer game
    $multiplayerGame = MultiplayerGame::create([
        'league_id' => $league->id,
        'game_id'   => $game->id,
        'name'      => 'Test Game',
        'status'    => 'registration',
    ]);

    // Add the player
    $multiplayerGame->players()->create([
        'user_id'   => $user->id,
        'joined_at' => now(),
    ]);

    // Leave the game
    $updatedGame = $this->service->leave($multiplayerGame, $user);

    expect($updatedGame->players()->count())->toBe(0);
});

it('prevents leaving a multiplayer game that has already started', function () {
    // Create a league with a game
    $game = Game::factory()->create(['is_multiplayer' => true]);
    $league = League::factory()->create([
        'game_id'     => $game->id,
        'rating_type' => RatingType::KillerPool,
    ]);

    // Create users
    $user = User::factory()->create();

    // Create a multiplayer game that has already started
    $multiplayerGame = MultiplayerGame::create([
        'league_id'  => $league->id,
        'game_id'    => $game->id,
        'name'       => 'Test Game',
        'status'     => 'in_progress',
        'started_at' => now(),
    ]);

    // Add the player
    $multiplayerGame->players()->create([
        'user_id'   => $user->id,
        'joined_at' => now(),
    ]);

    // Attempt to leave the game should throw exception
    expect(fn() => $this->service->leave($multiplayerGame, $user))
        ->toThrow(RuntimeException::class, 'Cannot leave a game that has already started.')
    ;
});

it('can start a multiplayer game with at least 2 players', function () {
    // Create a league with a game
    $game = Game::factory()->create(['is_multiplayer' => true]);
    $league = League::factory()->create([
        'game_id'     => $game->id,
        'rating_type' => RatingType::KillerPool,
    ]);

    // Create users
    $users = User::factory()->count(3)->create();

    // Create a multiplayer game
    $multiplayerGame = MultiplayerGame::create([
        'league_id' => $league->id,
        'game_id'   => $game->id,
        'name'      => 'Test Game',
        'status'    => 'registration',
    ]);

    // Add players
    foreach ($users as $user) {
        $multiplayerGame->players()->create([
            'user_id'   => $user->id,
            'joined_at' => now(),
        ]);
    }

    // Start the game
    $startedGame = $this->service->start($multiplayerGame);

    expect($startedGame->status)
        ->toBe('in_progress')
        ->and($startedGame->started_at)->not
        ->toBeNull()
        ->and($startedGame->moderator_user_id)->not
        ->toBeNull()
        ->and($startedGame->initial_lives)->toBeGreaterThan(0)
        ->and($startedGame->players()->count())->toBe(3)
    ;

    // Verify players have been updated with lives and turn order
    $players = $startedGame->players()->get();

    foreach ($players as $player) {
        expect($player->lives)
            ->toBe($startedGame->initial_lives)
            ->and($player->turn_order)->toBeGreaterThan(0)
            ->and($player->turn_order)->toBeLessThanOrEqual(3)
            ->and($player->cards)->toBeArray()
            ->and($player->cards)->toHaveKeys(['skip_turn', 'pass_turn', 'hand_shot'])
        ;
    }
});

it('prevents starting a multiplayer game with fewer than 2 players by throwing an exception', function () {
    // Arrange: Создаем лигу с игрой
    $gameDefinition = Game::factory()->create(['is_multiplayer' => true]);
    $league = League::factory()->create([
        'game_id' => $gameDefinition->id,
        'rating_type' => RatingType::KillerPool,
    ]);

    // Arrange: Создаем многопользовательскую игру с начальным статусом 'registration'
    $multiplayerGame = MultiplayerGame::create([
        'league_id' => $league->id,
        'game_id' => $gameDefinition->id,
        'name'      => 'Test Game',
        'status'    => 'registration',
    ]);

    // Arrange: Добавляем только одного игрока
    $multiplayerGame->players()->create([
        'user_id'   => User::factory()->create()->id,
        'joined_at' => now(),
    ]);

    // Act & Assert: Ожидаем, что будет выброшено RuntimeException с конкретным сообщением
    expect(fn() => $this->service->start($multiplayerGame))
        ->toThrow(RuntimeException::class, 'Unable to start the game.')
    ;

    // Assert (Optional): Дополнительно можно проверить, что статус игры не изменился.
    // Для этого нужно перезагрузить модель из базы данных.
    $multiplayerGame->refresh();
    expect($multiplayerGame->status)->toBe('registration');
});

it('can correctly apply player elimination and identify the winner', function () {
    // Create a league with a game
    $game = Game::factory()->create(['is_multiplayer' => true]);
    $league = League::factory()->create([
        'game_id'     => $game->id,
        'rating_type' => RatingType::KillerPool,
    ]);

    // Create users
    $users = User::factory()->count(2)->create(); // Just 2 players for simplicity

    // Create a multiplayer game that's in progress
    $multiplayerGame = MultiplayerGame::create([
        'league_id'     => $league->id,
        'game_id'       => $game->id,
        'name'          => 'Test Game',
        'status'        => 'in_progress',
        'started_at'    => now(),
        'initial_lives' => 3,
    ]);

    // Add players
    $player1 = $multiplayerGame->players()->create([
        'user_id'    => $users[0]->id,
        'joined_at'  => now(),
        'lives'      => 3,
        'turn_order' => 1,
    ]);

    $player2 = $multiplayerGame->players()->create([
        'user_id'    => $users[1]->id,
        'joined_at'  => now(),
        'lives'      => 1, // About to be eliminated
        'turn_order' => 2,
    ]);

    // Eliminate player2
    $this->service->decrementPlayerLives($player2);

    // Reload the players and game
    $player1 = MultiplayerGamePlayer::find($player1->id);
    $player2 = MultiplayerGamePlayer::find($player2->id);
    $multiplayerGame = MultiplayerGame::find($multiplayerGame->id);

    // Verify player2 was eliminated
    expect($player2->lives)
        ->toBe(0)
        ->and($player2->eliminated_at)->not
        ->toBeNull()
        ->and($player2->finish_position)->toBe(2)
        ->and($player1->finish_position)->toBe(1)
        ->and($player1->eliminated_at)->not
        ->toBeNull()
        ->and($multiplayerGame->status)->toBe('completed')
        ->and($multiplayerGame->completed_at)->not->toBeNull();

    // Verify player1 is the winner

    // Verify game was completed
});

it('correctly calculates prizes and rating points', function () {
    // Create a league with a game
    $game = Game::factory()->create(['is_multiplayer' => true]);
    $league = League::factory()->create([
        'game_id'     => $game->id,
        'rating_type' => RatingType::KillerPool,
    ]);

    // Create users
    $users = User::factory()->count(5)->create();

    // Create a multiplayer game that's in progress
    $multiplayerGame = MultiplayerGame::create([
        'league_id'            => $league->id,
        'game_id'              => $game->id,
        'name'                 => 'Test Game',
        'status'               => 'in_progress',
        'started_at'           => now(),
        'initial_lives'        => 3,
        'entrance_fee'         => 100,
        'first_place_percent'  => 60,
        'second_place_percent' => 20,
        'grand_final_percent'  => 20,
        'penalty_fee'          => 50,
    ]);

    // Add players with finish positions in reverse order of user creation
    foreach ($users as $i => $user) {
        MultiplayerGamePlayer::create([
            'multiplayer_game_id' => $multiplayerGame->id,
            'user_id'             => $user->id,
            'joined_at'           => now(),
            'lives'               => 0, // All eliminated
            'eliminated_at'       => now(),
            'finish_position'     => $i + 1, // 1 to 5
        ]);
    }

    // Complete the game
    $multiplayerGame->update([
        'status'       => 'completed',
        'completed_at' => now(),
    ]);

    // Calculate prizes
    $this->service->calculatePrizes($multiplayerGame);

    // Calculate rating points
    $this->service->calculateRatingPoints($multiplayerGame);

    // Reload the game
    $multiplayerGame = MultiplayerGame::find($multiplayerGame->id);

    // Verify prize pool data
    expect($multiplayerGame->prize_pool)
        ->toBeArray()
        ->and($multiplayerGame->prize_pool['total'])->toBe(500) // 5 players * 100
        ->and($multiplayerGame->prize_pool['first_place'])->toBe(300) // 60%
        ->and($multiplayerGame->prize_pool['second_place'])->toBe(100) // 20%
        ->and($multiplayerGame->prize_pool['grand_final_fund'])->toBe(100)
    ; // 20%

    // Verify player prizes and rating points
    $players = $multiplayerGame->players()->orderBy('finish_position')->get();

    expect($players[0]->prize_amount)
        ->toBe(300) // 1st place
        ->and($players[0]->rating_points)->toBe(5)
        ->and($players[1]->prize_amount)
        ->toBe(100) // 2nd place
        ->and($players[1]->rating_points)->toBe(4)
    ; // 5th from bottom

    // 4th from bottom

    // 3rd and beyond get no prizes
    for ($i = 2; $i < 5; $i++) {
        expect($players[$i]->prize_amount)
            ->toBe(0)
            ->and($players[$i]->rating_points)->toBe(5 - $i)
        ;
    }

    // Verify penalty fees for bottom half of players
    expect($players[3]->penalty_paid)
        ->toBeTrue()
        ->and($players[4]->penalty_paid)->toBeTrue()
    ;
});

it('applies rating points to league ratings', function () {
    // Create a league with KillerPool rating type
    $game = Game::factory()->create(['is_multiplayer' => true]);
    $league = League::factory()->create([
        'game_id'     => $game->id,
        'rating_type' => RatingType::KillerPool,
    ]);

    // Create users with ratings
    $users = User::factory()->count(3)->create();

    foreach ($users as $user) {
        Rating::create([
            'league_id'    => $league->id,
            'user_id'      => $user->id,
            'rating'       => 1000,
            'position'     => 1,
            'is_active'    => true,
            'is_confirmed' => true,
        ]);
    }

    // Create a completed multiplayer game
    $multiplayerGame = MultiplayerGame::create([
        'league_id'    => $league->id,
        'game_id'      => $game->id,
        'name'         => 'Test Game',
        'status'       => 'completed',
        'completed_at' => now(),
    ]);

    // Add players with positions and rating points
    foreach ($users as $i => $user) {
        MultiplayerGamePlayer::create([
            'multiplayer_game_id' => $multiplayerGame->id,
            'user_id'             => $user->id,
            'joined_at'           => now(),
            'eliminated_at'       => now(),
            'finish_position'     => $i + 1,
            'rating_points'       => 30 - ($i * 10), // 30, 20, 10
        ]);
    }

    // Mock the RatingService for applyRatingPointsForMultiplayerGame
    $this->mockRatingService
        ->expects('applyRatingPointsForMultiplayerGame')
        ->with($multiplayerGame)
    ;

    // Swap in mocked service
    $this->app->instance(RatingService::class, $this->mockRatingService);

    // Create service with dependency injection
    $service = $this->app->make(MultiplayerGameService::class);

    // Apply rating points
    $service->applyRatingPointsToLeague($multiplayerGame);
});

it('can finish a game and apply ratings', function () {
    // Create a league with KillerPool rating type
    $game = Game::factory()->create(['is_multiplayer' => true]);
    $league = League::factory()->create([
        'game_id'     => $game->id,
        'rating_type' => RatingType::KillerPool,
    ]);

    // Create users with ratings
    $users = User::factory()->count(3)->create();

    foreach ($users as $user) {
        Rating::create([
            'league_id'    => $league->id,
            'user_id'      => $user->id,
            'rating'       => 1000,
            'position'     => 1,
            'is_active'    => true,
            'is_confirmed' => true,
        ]);
    }

    // Create a completed multiplayer game
    $multiplayerGame = MultiplayerGame::create([
        'league_id'         => $league->id,
        'game_id'           => $game->id,
        'name'              => 'Test Game',
        'status'            => 'completed',
        'completed_at'      => now(),
        'moderator_user_id' => $users[0]->id,
    ]);

    // Add players with positions and rating points
    foreach ($users as $i => $user) {
        MultiplayerGamePlayer::create([
            'multiplayer_game_id' => $multiplayerGame->id,
            'user_id'             => $user->id,
            'joined_at'           => now(),
            'eliminated_at'       => now(),
            'finish_position'     => $i + 1,
            'rating_points'       => 30 - ($i * 10), // 30, 20, 10
        ]);
    }

    // Mock the RatingService for applyRatingPointsForMultiplayerGame
    $this->mockRatingService
        ->expects('applyRatingPointsForMultiplayerGame')
        ->with($multiplayerGame)
    ;

    // Swap in mocked service
    $this->app->instance(RatingService::class, $this->mockRatingService);

    // Create service with dependency injection
    $service = $this->app->make(MultiplayerGameService::class);

    // Finish the game
    $service->finishGame($multiplayerGame, $users[0]); // Moderator

    // Reload the game
    $multiplayerGame = MultiplayerGame::find($multiplayerGame->id);

    // Verify game was marked as finished
    expect($multiplayerGame->status)->toBe('finished');
});
