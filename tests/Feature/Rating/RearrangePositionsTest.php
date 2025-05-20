<?php

use App\Core\Models\Game;
use App\Core\Models\User;
use App\Leagues\Models\League;
use App\Leagues\Models\Rating;
use App\Leagues\Services\RatingService;
use App\Matches\Enums\GameStatus;
use App\Matches\Models\MatchGame;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new RatingService();
});

it('rearranges positions based on match results', function () {
    // Create a league
    $league = League::factory()->create();

    // Create a game
    $game = Game::factory()->create();

    // Create users with equal ratings initially
    $users = User::factory()->count(4)->create();
    $ratings = [];

    foreach ($users as $i => $user) {
        $ratings[] = Rating::create([
            'league_id'    => $league->id,
            'user_id'      => $user->id,
            'rating'       => 1000, // All equal ratings
            'position'     => 0, // Will be rearranged
            'is_active'    => true,
            'is_confirmed' => true,
        ]);
    }

    // Rearrange initial positions
    $this->service->rearrangePositions($league->id);

    // Create matches with different outcomes to test all tiebreakers

    // Match 1: user[0] wins against user[1] with a large score difference
    // This affects: wins_count, frame_diff, frames_won
    MatchGame::create([
        'game_id'           => $game->id,
        'league_id'         => $league->id,
        'first_rating_id'   => $ratings[0]->id,
        'second_rating_id'  => $ratings[1]->id,
        'first_user_score'  => 5,
        'second_user_score' => 0,
        'winner_rating_id'  => $ratings[0]->id,
        'loser_rating_id'   => $ratings[1]->id,
        'status'            => GameStatus::COMPLETED,
        'invitation_sent_at' => now(),
        'finished_at'       => now(),
    ]);

    // Match 2: user[2] wins against user[3] with a close score
    // This affects: wins_count, frame_diff, frames_won
    MatchGame::create([
        'game_id'           => $game->id,
        'league_id'         => $league->id,
        'first_rating_id'   => $ratings[2]->id,
        'second_rating_id'  => $ratings[3]->id,
        'first_user_score'  => 3,
        'second_user_score' => 2,
        'winner_rating_id'  => $ratings[2]->id,
        'loser_rating_id'   => $ratings[3]->id,
        'status'            => GameStatus::COMPLETED,
        'invitation_sent_at' => now(),
        'finished_at'       => now(),
    ]);

    // Match 3: user[1] wins against user[2]
    // Now both user[0] and user[1] have 1 win each
    MatchGame::create([
        'game_id'           => $game->id,
        'league_id'         => $league->id,
        'first_rating_id'   => $ratings[1]->id,
        'second_rating_id'  => $ratings[2]->id,
        'first_user_score'  => 3,
        'second_user_score' => 1,
        'winner_rating_id'  => $ratings[1]->id,
        'loser_rating_id'   => $ratings[2]->id,
        'status'            => GameStatus::COMPLETED,
        'invitation_sent_at' => now(),
        'finished_at'       => now(),
    ]);

    // Match 4: user[3] plays extra match but loses
    // This affects: matches_count
    MatchGame::create([
        'game_id'           => $game->id,
        'league_id'         => $league->id,
        'first_rating_id'   => $ratings[0]->id,
        'second_rating_id'  => $ratings[3]->id,
        'first_user_score'  => 3,
        'second_user_score' => 1,
        'winner_rating_id'  => $ratings[0]->id,
        'loser_rating_id'   => $ratings[3]->id,
        'status'            => GameStatus::COMPLETED,
        'invitation_sent_at' => now(),
        'finished_at'       => now(),
    ]);

    // Rearrange positions
    $this->service->rearrangePositions($league->id);

    // Expected order based on tiebreakers:
    // 1. user[0]: 2 wins, +7 frame diff, 8 frames won, 2 matches
    // 2. user[1]: 1 win, +2 frame diff, 3 frames won, 2 matches
    // 3. user[2]: 1 win, +1 frame diff, 4 frames won, 2 matches
    // 4. user[3]: 0 wins, -10 frame diff, 3 frames won, 2 matches

    $expectedOrder = [$users[0]->id, $users[2]->id, $users[1]->id, $users[3]->id];

    $actualOrder = Rating::where('league_id', $league->id)
        ->orderBy('position')
        ->pluck('user_id')
        ->toArray()
    ;

    expect($actualOrder)->toBe($expectedOrder);
});

it('handles rearranging positions with no active ratings', function () {
    /** @var League $league */
    $league = League::factory()->create();

    // No ratings created

    // This should not throw any exceptions
    $this->service->rearrangePositions($league->id);

    // Verify no ratings exist
    expect(Rating::where('league_id', $league->id)->count())->toBe(0);
});

it('correctly rearranges positions for multiple ratings with the same metrics', function () {
    /** @var League $league */
    $league = League::factory()->create();

    // Create 5 users with the same rating value
    $users = User::factory()->count(5)->create();

    foreach ($users as $i => $user) {
        Rating::create([
            'league_id' => $league->id,
            'user_id'   => $user->id,
            'rating'    => 1000, // Same rating for all
            'position'  => 0, // Will be rearranged
            'is_active' => true,
        ]);
    }

    // Rearrange positions
    $this->service->rearrangePositions($league->id);

    // Should be ordered by lastname, firstname
    $ratingsInOrder = Rating::where('league_id', $league->id)
        ->with('user')
        ->orderBy('position')
        ->get()
    ;

    $expectedOrder = $users->sortBy('lastname')->sortBy('firstname')->pluck('id')->toArray();
    $actualOrder = $ratingsInOrder->pluck('user_id')->toArray();

    expect($actualOrder)->toBe($expectedOrder);
});

it('handles a mix of active and inactive ratings correctly', function () {
    /** @var League $league */
    $league = League::factory()->create();

    // Create 3 active and 2 inactive users
    $activeUsers = User::factory()->count(3)->create();
    $inactiveUsers = User::factory()->count(2)->create();

    // Create active ratings
    foreach ($activeUsers as $i => $user) {
        Rating::create([
            'league_id' => $league->id,
            'user_id'   => $user->id,
            'rating'    => 1000 + ($i * 100), // Different ratings
            'position'  => 0, // Will be rearranged
            'is_active' => true,
        ]);
    }

    // Create inactive ratings
    foreach ($inactiveUsers as $i => $user) {
        Rating::create([
            'league_id' => $league->id,
            'user_id'   => $user->id,
            'rating'    => 1500 + ($i * 100), // Higher ratings than active users
            'position'  => 0, // Will be rearranged
            'is_active' => false,
        ]);
    }

    // Rearrange positions
    $this->service->rearrangePositions($league->id);

    // Get active ratings in order
    $activeRatingsInOrder = Rating::where('league_id', $league->id)
        ->where('is_active', true)
        ->orderBy('position')
        ->get()
    ;

    // Verify positions are sequential
    $positions = $activeRatingsInOrder->pluck('position')->toArray();
    expect($positions)->toBe([3, 4, 5]);

    // Verify correct ordering by rating
    $ratings = $activeRatingsInOrder->pluck('rating')->toArray();
    expect($ratings)->toBe([1200, 1100, 1000]); // Descending order
});

it('updates positions after ratings change', function () {
    /** @var League $league */
    $league = League::factory()->create();

    // Create users with different ratings
    $users = User::factory()->count(3)->create();

    Rating::create([
        'league_id' => $league->id,
        'user_id'   => $users[0]->id,
        'rating'    => 1000,
        'position'  => 0,
        'is_active' => true,
    ]);

    Rating::create([
        'league_id' => $league->id,
        'user_id'   => $users[1]->id,
        'rating'    => 1200,
        'position'  => 0,
        'is_active' => true,
    ]);

    Rating::create([
        'league_id' => $league->id,
        'user_id'   => $users[2]->id,
        'rating'    => 1100,
        'position'  => 0,
        'is_active' => true,
    ]);

    // Rearrange positions
    $this->service->rearrangePositions($league->id);

    // Verify initial positions
    $initialPositions = Rating::where('league_id', $league->id)
        ->orderBy('position')
        ->pluck('user_id')
        ->toArray()
    ;

    // Expected order: user[1] (1200), user[2] (1100), user[0] (1000)
    expect($initialPositions)->toBe([$users[1]->id, $users[2]->id, $users[0]->id]);

    // Change ratings
    Rating::where('user_id', $users[0]->id)
        ->where('league_id', $league->id)
        ->update(['rating' => 1500])
    ; // Now highest

    // Rearrange positions again
    $this->service->rearrangePositions($league->id);

    // Verify updated positions
    $updatedPositions = Rating::where('league_id', $league->id)
        ->orderBy('position')
        ->pluck('user_id')
        ->toArray()
    ;

    // Expected new order: user[0] (1500), user[1] (1200), user[2] (1100)
    expect($updatedPositions)->toBe([$users[0]->id, $users[1]->id, $users[2]->id]);
});
