<?php

use App\Core\Models\Game;
use App\Core\Models\User;
use App\Leagues\Models\League;
use App\Leagues\Models\Rating;
use App\Leagues\Services\RatingService;
use App\Matches\Models\MatchGame;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('updates ratings and reorders positions for 5 users with strict positions', function () {
    $service = new RatingService();

    $league = League::factory()->create([
        'start_rating'                   => 1000,
        'rating_type'                    => 'elo',
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

    $users = User::factory()->count(5)->create();
    [$u1, $u2, $u3, $u4, $u5] = $users;

    // Уникальные рейтинги
    /** @var Collection<MatchGame> $ratings */
    $ratings = collect([980, 1000, 1080, 990, 1060])
        ->map(fn($rating, $i) => Rating::create([
            'league_id' => $league->id,
            'user_id'   => $users[$i]->id,
            'rating'    => $rating,
            'position'  => $i + 1,
            'is_active' => true,
        ]))
    ;

    $matchGame = MatchGame::create([
        'game_id'            => Game::factory()->create()->id,
        'league_id'          => $league->id,
        'first_rating_id'    => $ratings[0]->id,
        'second_rating_id'   => $ratings[2]->id,
        'first_user_score'   => 5,
        'second_user_score'  => 3,
        'winner_rating_id'   => $ratings[0]->id,
        'loser_rating_id'    => $ratings[2]->id,
        'invitation_sent_at' => now(),
    ]);

    // u1 (980) выигрывает у u3 (1080) → Δ = 100 → слабый победил
    // по правилам: +30/-30
    $service->updateRatings($matchGame, $u1->id);

    // Обновляем модели
    $ratings = $ratings->map(fn($r) => $r->refresh());

    expect($ratings[0]->rating)
        ->toBe(1010)
        ->and($ratings[2]->rating)
        ->toBe(1050)
    ; // 980 + 30
    // 1080 - 30

    // Проверим порядок по position
    $ordered = Rating::where('league_id', $league->id)
        ->orderBy('position')
        ->get()
    ;

    $expectedOrder = [
        $u5->id, // 1060
        $u3->id, // 1050
        $u1->id, // 1010
        $u2->id, // 1000
        $u4->id, // 990
    ];

    expect($ordered->pluck('user_id')->toArray())->toBe($expectedOrder);

    echo "\n[RatingServiceTest] Финальные позиции:\n";
    $ordered->each(function ($r) {
        echo "Позиция {$r->position}: user_id={$r->user_id}, рейтинг={$r->rating}\n";
    });
});
