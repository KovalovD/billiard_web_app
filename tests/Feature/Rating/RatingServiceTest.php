<?php

use App\Core\Models\Game;
use App\Core\Models\User;
use App\Leagues\Enums\RatingType;
use App\Leagues\Models\League;
use App\Leagues\Models\Rating;
use App\Leagues\Services\RatingService;
use App\Matches\Models\MatchGame;
use App\Matches\Models\MultiplayerGame;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new RatingService();
});

it('gets active ratings with users for a league', function () {
    // Create a league
    $league = League::factory()->create();

    // Create some users
    $users = User::factory()->count(3)->create();

    // Create active ratings for these users
    foreach ($users as $i => $user) {
        Rating::create([
            'league_id' => $league->id,
            'user_id'   => $user->id,
            'rating'    => 1000 + ($i * 100),
            'position'  => $i + 1,
            'is_active' => true,
        ]);
    }

    // Create an inactive rating that shouldn't be returned
    Rating::create([
        'league_id' => $league->id,
        'user_id'   => User::factory()->create()->id,
        'rating'    => 1500,
        'position'  => 4,
        'is_active' => false,
    ]);

    // Get active ratings
    $activeRatings = $this->service->getRatingsWithUsers($league);

    expect($activeRatings)
        ->toHaveCount(3)
        ->and($activeRatings->pluck('is_active')->unique())->toEqual(collect([true]))
        ->and($activeRatings->pluck('user_id')->sort()->values())->toEqual($users->pluck('id')->sort()->values())
    ;
});

it('adds player to league considering max players limit', function () {
    // Create a league with max_players = 2
    $league = League::factory()->create([
        'max_players' => 2,
    ]);

    // Create 3 users
    $users = User::factory()->count(3)->create();

    // Add first two users - should succeed
    expect($this->service->addPlayer($league, $users[0]))
        ->toBeTrue()
        ->and($this->service->addPlayer($league, $users[1]))->toBeTrue()
    ;

    // Confirm both users
    Rating::where('league_id', $league->id)->update(['is_confirmed' => true]);

    // Attempt to add third user - should fail due to max_players limit
    expect($this->service->addPlayer($league, $users[2]))->toBeFalse();

    // Now set max_players to 0 (unlimited)
    $league->update(['max_players' => 0]);

    // Should now be able to add the third user
    expect($this->service->addPlayer($league, $users[2]))->toBeTrue();

    // Verify proper count of active ratings
    $activeRatings = Rating::where('league_id', $league->id)
        ->where('is_active', true)
        ->count()
    ;

    expect($activeRatings)->toBe(3);
});

it('disables a player in a league', function () {
    // Create a league
    $league = League::factory()->create();

    // Create a user with an active rating
    $user = User::factory()->create();
    Rating::create([
        'league_id' => $league->id,
        'user_id'   => $user->id,
        'rating'    => 1000,
        'position'  => 1,
        'is_active' => true,
    ]);

    // Verify the rating is active
    expect(Rating::where('league_id', $league->id)
        ->where('user_id', $user->id)
        ->where('is_active', true)
        ->exists())->toBeTrue();

    // Disable the player
    $this->service->disablePlayer($league, $user);

    // Verify the rating is now inactive
    expect(Rating::where('league_id', $league->id)
        ->where('user_id', $user->id)
        ->where('is_active', false)
        ->exists())->toBeTrue();
});

it('updates ratings with the elo strategy', function () {
    // Create a league with Elo rating type
    $league = League::factory()->create([
        'rating_type' => RatingType::Elo,
        'rating_change_for_winners_rule' => [
            ['range' => [0, 50], 'strong' => 25, 'weak' => 25],
            ['range' => [51, 100], 'strong' => 20, 'weak' => 30],
            ['range' => [101, 200], 'strong' => 15, 'weak' => 35],
            ['range' => [201, 1000000], 'strong' => 10, 'weak' => 40],
        ],
        'rating_change_for_losers_rule'  => [
            ['range' => [0, 50], 'strong' => -25, 'weak' => -25],
            ['range' => [51, 100], 'strong' => -20, 'weak' => -30],
            ['range' => [101, 200], 'strong' => -15, 'weak' => -35],
            ['range' => [201, 1000000], 'strong' => -10, 'weak' => -40],
        ],
    ]);

    // Create 2 users with different ratings
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $rating1 = Rating::create([
        'league_id' => $league->id,
        'user_id'   => $user1->id,
        'rating'    => 1000,
        'position'  => 1,
        'is_active' => true,
    ]);

    $rating2 = Rating::create([
        'league_id' => $league->id,
        'user_id'   => $user2->id,
        'rating'    => 1100,
        'position'  => 2,
        'is_active' => true,
    ]);

    // Create a match game
    $matchGame = MatchGame::create([
        'game_id'           => Game::factory()->create()->id,
        'league_id'         => $league->id,
        'first_rating_id'   => $rating1->id,
        'second_rating_id'  => $rating2->id,
        'first_user_score'  => 5,
        'second_user_score' => 3,
        'winner_rating_id'  => $rating1->id,
        'loser_rating_id'   => $rating2->id,
        'invitation_sent_at' => now(),
    ]);

    // Update ratings
    $result = $this->service->updateRatings($matchGame, $user1->id);

    // Delta = 100, weaker player (rating1) won
    expect($result[$rating1->id])
        ->toBe(1030) // 1000 + 30
        ->and($result[$rating2->id])->toBe(1070)
        ->and(Rating::find($rating1->id)->rating)
        ->toBe(1030)
        ->and(Rating::find($rating2->id)->rating)->toBe(1070)
    ; // 1100 - 15

    // Verify the ratings were updated in the database
});

it('gets active rating for user in league', function () {
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

    // Get the active rating
    $activeRating = $this->service->getActiveRatingForUserLeague($user, $league);

    expect($activeRating)->not
        ->toBeNull()
        ->and($activeRating?->id)->toBe($rating->id)
    ;

    // Now create an inactive rating for another user
    $inactiveUser = User::factory()->create();
    Rating::create([
        'league_id' => $league->id,
        'user_id'   => $inactiveUser->id,
        'rating'    => 1000,
        'position'  => 2,
        'is_active' => false,
    ]);

    // Should return null for inactive rating
    $inactiveRating = $this->service->getActiveRatingForUserLeague($inactiveUser, $league);

    expect($inactiveRating)->toBeNull();
});

it('applies rating points from multiplayer game', function () {
    // Create a league with KillerPool rating type
    $league = League::factory()->create([
        'rating_type' => RatingType::KillerPool,
    ]);

    // Create 3 users with ratings
    $users = User::factory()->count(3)->create();
    $ratings = [];

    foreach ($users as $i => $user) {
        $ratings[] = Rating::create([
            'league_id' => $league->id,
            'user_id'   => $user->id,
            'rating'    => 1000,
            'position'  => $i + 1,
            'is_active' => true,
        ]);
    }

    // Create a multiplayer game
    $game = MultiplayerGame::create([
        'league_id' => $league->id,
        'game_id'   => Game::factory()->create(['is_multiplayer' => true])->id,
        'name'      => 'Test Game',
        'status'    => 'completed',
    ]);

    // Create players with rating points
    foreach ($users as $i => $user) {
        $game->players()->create([
            'joined_at' => now(),
            'user_id'         => $user->id,
            'rating_points'   => 10 + ($i * 5), // 10, 15, 20 points
            'finish_position' => 3 - $i, // 3, 2, 1 (reverse order)
        ]);
    }

    // Apply rating points
    $this->service->applyRatingPointsForMultiplayerGame($game);

    // Verify ratings were updated correctly
    $updatedRatings = Rating::where('league_id', $league->id)
        ->orderBy('user_id')
        ->get()
        ->pluck('rating')
        ->toArray()
    ;

    // Each player should get their rating points added
    expect($updatedRatings)->toBe([1010, 1015, 1020]);

    // Verify positions were rearranged
    $newPositions = Rating::where('league_id', $league->id)
        ->orderBy('position')
        ->pluck('user_id')
        ->toArray()
    ;

    // Expected order: user[2] (1020), user[1] (1015), user[0] (1010)
    expect($newPositions)->toBe([$users[2]->id, $users[1]->id, $users[0]->id]);
});

it('throws exception when applying multiplayer rating points with wrong strategy', function () {
    // Create a league with Elo rating type
    $league = League::factory()->create([
        'rating_type' => RatingType::Elo,
    ]);

    // Create a multiplayer game
    $game = MultiplayerGame::create([
        'league_id' => $league->id,
        'game_id'   => Game::factory()->create(['is_multiplayer' => true])->id,
        'name'      => 'Test Game',
        'status'    => 'completed',
    ]);

    // Should throw exception because we're using Elo for a multiplayer game
    expect(function () use ($game) {
        $this->service->applyRatingPointsForMultiplayerGame($game);
    })->toThrow(LogicException::class, "League rating type must be KillerPool");
});

it('handles reactivating a previously inactive player', function () {
    // Create a league
    $league = League::factory()->create();

    // Create a user
    $user = User::factory()->create();

    // Add the player initially
    $this->service->addPlayer($league, $user);

    // Get the rating
    $rating = Rating::where('user_id', $user->id)
        ->where('league_id', $league->id)
        ->first()
    ;

    // Verify the rating exists before proceeding
    expect($rating)->not->toBeNull();

    // Manually update to inactive to simulate disabling
    if ($rating) {
        $rating->is_active = false;
        $rating->save();
    }

    // Verify the rating is inactive
    expect(Rating::where('league_id', $league->id)
        ->where('user_id', $user->id)
        ->where('is_active', false)
        ->exists())->toBeTrue();

    // Reactivate the player
    $this->service->addPlayer($league, $user);

    // Verify the rating is now active again
    expect(Rating::where('league_id', $league->id)
        ->where('user_id', $user->id)
        ->where('is_active', true)
        ->exists())->toBeTrue();
});
