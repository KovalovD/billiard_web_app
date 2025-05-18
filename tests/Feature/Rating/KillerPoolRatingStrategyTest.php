<?php

use App\Leagues\Strategies\Rating\KillerPoolRatingStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('adds points to ratings based on points in winnersRules', function () {
    $strategy = new KillerPoolRatingStrategy();

    // Create test ratings (manually created arrays to match the format expected by KillerPoolRatingStrategy)
    $ratings = [
        ['id' => 1, 'user_id' => 101, 'rating' => 1000],
        ['id' => 2, 'user_id' => 102, 'rating' => 1200],
        ['id' => 3, 'user_id' => 103, 'rating' => 1500],
    ];

    // Points to be added for each user
    $points = [
        101 => 10, // 10 points for user 101
        102 => 20, // 20 points for user 102
        103 => 5,  // 5 points for user 103
    ];

    $result = $strategy->calculate($ratings, 0, $points);

    expect($result)
        ->toBeArray()
        ->and($result[1])->toBe(1010) // 1000 + 10
        ->and($result[2])->toBe(1220) // 1200 + 20
        ->and($result[3])->toBe(1505)
    ; // 1500 + 5
});

it('handles missing player points by defaulting to 0', function () {
    $strategy = new KillerPoolRatingStrategy();

    // Create test ratings
    $ratings = [
        ['id' => 1, 'user_id' => 101, 'rating' => 1000],
        ['id' => 2, 'user_id' => 102, 'rating' => 1200],
        ['id' => 3, 'user_id' => 103, 'rating' => 1500],
    ];

    // Points only for some users
    $points = [
        101 => 10, // 10 points for user 101
        // No points for user 102 (should default to 0)
        103 => 5,  // 5 points for user 103
    ];

    $result = $strategy->calculate($ratings, 0, $points);

    expect($result)
        ->toBeArray()
        ->and($result[1])->toBe(1010) // 1000 + 10
        ->and($result[2])->toBe(1200) // 1200 + 0 (missing, defaults to 0)
        ->and($result[3])->toBe(1505)
    ; // 1500 + 5
});

it('handles empty ratings array', function () {
    $strategy = new KillerPoolRatingStrategy();

    $result = $strategy->calculate([], 0, []);

    expect($result)
        ->toBeArray()
        ->and($result)->toBeEmpty()
    ;
});

it('ignores losersRules parameter', function () {
    $strategy = new KillerPoolRatingStrategy();

    $ratings = [
        ['id' => 1, 'user_id' => 101, 'rating' => 1000],
    ];

    $points = [
        101 => 10,
    ];

    $losersRules = [
        101 => -50, // This should be ignored
    ];

    $result = $strategy->calculate($ratings, 0, $points, $losersRules);

    expect($result[1])->toBe(1010); // Only the winner rules affect the calculation
});

it('handles zero-point allocations', function () {
    $strategy = new KillerPoolRatingStrategy();

    $ratings = [
        ['id' => 1, 'user_id' => 101, 'rating' => 1000],
        ['id' => 2, 'user_id' => 102, 'rating' => 1500],
    ];

    $points = [
        101 => 0, // Zero points
        102 => 0, // Zero points
    ];

    $result = $strategy->calculate($ratings, 0, $points);

    expect($result[1])
        ->toBe(1000) // No change
        ->and($result[2])->toBe(1500)
    ; // No change
});

it('processes negative points correctly', function () {
    $strategy = new KillerPoolRatingStrategy();

    $ratings = [
        ['id' => 1, 'user_id' => 101, 'rating' => 1000],
    ];

    $points = [
        101 => -15, // Negative points
    ];

    $result = $strategy->calculate($ratings, 0, $points);

    expect($result[1])->toBe(985); // 1000 - 15
});
