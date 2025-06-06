<?php

use App\Core\Models\Game;
use App\Core\Models\User;
use App\Leagues\Models\League;
use App\Leagues\Models\Rating;
use App\Leagues\Services\MatchGamesService;
use App\Leagues\Services\RatingService;
use App\Matches\Enums\GameStatus;
use App\Matches\Models\MatchGame;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->mockRatingService = $this->mock(RatingService::class);
    $this->service = new MatchGamesService($this->mockRatingService);
});

it('can send a match game with valid input', function () {
    // Create a league
    $league = League::factory()->create();

    // Create sender and receiver
    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    // Create ratings for both users
    $senderRating = Rating::create([
        'league_id'    => $league->id,
        'user_id'      => $sender->id,
        'rating'       => 1000,
        'position'     => 1,
        'is_active'    => true,
        'is_confirmed' => true,
    ]);

    $receiverRating = Rating::create([
        'league_id'    => $league->id,
        'user_id'      => $receiver->id,
        'rating'       => 1100,
        'position'     => 2,
        'is_active'    => true,
        'is_confirmed' => true,
    ]);

    // Mock the getActiveRatingForUserLeague method
    $this->mockRatingService
        ->expects('getActiveRatingForUserLeague')
        ->with($sender, $league)
        ->andReturns($senderRating)
    ;

    $this->mockRatingService
        ->expects('getActiveRatingForUserLeague')
        ->with($receiver, $league)
        ->andReturns($receiverRating)
    ;

    // Create DTO
    $dto = new App\Leagues\DataTransferObjects\SendGameDTO([
        'sender'     => $sender,
        'receiver'   => $receiver,
        'league'     => $league,
        'stream_url' => 'https://example.com/stream',
        'details'    => 'Test match',
    ]);

    // Send the match
    $result = $this->service->send($dto);

    expect($result)->toBeTrue();

    // Verify match was created
    $match = MatchGame::where('first_rating_id', $senderRating->id)
        ->where('second_rating_id', $receiverRating->id)
        ->first()
    ;

    expect($match)->not
        ->toBeNull()
        ->and($match->stream_url)->toBe('https://example.com/stream')
        ->and($match->details)->toBe('Test match')
        ->and($match->status)->toBe(GameStatus::IN_PROGRESS)
    ;
});

it('handles validation for rating criteria when sending match', function () {
    // Create a league
    $league = League::factory()->create();

    // Create sender and receiver
    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    // Test 1: When one rating is not active or not confirmed
    $senderRating = Rating::create([
        'league_id'    => $league->id,
        'user_id'      => $sender->id,
        'rating'       => 1000,
        'position'     => 1,
        'is_active'    => true,
        'is_confirmed' => true,
    ]);

    $receiverRating = Rating::create([
        'league_id'    => $league->id,
        'user_id'      => $receiver->id,
        'rating'       => 1100,
        'position'     => 2,
        'is_active'    => true,
        'is_confirmed' => false, // Not confirmed
    ]);

    // Mock the getActiveRatingForUserLeague method
    $this->mockRatingService
        ->allows('getActiveRatingForUserLeague')
        ->with($sender, $league)
        ->andReturns($senderRating)
    ;

    $this->mockRatingService
        ->allows('getActiveRatingForUserLeague')
        ->with($receiver, $league)
        ->andReturns($receiverRating)
    ;

    // Create DTO
    $dto = new App\Leagues\DataTransferObjects\SendGameDTO([
        'sender'   => $sender,
        'receiver' => $receiver,
        'league'   => $league,
    ]);

    // Send should fail
    expect($this->service->send($dto))->toBeFalse();

    // Test 2: When ratings have a position difference > 10
    $receiverRating->update([
        'is_confirmed' => true,
        'position'     => 15, // Far away from sender's position 1
    ]);

    expect($this->service->send($dto))->toBeFalse();
});

it('prevents sending match if player already has ongoing matches', function () {
    // Create a league
    $league = League::factory()->create();

    // Create sender and receiver
    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    // Create ratings for both users
    $senderRating = Rating::create([
        'league_id'    => $league->id,
        'user_id'      => $sender->id,
        'rating'       => 1000,
        'position'     => 1,
        'is_active'    => true,
        'is_confirmed' => true,
    ]);

    $receiverRating = Rating::create([
        'league_id'    => $league->id,
        'user_id'      => $receiver->id,
        'rating'       => 1100,
        'position'     => 2,
        'is_active'    => true,
        'is_confirmed' => true,
    ]);

    // Create an ongoing match for the sender
    MatchGame::create([
        'game_id'            => Game::factory()->create()->id,
        'league_id'          => $league->id,
        'first_rating_id'    => $senderRating->id,
        'second_rating_id'   => $receiverRating->id,
        'status'             => GameStatus::IN_PROGRESS,
        'invitation_sent_at' => now(),
    ]);

    // Mock the getActiveRatingForUserLeague method
    $this->mockRatingService
        ->allows('getActiveRatingForUserLeague')
        ->with($sender, $league)
        ->andReturns($senderRating)
    ;

    $this->mockRatingService
        ->allows('getActiveRatingForUserLeague')
        ->with($receiver, $league)
        ->andReturns($receiverRating)
    ;

    // Create DTO
    $dto = new App\Leagues\DataTransferObjects\SendGameDTO([
        'sender'   => $sender,
        'receiver' => $receiver,
        'league'   => $league,
    ]);

    // Send should fail because sender already has an ongoing match
    expect($this->service->send($dto))->toBeFalse();
});

it('prevents sending match if last opponent was the same', function () {
    // Create a league
    $league = League::factory()->create();

    // Create sender and receiver
    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    // Create ratings for both users
    $senderRating = Rating::create([
        'league_id'    => $league->id,
        'user_id'      => $sender->id,
        'rating'       => 1000,
        'position'     => 1,
        'is_active'    => true,
        'is_confirmed' => true,
    ]);

    $receiverRating = Rating::create([
        'league_id'    => $league->id,
        'user_id'      => $receiver->id,
        'rating'       => 1100,
        'position'     => 2,
        'is_active'    => true,
        'is_confirmed' => true,
    ]);

    // Create a completed match between the same players
    MatchGame::create([
        'game_id'            => Game::factory()->create()->id,
        'league_id'          => $league->id,
        'first_rating_id'    => $senderRating->id,
        'second_rating_id'   => $receiverRating->id,
        'status'             => GameStatus::COMPLETED,
        'winner_rating_id'   => $senderRating->id,
        'loser_rating_id'    => $receiverRating->id,
        'invitation_sent_at' => now(),
        'finished_at'        => now(),
    ]);

    // Mock the getActiveRatingForUserLeague method
    $this->mockRatingService
        ->allows('getActiveRatingForUserLeague')
        ->with($sender, $league)
        ->andReturns($senderRating)
    ;

    $this->mockRatingService
        ->allows('getActiveRatingForUserLeague')
        ->with($receiver, $league)
        ->andReturns($receiverRating)
    ;

    // Create DTO
    $dto = new App\Leagues\DataTransferObjects\SendGameDTO([
        'sender'   => $sender,
        'receiver' => $receiver,
        'league'   => $league,
    ]);

    // Send should fail because last opponent was the same
    expect($this->service->send($dto))->toBeFalse();
});

it('can accept matches with valid input', function () {
    // Create a league
    $league = League::factory()->create();

    // Create sender and receiver
    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    // Create ratings for both users
    $senderRating = Rating::create([
        'league_id'    => $league->id,
        'user_id'      => $sender->id,
        'rating'       => 1000,
        'position'     => 1,
        'is_active'    => true,
        'is_confirmed' => true,
    ]);

    $receiverRating = Rating::create([
        'league_id'    => $league->id,
        'user_id'      => $receiver->id,
        'rating'       => 1100,
        'position'     => 2,
        'is_active'    => true,
        'is_confirmed' => true,
    ]);

    // Create a pending match
    $match = MatchGame::create([
        'game_id'            => Game::factory()->create()->id,
        'league_id'          => $league->id,
        'first_rating_id'    => $senderRating->id,
        'second_rating_id'   => $receiverRating->id,
        'status'             => GameStatus::PENDING,
        'invitation_sent_at' => now(),
    ]);

    // Mock the getActiveRatingForUserLeague method for checking access
    $this->mockRatingService
        ->expects('getActiveRatingForUserLeague')
        ->with($receiver, $match->league)
        ->andReturns($receiverRating)
    ;

    // Accept should succeed when receiver is accepting
    expect($this->service->accept($receiver, $match))->toBeTrue();

    // Reload match
    $match = MatchGame::find($match->id);

    expect($match->status)
        ->toBe(GameStatus::IN_PROGRESS)
        ->and($match->invitation_accepted_at)->not
        ->toBeNull()
    ;
});

it('prevents accepting if not the receiver or match not in pending status', function () {
    // Create a league
    $league = League::factory()->create();

    // Create sender and receiver
    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    // Create ratings for both users
    $senderRating = Rating::create([
        'league_id'    => $league->id,
        'user_id'      => $sender->id,
        'rating'       => 1000,
        'position'     => 1,
        'is_active'    => true,
        'is_confirmed' => true,
    ]);

    $receiverRating = Rating::create([
        'league_id'    => $league->id,
        'user_id'      => $receiver->id,
        'rating'       => 1100,
        'position'     => 2,
        'is_active'    => true,
        'is_confirmed' => true,
    ]);

    // Create a pending match
    $match = MatchGame::create([
        'game_id'            => Game::factory()->create()->id,
        'league_id'          => $league->id,
        'first_rating_id'    => $senderRating->id,
        'second_rating_id'   => $receiverRating->id,
        'status'             => GameStatus::PENDING,
        'invitation_sent_at' => now(),
    ]);

    // Test 1: Sender cannot accept their own match
    $this->mockRatingService
        ->allows('getActiveRatingForUserLeague')
        ->with($sender, $match->league)
        ->andReturns($senderRating)
    ;

    expect($this->service->accept($sender, $match))->toBeFalse();

    // Test 2: Cannot accept a match that's not in PENDING status
    $match->update(['status' => GameStatus::IN_PROGRESS]);

    $this->mockRatingService
        ->allows('getActiveRatingForUserLeague')
        ->with($receiver, $match->league)
        ->andReturns($receiverRating)
    ;

    expect($this->service->accept($receiver, $match))->toBeFalse();
});

it('can send match results with valid input', function () {
    // Create a league
    $league = League::factory()->create([
        'max_score' => 7,
    ]);

    // Create sender and receiver
    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    // Create ratings for both users
    $senderRating = Rating::create([
        'league_id'    => $league->id,
        'user_id'      => $sender->id,
        'rating'       => 1000,
        'position'     => 1,
        'is_active'    => true,
        'is_confirmed' => true,
    ]);

    $receiverRating = Rating::create([
        'league_id'    => $league->id,
        'user_id'      => $receiver->id,
        'rating'       => 1100,
        'position'     => 2,
        'is_active'    => true,
        'is_confirmed' => true,
    ]);

    // Create an in-progress match
    $match = MatchGame::create([
        'game_id'            => Game::factory()->create(['is_multiplayer' => false])->id,
        'league_id'          => $league->id,
        'first_rating_id'    => $senderRating->id,
        'second_rating_id'   => $receiverRating->id,
        'status'             => GameStatus::IN_PROGRESS,
        'invitation_sent_at' => now(),
    ]);

    // Mock the getActiveRatingForUserLeague method for haveAccessToGame
    $this->mockRatingService
        ->allows('getActiveRatingForUserLeague')
        ->andReturns($senderRating)
    ;

    // Create result DTO (first user wins)
    $resultDTO = new App\Leagues\DataTransferObjects\SendResultDTO([
        'first_user_score'  => 5,
        'second_user_score' => 3,
        'matchGame'         => $match,
    ]);

    // Send result
    expect($this->service->sendResult($sender, $resultDTO))->toBeTrue();

    // Reload match
    $match = MatchGame::find($match->id);

    expect($match->status)
        ->toBe(GameStatus::MUST_BE_CONFIRMED)
        ->and($match->first_user_score)->toBe(5)
        ->and($match->second_user_score)->toBe(3)
        ->and($match->winner_rating_id)->toBe($senderRating->id)
        ->and($match->loser_rating_id)->toBe($receiverRating->id)
        ->and($match->result_confirmed)->toBeArray()
        ->and(count($match->result_confirmed))->toBe(1)
        ->and($match->result_confirmed[0]['key'])->toBe($senderRating->id)
        ->and($match->result_confirmed[0]['score'])->toBe('5-3')
    ;
});

it('completes match when both players confirm the same result', function () {
    // Create a league
    $league = League::factory()->create();

    // Create sender and receiver
    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    // Create ratings for both users
    $senderRating = Rating::create([
        'league_id'    => $league->id,
        'user_id'      => $sender->id,
        'rating'       => 1000,
        'position'     => 1,
        'is_active'    => true,
        'is_confirmed' => true,
    ]);

    $receiverRating = Rating::create([
        'league_id'    => $league->id,
        'user_id'      => $receiver->id,
        'rating'       => 1100,
        'position'     => 2,
        'is_active'    => true,
        'is_confirmed' => true,
    ]);

    // Create a match that's already in MUST_BE_CONFIRMED status with sender's result
    $match = MatchGame::create([
        'game_id'            => Game::factory()->create(['is_multiplayer' => false])->id,
        'league_id'          => $league->id,
        'first_rating_id'    => $senderRating->id,
        'second_rating_id'   => $receiverRating->id,
        'first_user_score'   => 5,
        'second_user_score'  => 3,
        'winner_rating_id'   => $senderRating->id,
        'loser_rating_id'    => $receiverRating->id,
        'status'             => GameStatus::MUST_BE_CONFIRMED,
        'invitation_sent_at' => now(),
        'result_confirmed'   => [
            [
                'key'   => $senderRating->id,
                'score' => '5-3',
            ],
        ],
    ]);

    // Mock the getActiveRatingForUserLeague method for haveAccessToGame
    $this->mockRatingService
        ->allows('getActiveRatingForUserLeague')
        ->andReturns($receiverRating)
    ;

    // Mock the updateRatings method
    $this->mockRatingService
        ->expects('updateRatings')
        ->andReturns([
            $senderRating->id   => 1020,
            $receiverRating->id => 1080,
        ])
    ;

    // Create the same result DTO from receiver
    $resultDTO = new App\Leagues\DataTransferObjects\SendResultDTO([
        'first_user_score'  => 5,
        'second_user_score' => 3,
        'matchGame'         => $match,
    ]);

    // Send result
    $result = $this->service->sendResult($receiver, $resultDTO);
    expect($result)->toBeTrue();

    // Reload match
    $match = MatchGame::find($match->id);

    // Verify the match has the status COMPLETED and other expected properties
    expect($match->status)->toBe(GameStatus::COMPLETED);
});

it('handles conflicting match results', function () {
    // Create a league
    $league = League::factory()->create();

    // Create sender and receiver
    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    // Create ratings for both users
    $senderRating = Rating::create([
        'league_id'    => $league->id,
        'user_id'      => $sender->id,
        'rating'       => 1000,
        'position'     => 1,
        'is_active'    => true,
        'is_confirmed' => true,
    ]);

    $receiverRating = Rating::create([
        'league_id'    => $league->id,
        'user_id'      => $receiver->id,
        'rating'       => 1100,
        'position'     => 2,
        'is_active'    => true,
        'is_confirmed' => true,
    ]);

    // Create a match that's already in MUST_BE_CONFIRMED status with sender's result
    $match = MatchGame::create([
        'game_id'            => Game::factory()->create(['is_multiplayer' => false])->id,
        'league_id'          => $league->id,
        'first_rating_id'    => $senderRating->id,
        'second_rating_id'   => $receiverRating->id,
        'first_user_score'   => 5,
        'second_user_score'  => 3,
        'winner_rating_id'   => $senderRating->id,
        'loser_rating_id'    => $receiverRating->id,
        'status'             => GameStatus::MUST_BE_CONFIRMED,
        'invitation_sent_at' => now(),
        'result_confirmed'   => [
            [
                'key'   => $senderRating->id,
                'score' => '5-3',
            ],
        ],
    ]);

    // Mock the getActiveRatingForUserLeague method for haveAccessToGame
    $this->mockRatingService
        ->allows('getActiveRatingForUserLeague')
        ->andReturns($receiverRating)
    ;

    // Create a different result DTO from receiver
    $resultDTO = new App\Leagues\DataTransferObjects\SendResultDTO([
        'first_user_score'  => 3,
        'second_user_score' => 5, // Different result
        'matchGame'         => $match,
    ]);

    // Send result
    $result = $this->service->sendResult($receiver, $resultDTO);
    expect($result)->toBeTrue();

    // Reload match
    $match = MatchGame::find($match->id);

    // Verify it's still in MUST_BE_CONFIRMED state
    expect($match->status)
        ->toBe(GameStatus::MUST_BE_CONFIRMED)
        ->and($match->result_confirmed)->toBeArray()
        ->and(count($match->result_confirmed))->toBe(1)
        ->and($match->result_confirmed[0]['key'])->toBe($receiverRating->id)
        ->and($match->result_confirmed[0]['score'])->toBe('3-5')
    ;

    // Check that result_confirmed now contains only receiver's result
});
