<?php

use App\Core\Models\Game;
use App\Core\Models\User;
use App\Leagues\Models\League;
use App\Leagues\Models\Rating;
use App\Matches\Enums\GameStatus;
use App\Matches\Models\MatchGame;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('has the correct relationship with users', function () {
    $user = User::factory()->create();
    $league = League::factory()->create();

    $rating = Rating::create([
        'league_id' => $league->id,
        'user_id'   => $user->id,
        'rating'    => 1000,
        'position'  => 1,
        'is_active' => true,
    ]);

    expect($rating->user()->exists())
        ->toBeTrue()
        ->and($rating->user->id)->toBe($user->id)
    ;
});

it('has the correct relationship with leagues', function () {
    $user = User::factory()->create();
    $league = League::factory()->create();

    $rating = Rating::create([
        'league_id' => $league->id,
        'user_id'   => $user->id,
        'rating'    => 1000,
        'position'  => 1,
        'is_active' => true,
    ]);

    expect($rating->league()->exists())
        ->toBeTrue()
        ->and($rating->league->id)->toBe($league->id)
    ;
});

it('correctly tracks ongoing matches', function () {
    $user = User::factory()->create();
    $league = League::factory()->create();
    $game = Game::factory()->create();

    $rating = Rating::create([
        'league_id' => $league->id,
        'user_id'   => $user->id,
        'rating'    => 1000,
        'position'  => 1,
        'is_active' => true,
    ]);

    $otherRating = Rating::create([
        'league_id' => $league->id,
        'user_id'   => User::factory()->create()->id,
        'rating'    => 1100,
        'position'  => 2,
        'is_active' => true,
    ]);

    // Create a match in PENDING status
    MatchGame::create([
        'game_id'            => $game->id,
        'league_id'          => $league->id,
        'first_rating_id'    => $rating->id,
        'second_rating_id'   => $otherRating->id,
        'status'             => GameStatus::PENDING,
        'invitation_sent_at' => now(),
    ]);

    // Create a match in IN_PROGRESS status
    MatchGame::create([
        'game_id'            => $game->id,
        'league_id'          => $league->id,
        'first_rating_id'    => $rating->id,
        'second_rating_id'   => $otherRating->id,
        'status'             => GameStatus::IN_PROGRESS,
        'invitation_sent_at' => now(),
    ]);

    // Create a match in COMPLETED status (shouldn't be in ongoing matches)
    MatchGame::create([
        'game_id'            => $game->id,
        'league_id'          => $league->id,
        'first_rating_id'    => $rating->id,
        'second_rating_id'   => $otherRating->id,
        'status'             => GameStatus::COMPLETED,
        'invitation_sent_at' => now(),
    ]);

    // Test ongoingMatchesAsFirstPlayer
    expect($rating->ongoingMatchesAsFirstPlayer()->count())
        ->toBe(2)
        ->and($rating->ongoingMatchesAsFirstPlayer->pluck('status')->toArray())
        ->toContain(GameStatus::PENDING, GameStatus::IN_PROGRESS)
    ;

    // Create matches where rating is second player
    MatchGame::create([
        'game_id'            => $game->id,
        'league_id'          => $league->id,
        'first_rating_id'    => $otherRating->id,
        'second_rating_id'   => $rating->id,
        'status'             => GameStatus::MUST_BE_CONFIRMED,
        'invitation_sent_at' => now(),
    ]);

    // Test ongoingMatchesAsSecondPlayer
    expect($rating->ongoingMatchesAsSecondPlayer()->count())
        ->toBe(1)
        ->and($rating->ongoingMatchesAsSecondPlayer->pluck('status')->toArray())
        ->toContain(GameStatus::MUST_BE_CONFIRMED)
        ->and($rating->ongoingMatches()->count())->toBe(3)
    ;

    // Test ongoingMatches (combines both)
});

it('correctly counts wins and losses', function () {
    $user = User::factory()->create();
    $league = League::factory()->create();
    $game = Game::factory()->create();

    $rating = Rating::create([
        'league_id' => $league->id,
        'user_id'   => $user->id,
        'rating'    => 1000,
        'position'  => 1,
        'is_active' => true,
    ]);

    $otherRating = Rating::create([
        'league_id' => $league->id,
        'user_id'   => User::factory()->create()->id,
        'rating'    => 1100,
        'position'  => 2,
        'is_active' => true,
    ]);

    // Create matches where rating is winner
    MatchGame::create([
        'game_id'            => $game->id,
        'league_id'          => $league->id,
        'first_rating_id'    => $rating->id,
        'second_rating_id'   => $otherRating->id,
        'winner_rating_id'   => $rating->id,
        'loser_rating_id'    => $otherRating->id,
        'status'             => GameStatus::COMPLETED,
        'invitation_sent_at' => now(),
    ]);

    MatchGame::create([
        'game_id'            => $game->id,
        'league_id'          => $league->id,
        'first_rating_id'    => $otherRating->id,
        'second_rating_id'   => $rating->id,
        'winner_rating_id'   => $rating->id,
        'loser_rating_id'    => $otherRating->id,
        'status'             => GameStatus::COMPLETED,
        'invitation_sent_at' => now(),
    ]);

    // Create match where rating is loser
    MatchGame::create([
        'game_id'            => $game->id,
        'league_id'          => $league->id,
        'first_rating_id'    => $rating->id,
        'second_rating_id'   => $otherRating->id,
        'winner_rating_id'   => $otherRating->id,
        'loser_rating_id'    => $rating->id,
        'status'             => GameStatus::COMPLETED,
        'invitation_sent_at' => now(),
    ]);

    // Test wins and losses count
    expect($rating->wins()->count())
        ->toBe(2)
        ->and($rating->loses()->count())->toBe(1)
        ->and($rating->matches()->count())->toBe(3)
    ;
});

it('sorts ongoing matches by status priority', function () {
    // Create a league
    $league = League::factory()->create();

    // Create a user with an active rating
    $user = User::factory()->create();
    $rating = Rating::create([
        'league_id' => $league->id,
        'user_id'   => $user->id,
        'rating'    => 1000,
        'position'  => 1,
        'is_active' => true,
    ]);

    // Create a mock Game
    $game = Game::factory()->create();

    // Create another user for ratings
    $otherUser = User::factory()->create();
    $otherRating = Rating::create([
        'league_id' => $league->id,
        'user_id' => $otherUser->id,
        'rating'    => 1100,
        'position'  => 2,
        'is_active' => true,
    ]);

    // Create matches in different order deliberately to test sorting
    $pendingMatch = MatchGame::create([
        'game_id'            => $game->id,
        'league_id'          => $league->id,
        'first_rating_id'    => $rating->id,
        'second_rating_id'   => $otherRating->id,
        'status'             => GameStatus::PENDING,
        'invitation_sent_at' => now()->subDay(1),
    ]);

    $inProgressMatch = MatchGame::create([
        'game_id'            => $game->id,
        'league_id'          => $league->id,
        'first_rating_id'    => $rating->id,
        'second_rating_id'   => $otherRating->id,
        'status'             => GameStatus::IN_PROGRESS,
        'invitation_sent_at' => now()->subDay(2),
    ]);

    $mustBeConfirmedMatch = MatchGame::create([
        'game_id'            => $game->id,
        'league_id'          => $league->id,
        'first_rating_id'    => $rating->id,
        'second_rating_id'   => $otherRating->id,
        'status'             => GameStatus::MUST_BE_CONFIRMED,
        'invitation_sent_at' => now()->subDay(3),
    ]);

    // Get the ongoingMatches - the actual method being tested
    $ongoingMatches = $rating->ongoingMatches();

    // Convert to array to help with debugging
    $matchesArray = $ongoingMatches->values()->toArray();

    // Get IDs for inspection/debugging
    $statusOrder = $ongoingMatches->pluck('status')->map(fn($status) => $status->value)->toArray();

    // Assert the result matches our expected order - first should be MUST_BE_CONFIRMED
    $this->assertEquals(GameStatus::MUST_BE_CONFIRMED->value, $ongoingMatches->first()->status->value);

    // Second should be IN_PROGRESS
    $this->assertEquals(GameStatus::IN_PROGRESS->value, $ongoingMatches[1]->status->value);

    // Third should be PENDING
    $this->assertEquals(GameStatus::PENDING->value, $ongoingMatches->last()->status->value);
});

it('correctly manages last player rating ID', function () {
    $user = User::factory()->create();
    $league = League::factory()->create();

    $rating = Rating::create([
        'league_id' => $league->id,
        'user_id'   => $user->id,
        'rating'    => 1000,
        'position'  => 1,
        'is_active' => true,
    ]);

    // Initially null
    expect($rating->getLastPlayerRatingId())->toBeNull();

    // Set a value
    $testId = 123;
    $rating->setLastPlayerRatingId($testId);

    expect($rating->getLastPlayerRatingId())->toBe($testId);

    // Change the value
    $newTestId = 456;
    $rating->setLastPlayerRatingId($newTestId);

    expect($rating->getLastPlayerRatingId())->toBe($newTestId);

    // Set to null
    $rating->setLastPlayerRatingId(null);

    expect($rating->getLastPlayerRatingId())->toBeNull();
});
