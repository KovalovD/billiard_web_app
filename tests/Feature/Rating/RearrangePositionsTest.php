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
        'rating'    => 2000, 'position' => 0,
    ]);
    Rating::create([
        'league_id' => $league->id, 'user_id' => $weak->id,
        'rating'    => 1000, 'position' => 0,
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

    $rA = Rating::create(['league_id' => $league->id, 'user_id' => $a->id, 'rating' => 1500, 'position' => 0]);
    $rB = Rating::create(['league_id' => $league->id, 'user_id' => $b->id, 'rating' => 1500, 'position' => 0]);

    // A выигрывает один матч, B — ни одного
    MatchGame::create([
        'league_id'         => $league->id,
        'first_rating_id'   => $rA->id,
        'second_rating_id'  => $rB->id,
        'first_user_score'  => 5,
        'second_user_score' => 3,
        'winner_rating_id'  => $rA->id,
        'loser_rating_id'   => $rB->id,
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

    $rA = Rating::create(['league_id' => $league->id, 'user_id' => $a->id, 'rating' => 1500, 'position' => 0]);
    $rB = Rating::create(['league_id' => $league->id, 'user_id' => $b->id, 'rating' => 1500, 'position' => 0]);

    // ни у кого нет побед → wins_count = 0
    // A: проигрыш 3–5 → diff = 3 – 5 = -2
    MatchGame::create([
        'league_id'         => $league->id,
        'first_rating_id'   => $rA->id,
        'second_rating_id'  => $rB->id + 100, // не B, чисто для записи
        'first_user_score'  => 3,
        'second_user_score' => 5,
        'winner_rating_id'  => null,
        'loser_rating_id'   => null,
    ]);
    // B: проигрыш 1–5 → diff = 1 – 5 = -4
    MatchGame::create([
        'league_id'         => $league->id,
        'first_rating_id'   => $rB->id,
        'second_rating_id'  => $rA->id + 100,
        'first_user_score'  => 1,
        'second_user_score' => 5,
        'winner_rating_id'  => null,
        'loser_rating_id'   => null,
    ]);

    $this->service->rearrangePositions($league->id);

    expect($rA->fresh()->position)
        ->toBe(1)
        ->and($rB->fresh()->position)->toBe(2)
    ;
});

it('3) breaks tie by frames_won descending when previous metrics equal', function () {
    /** @var League $league */
    $league = League::factory()->create();
    $a = User::factory()->create(['firstname' => 'A', 'lastname' => 'Alpha']);
    $b = User::factory()->create(['firstname' => 'B', 'lastname' => 'Beta']);

    $rA = Rating::create(['league_id' => $league->id, 'user_id' => $a->id, 'rating' => 1500, 'position' => 0]);
    $rB = Rating::create(['league_id' => $league->id, 'user_id' => $b->id, 'rating' => 1500, 'position' => 0]);

    // обеих по одной победе и по одному поражению (diff=0 у обоих)
    // A: выиграл 7–5, проиграл 5–7 → frames_won = 7+5 = 12
    MatchGame::insert([
        [
            'league_id'         => $league->id,
            'first_rating_id'   => $rA->id,
            'second_rating_id'  => $rB->id,
            'first_user_score'  => 7,
            'second_user_score' => 5,
            'winner_rating_id'  => $rA->id,
            'loser_rating_id'   => $rB->id,
        ],
        [
            'league_id'         => $league->id,
            'first_rating_id'   => $rA->id,
            'second_rating_id'  => $rB->id,
            'first_user_score'  => 5,
            'second_user_score' => 7,
            'winner_rating_id'  => $rB->id,
            'loser_rating_id'   => $rA->id,
        ],
    ]);
    // B: выиграл 6–4, проиграл 4–6 → frames_won = 6+4 = 10
    MatchGame::insert([
        [
            'league_id'         => $league->id,
            'first_rating_id'   => $rB->id,
            'second_rating_id'  => $rA->id,
            'first_user_score'  => 6,
            'second_user_score' => 4,
            'winner_rating_id'  => $rB->id,
            'loser_rating_id'   => $rA->id,
        ],
        [
            'league_id'         => $league->id,
            'first_rating_id'   => $rB->id,
            'second_rating_id'  => $rA->id,
            'first_user_score'  => 4,
            'second_user_score' => 6,
            'winner_rating_id'  => $rA->id,
            'loser_rating_id'   => $rB->id,
        ],
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

    $rA = Rating::create(['league_id' => $league->id, 'user_id' => $a->id, 'rating' => 1500, 'position' => 0]);
    $rB = Rating::create(['league_id' => $league->id, 'user_id' => $b->id, 'rating' => 1500, 'position' => 0]);
    $rC = Rating::create(['league_id' => $league->id, 'user_id' => $c->id, 'rating' => 1500, 'position' => 0]);

    // Два первых матча между A и B дают wins_count=1, diff=0, frames_won=10
    MatchGame::insert([
        [
            'league_id'         => $league->id,
            'first_rating_id'   => $rA->id,
            'second_rating_id'  => $rB->id,
            'first_user_score'  => 6,
            'second_user_score' => 4,
            'winner_rating_id'  => $rA->id,
            'loser_rating_id'   => $rB->id,
        ],
        [
            'league_id'         => $league->id,
            'first_rating_id'   => $rB->id,
            'second_rating_id'  => $rA->id,
            'first_user_score'  => 6,
            'second_user_score' => 4,
            'winner_rating_id'  => $rB->id,
            'loser_rating_id'   => $rA->id,
        ],
    ]);
    // Дополнительный "пустой" матч C→B, нулевые очки и без победителя,
    // чтобы увеличить matches_count только для B
    MatchGame::create([
        'league_id'         => $league->id,
        'first_rating_id'   => $rC->id,
        'second_rating_id'  => $rB->id,
        'first_user_score'  => 0,
        'second_user_score' => 0,
        'winner_rating_id'  => null,
        'loser_rating_id'   => null,
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

    Rating::create(['league_id' => $league->id, 'user_id' => $a->id, 'rating' => 1500, 'position' => 0]);
    Rating::create(['league_id' => $league->id, 'user_id' => $b->id, 'rating' => 1500, 'position' => 0]);

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

    Rating::create(['league_id' => $league->id, 'user_id' => $a->id, 'rating' => 1500, 'position' => 0]);
    Rating::create(['league_id' => $league->id, 'user_id' => $b->id, 'rating' => 1500, 'position' => 0]);

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
