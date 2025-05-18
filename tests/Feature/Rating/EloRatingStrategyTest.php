<?php

use App\Leagues\Models\League;
use App\Leagues\Models\Rating;
use App\Leagues\Strategies\Rating\EloRatingStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('calculates correct delta when weaker wins', function () {
    $strategy = new EloRatingStrategy();

    /** @var League $league */
    $league = League::factory()->create([
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

    /** @var Rating $r1 */
    /** @var Rating $r2 */
    $r1 = Rating::factory()->create(['rating' => 980]);
    $r2 = Rating::factory()->create(['rating' => 1080]);

    $result = $strategy->calculate(
        [
            $r1,
            $r2,
        ],
        $r1->user_id, // слабый победил
        $league->rating_change_for_winners_rule,
        $league->rating_change_for_losers_rule,
    );

    expect($result[$r1->id])
        ->toBe(1010)
        ->and($result[$r2->id])
        ->toBe(1050)
    ; // 980 + 30
    // 1080 - 30
});

it('calculates correct delta when stronger wins', function () {
    $strategy = new EloRatingStrategy();

    /** @var League $league */
    $league = League::factory()->create([
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

    /** @var Rating $r1 */
    /** @var Rating $r2 */
    $r1 = Rating::factory()->create(['rating' => 1080]);
    $r2 = Rating::factory()->create(['rating' => 980]);

    $result = $strategy->calculate(
        [
            $r1,
            $r2,
        ],
        $r1->user_id, // сильный победил
        $league->rating_change_for_winners_rule,
        $league->rating_change_for_losers_rule,
    );

    expect($result[$r1->id])
        ->toBe(1100)
        ->and($result[$r2->id])
        ->toBe(960)
    ; // 1080 + 20
    // 980 - 20
});

it('calculates equal rating changes when ratings are the same', function () {
    $strategy = new EloRatingStrategy();

    /** @var League $league */
    $league = League::factory()->create([
        'rating_change_for_winners_rule' => [
            ['range' => [0, 50], 'strong' => 25, 'weak' => 25],
        ],
        'rating_change_for_losers_rule'  => [
            ['range' => [0, 50], 'strong' => -25, 'weak' => -25],
        ],
    ]);

    /** @var Rating $r1 */
    /** @var Rating $r2 */
    $r1 = Rating::factory()->create(['rating' => 1000]);
    $r2 = Rating::factory()->create(['rating' => 1000]);

    $result = $strategy->calculate(
        [
            $r1,
            $r2,
        ],
        $r1->user_id, // Either player winning should use the same rule
        $league->rating_change_for_winners_rule,
        $league->rating_change_for_losers_rule,
    );

    expect($result[$r1->id])
        ->toBe(1025) // 1000 + 25
        ->and($result[$r2->id])->toBe(975)
    ; // 1000 - 25
});

it('throws exception when no rule matches the delta', function () {
    $strategy = new EloRatingStrategy();

    /** @var League $league */
    $league = League::factory()->create([
        'rating_change_for_winners_rule' => [
            ['range' => [0, 50], 'strong' => 25, 'weak' => 25],
            // No rule for deltas > 50
        ],
        'rating_change_for_losers_rule'  => [
            ['range' => [0, 50], 'strong' => -25, 'weak' => -25],
            // No rule for deltas > 50
        ],
    ]);

    /** @var Rating $r1 */
    /** @var Rating $r2 */
    $r1 = Rating::factory()->create(['rating' => 1200]);
    $r2 = Rating::factory()->create(['rating' => 1000]);

    // Delta is 200, which doesn't match any rule
    expect(function () use ($strategy, $r1, $r2, $league) {
        $strategy->calculate(
            [
                $r1,
                $r2,
            ],
            $r1->user_id,
            $league->rating_change_for_winners_rule,
            $league->rating_change_for_losers_rule,
        );
    })->toThrow(LogicException::class, 'No rule matched for delta 200');
});

it('handles extreme rating differences correctly', function () {
    $strategy = new EloRatingStrategy();

    /** @var League $league */
    $league = League::factory()->create([
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

    /** @var Rating $r1 */
    /** @var Rating $r2 */
    $r1 = Rating::factory()->create(['rating' => 2000]);
    $r2 = Rating::factory()->create(['rating' => 1000]);

    // Extreme difference: 1000 points
    $result = $strategy->calculate(
        [
            $r1,
            $r2,
        ],
        $r1->user_id, // Stronger player wins
        $league->rating_change_for_winners_rule,
        $league->rating_change_for_losers_rule,
    );

    expect($result[$r1->id])
        ->toBe(2010) // 2000 + 10
        ->and($result[$r2->id])->toBe(960)
    ; // 1000 - 40

    // Now test when weaker player wins with extreme difference
    $result = $strategy->calculate(
        [
            $r1,
            $r2,
        ],
        $r2->user_id, // Weaker player wins
        $league->rating_change_for_winners_rule,
        $league->rating_change_for_losers_rule,
    );

    expect($result[$r1->id])
        ->toBe(1990) // 2000 - 10
        ->and($result[$r2->id])->toBe(1040)
    ; // 1000 + 40
});
