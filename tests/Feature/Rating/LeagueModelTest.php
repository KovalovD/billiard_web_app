<?php

use App\Core\Models\Game;
use App\Core\Models\User;
use App\Leagues\Enums\RatingType;
use App\Leagues\Models\League;
use App\Leagues\Models\Rating;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('has the correct relationship with games', function () {
    $game = Game::factory()->create();
    $league = League::factory()->create([
        'game_id' => $game->id,
    ]);

    expect($league->game()->exists())
        ->toBeTrue()
        ->and($league->game->id)->toBe($game->id)
    ;
});

it('has the correct relationship with ratings', function () {
    $league = League::factory()->create();
    $user = User::factory()->create();

    $rating = Rating::create([
        'league_id' => $league->id,
        'user_id'   => $user->id,
        'rating'    => 1000,
        'position'  => 1,
        'is_active' => true,
    ]);

    expect($league->ratings()->exists())
        ->toBeTrue()
        ->and($league->ratings->first()->id)->toBe($rating->id)
    ;
});

it('correctly filters active ratings', function () {
    $league = League::factory()->create();
    $users = User::factory()->count(3)->create();

    // Create 2 active and 1 inactive rating
    $rating1 = Rating::create([
        'league_id' => $league->id,
        'user_id'   => $users[0]->id,
        'rating'    => 1000,
        'position'  => 1,
        'is_active' => true,
    ]);

    $rating2 = Rating::create([
        'league_id' => $league->id,
        'user_id'   => $users[1]->id,
        'rating'    => 1100,
        'position'  => 2,
        'is_active' => true,
    ]);

    $rating3 = Rating::create([
        'league_id' => $league->id,
        'user_id'   => $users[2]->id,
        'rating'    => 1200,
        'position'  => 3,
        'is_active' => false,
    ]);

    expect($league->activeRatings()->count())
        ->toBe(2)
        ->and($league->activeRatings->pluck('id')->toArray())->toContain($rating1->id, $rating2->id)
        ->and($league->activeRatings->pluck('id')->toArray())->not->toContain($rating3->id);
});

it('handles rating type enum correctly', function () {
    $eloLeague = League::factory()->create([
        'rating_type' => RatingType::Elo,
    ]);

    $killerPoolLeague = League::factory()->create([
        'rating_type' => RatingType::KillerPool,
    ]);

    expect($eloLeague->rating_type)
        ->toBe(RatingType::Elo)
        ->and($killerPoolLeague->rating_type)->toBe(RatingType::KillerPool)
        ->and($eloLeague->rating_type === RatingType::Elo)->toBeTrue()
        ->and($eloLeague->rating_type === RatingType::KillerPool)->toBeFalse()
        ->and($killerPoolLeague->rating_type === RatingType::KillerPool)->toBeTrue()
    ;

    // Test type checking
});

it('can store and retrieve rating rules correctly', function () {
    $winnerRules = [
        ['range' => [0, 50], 'strong' => 25, 'weak' => 25],
        ['range' => [51, 100], 'strong' => 20, 'weak' => 30],
    ];

    $loserRules = [
        ['range' => [0, 50], 'strong' => -25, 'weak' => -25],
        ['range' => [51, 100], 'strong' => -20, 'weak' => -30],
    ];

    $league = League::factory()->create([
        'rating_change_for_winners_rule' => $winnerRules,
        'rating_change_for_losers_rule'  => $loserRules,
    ]);

    // Reload from database
    $league = League::find($league->id);

    expect($league->rating_change_for_winners_rule)
        ->toBe($winnerRules)
        ->and($league->rating_change_for_losers_rule)->toBe($loserRules)
    ;
});

it('has the correct active ratings count', function () {
    $league = League::factory()->create();
    $users = User::factory()->count(5)->create();

    // Create 3 active ratings
    foreach ($users->take(3) as $i => $user) {
        Rating::create([
            'league_id' => $league->id,
            'user_id'   => $user->id,
            'rating'    => 1000 + ($i * 100),
            'position'  => $i + 1,
            'is_active' => true,
        ]);
    }

    // Create 2 inactive ratings
    foreach ($users->skip(3) as $i => $user) {
        Rating::create([
            'league_id' => $league->id,
            'user_id'   => $user->id,
            'rating'    => 1000 + ($i * 100),
            'position'  => $i + 4,
            'is_active' => false,
        ]);
    }

    // Reload league with counts
    $league = League::withCount('activeRatings')->find($league->id);

    expect($league->active_ratings_count)->toBe(3);
});

it('can have a maximum player limit', function () {
    $unlimitedLeague = League::factory()->create([
        'max_players' => 0, // 0 means unlimited
    ]);

    $limitedLeague = League::factory()->create([
        'max_players' => 5,
    ]);

    expect($unlimitedLeague->max_players)
        ->toBe(0)
        ->and($limitedLeague->max_players)->toBe(5)
    ;
});

it('uses soft deletes', function () {
    $league = League::factory()->create();
    $league->delete();

    // Should not be found with regular query
    expect(League::find($league->id))->toBeNull();

    // Should be found with withTrashed()
    $trashedLeague = League::withTrashed()->find($league->id);
    expect($trashedLeague)->not
        ->toBeNull()
        ->and($trashedLeague->id)->toBe($league->id)
        ->and($trashedLeague->deleted_at)->not->toBeNull();

    // Should be able to restore
    $trashedLeague->restore();

    // Should now be found again with regular query
    expect(League::find($league->id))->not->toBeNull();
});

it('has correct game type relationship through game', function () {
    $game = Game::factory()->create([
        'is_multiplayer' => true,
    ]);

    $league = League::factory()->create([
        'game_id' => $game->id,
    ]);

    // Load with relationship
    $league->load('game');

    expect($league->game->is_multiplayer)->toBeTrue();
});
