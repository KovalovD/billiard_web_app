<?php

use App\Core\Models\User;
use App\Leagues\Models\League;
use App\Leagues\Models\Rating;
use App\Leagues\Services\RatingService;
use App\Matches\Models\MatchGame;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(RatingService::class);
});

it('0) sorts by rating descending', function () {
    /** @var League $league */
    $league = League::factory()->create();
    $strong = User::factory()->create(['firstname' => 'Strong', 'lastname' => 'Player']);
    $weak = User::factory()->create(['firstname' => 'Weak', 'lastname' => 'Player']);

    Rating::create([
        'league_id' => $league->id, 'user_id' => $strong->id,
        'rating' => 2000, 'position' => 0, 'is_active' => true,
    ]);
    Rating::create([
        'league_id' => $league->id, 'user_id' => $weak->id,
        'rating' => 1000, 'position' => 0, 'is_active' => true,
    ]);

    $this->service->rearrangePositions($league->id);

    expect(Rating::where('user_id', $strong->id)->first()->position)
        ->toBe(1)
        ->and(Rating::where('user_id', $weak->id)->first()->position)->toBe(2)
    ;
});

it('1) breaks tie by wins_count descending', function () {
    /** @var League $league */
    $league = League::factory()->create();
    $a = User::factory()->create(['firstname' => 'A', 'lastname' => 'Alpha']);
    $b = User::factory()->create(['firstname' => 'B', 'lastname' => 'Beta']);

    $rA = Rating::create([
        'league_id' => $league->id, 'user_id' => $a->id, 'rating' => 1500, 'position' => 0, 'is_active' => true,
    ]);
    $rB = Rating::create([
        'league_id' => $league->id, 'user_id' => $b->id, 'rating' => 1500, 'position' => 0, 'is_active' => true,
    ]);

    // A выигрывает один матч, B — ни одного
    MatchGame::create([
        'league_id'          => $league->id,
        'first_rating_id'    => $rA->id,
        'second_rating_id'   => $rB->id,
        'first_user_score'   => 5,
        'second_user_score'  => 3,
        'winner_rating_id'   => $rA->id,
        'loser_rating_id'    => $rB->id,
        'invitation_sent_at' => now(),
    ]);

    $this->service->rearrangePositions($league->id);

    expect($rA->fresh()->position)
        ->toBe(1)
        ->and($rB->fresh()->position)->toBe(2)
    ;
});

it('2) breaks tie by frame_diff descending when rating and wins_count equal', function () {
    /** @var League $league */
    $league = League::factory()->create();
    $a = User::factory()->create(['firstname' => 'A', 'lastname' => 'Alpha']);
    $b = User::factory()->create(['firstname' => 'B', 'lastname' => 'Beta']);
    $c = User::factory()->create(['firstname' => 'C', 'lastname' => 'Gamma']);

    $rA = Rating::create([
        'league_id' => $league->id, 'user_id' => $a->id, 'rating' => 1500, 'position' => 0, 'is_active' => true,
    ]);
    $rB = Rating::create([
        'league_id' => $league->id, 'user_id' => $b->id, 'rating' => 1500, 'position' => 0, 'is_active' => true,
    ]);
    $rC = Rating::create([
        'league_id' => $league->id, 'user_id' => $c->id, 'rating' => 1500, 'position' => 0, 'is_active' => true,
    ]);

    // ни у кого нет побед → wins_count = 0
    // A: проигрыш 3–5 → diff = 3 – 5 = -2
    MatchGame::create([
        'league_id'          => $league->id,
        'first_rating_id'    => $rA->id,
        'second_rating_id' => $rC->id, // использует C вместо невалидного ID
        'first_user_score'   => 3,
        'second_user_score'  => 5,
        'winner_rating_id' => $rC->id,
        'loser_rating_id'  => $rA->id,
        'invitation_sent_at' => now(),
    ]);
    // B: проигрыш 1–5 → diff = 1 – 5 = -4
    MatchGame::create([
        'league_id'          => $league->id,
        'first_rating_id'    => $rB->id,
        'second_rating_id' => $rC->id, // использует C вместо невалидного ID
        'first_user_score'   => 1,
        'second_user_score'  => 5,
        'winner_rating_id' => $rC->id,
        'loser_rating_id'  => $rB->id,
        'invitation_sent_at' => now(),
    ]);

    $this->service->rearrangePositions($league->id);

    // C has 2 wins, so C is position 1
    // A has 0 wins but better frame_diff (-2 vs -4), so A is position 2
    // B has 0 wins and worse frame_diff (-4), so B is position 3
    expect($rA->fresh()->position)
        ->toBe(2)
        ->and($rB->fresh()->position)->toBe(3)
    ;
});

it('3) breaks tie by frames_won descending when previous metrics equal', function () {
    /** @var League $league */
    $league = League::factory()->create();
    $a = User::factory()->create(['firstname' => 'A', 'lastname' => 'Alpha']);
    $b = User::factory()->create(['firstname' => 'B', 'lastname' => 'Beta']);

    $rA = Rating::create([
        'league_id' => $league->id, 'user_id' => $a->id, 'rating' => 1500, 'position' => 0, 'is_active' => true,
    ]);
    $rB = Rating::create([
        'league_id' => $league->id, 'user_id' => $b->id, 'rating' => 1500, 'position' => 0, 'is_active' => true,
    ]);

    // обеих по одной победе и по одному поражению (diff=0 у обоих)
    // A: выиграл 7–5, проиграл 5–7 → frames_won = 7+5 = 12
    MatchGame::create([
        'league_id'          => $league->id,
        'first_rating_id'    => $rA->id,
        'second_rating_id'   => $rB->id,
        'first_user_score'   => 7,
        'second_user_score'  => 5,
        'winner_rating_id'   => $rA->id,
        'loser_rating_id'    => $rB->id,
        'invitation_sent_at' => now(),
    ]);
    MatchGame::create([
        'league_id'          => $league->id,
        'first_rating_id'    => $rA->id,
        'second_rating_id'   => $rB->id,
        'first_user_score'   => 5,
        'second_user_score'  => 7,
        'winner_rating_id'   => $rB->id,
        'loser_rating_id'    => $rA->id,
        'invitation_sent_at' => now(),
    ]);
    // B: выиграл 6–4, проиграл 4–6 → frames_won = 6+4 = 10
    MatchGame::create([
        'league_id'          => $league->id,
        'first_rating_id'    => $rB->id,
        'second_rating_id'   => $rA->id,
        'first_user_score'   => 6,
        'second_user_score'  => 4,
        'winner_rating_id'   => $rB->id,
        'loser_rating_id'    => $rA->id,
        'invitation_sent_at' => now(),
    ]);
    MatchGame::create([
        'league_id'          => $league->id,
        'first_rating_id'    => $rB->id,
        'second_rating_id'   => $rA->id,
        'first_user_score'   => 4,
        'second_user_score'  => 6,
        'winner_rating_id'   => $rA->id,
        'loser_rating_id'    => $rB->id,
        'invitation_sent_at' => now(),
    ]);

    $this->service->rearrangePositions($league->id);

    expect($rA->fresh()->position)
        ->toBe(1)
        ->and($rB->fresh()->position)->toBe(2)
    ;
});

it('4) breaks tie by matches_count descending when all previous metrics equal', function () {
    /** @var League $league */
    $league = League::factory()->create();
    $a = User::factory()->create(['firstname' => 'A', 'lastname' => 'Alpha']);
    $b = User::factory()->create(['firstname' => 'B', 'lastname' => 'Beta']);
    $c = User::factory()->create(['firstname' => 'C', 'lastname' => 'Gamma']);

    $rA = Rating::create([
        'league_id' => $league->id, 'user_id' => $a->id, 'rating' => 1500, 'position' => 0, 'is_active' => true,
    ]);
    $rB = Rating::create([
        'league_id' => $league->id, 'user_id' => $b->id, 'rating' => 1500, 'position' => 0, 'is_active' => true,
    ]);
    $rC = Rating::create([
        'league_id' => $league->id, 'user_id' => $c->id, 'rating' => 1500, 'position' => 0, 'is_active' => true,
    ]);

    // Два первых матча между A и B дают wins_count=1, diff=0, frames_won=10
    MatchGame::create([
        'league_id'          => $league->id,
        'first_rating_id'    => $rA->id,
        'second_rating_id'   => $rB->id,
        'first_user_score'   => 6,
        'second_user_score'  => 4,
        'winner_rating_id'   => $rA->id,
        'loser_rating_id'    => $rB->id,
        'invitation_sent_at' => now(),
    ]);
    MatchGame::create([
        'league_id'          => $league->id,
        'first_rating_id'    => $rB->id,
        'second_rating_id'   => $rA->id,
        'first_user_score'   => 6,
        'second_user_score'  => 4,
        'winner_rating_id'   => $rB->id,
        'loser_rating_id'    => $rA->id,
        'invitation_sent_at' => now(),
    ]);
    // Дополнительный "пустой" матч C→B, нулевые очки и без победителя,
    // чтобы увеличить matches_count только для B
    MatchGame::create([
        'league_id'          => $league->id,
        'first_rating_id'    => $rC->id,
        'second_rating_id'   => $rB->id,
        'first_user_score'   => 0,
        'second_user_score'  => 0,
        'winner_rating_id'   => null,
        'loser_rating_id'    => null,
        'invitation_sent_at' => now(),
    ]);

    $this->service->rearrangePositions($league->id);

    expect($rB->fresh()->position)
        ->toBe(1)
        ->and($rA->fresh()->position)->toBe(2)
    ;
});

it('6) falls back to user.lastname ascending when all metrics equal', function () {
    /** @var League $league */
    $league = League::factory()->create();
    $a = User::factory()->create(['firstname' => 'Z', 'lastname' => 'Alpha']);
    $b = User::factory()->create(['firstname' => 'Y', 'lastname' => 'Beta']);

    Rating::create([
        'league_id' => $league->id, 'user_id' => $a->id, 'rating' => 1500, 'position' => 0, 'is_active' => true,
    ]);
    Rating::create([
        'league_id' => $league->id, 'user_id' => $b->id, 'rating' => 1500, 'position' => 0, 'is_active' => true,
    ]);

    // Никаких игр — все метрики 0
    $this->service->rearrangePositions($league->id);

    $ordered = Rating::where('league_id', $league->id)
        ->orderBy('position')
        ->with('user')
        ->get()
        ->pluck('user.lastname')
        ->toArray()
    ;

    expect($ordered)->toEqual(['Alpha', 'Beta']);
});

it('7) falls back to user.firstname ascending when lastnames equal', function () {
    /** @var League $league */
    $league = League::factory()->create();
    $a = User::factory()->create(['firstname' => 'Alice', 'lastname' => 'Smith']);
    $b = User::factory()->create(['firstname' => 'Bob', 'lastname' => 'Smith']);

    Rating::create([
        'league_id' => $league->id, 'user_id' => $a->id, 'rating' => 1500, 'position' => 0, 'is_active' => true,
    ]);
    Rating::create([
        'league_id' => $league->id, 'user_id' => $b->id, 'rating' => 1500, 'position' => 0, 'is_active' => true,
    ]);

    $this->service->rearrangePositions($league->id);

    $ordered = Rating::where('league_id', $league->id)
        ->orderBy('position')
        ->with('user')
        ->get()
        ->pluck('user.firstname')
        ->toArray()
    ;

    expect($ordered)->toEqual(['Alice', 'Bob']);
});
