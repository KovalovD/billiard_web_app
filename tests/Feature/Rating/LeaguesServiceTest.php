<?php

use App\Core\Models\Game;
use App\Core\Models\User;
use App\Leagues\Models\League;
use App\Leagues\Models\Rating;
use App\Leagues\Services\LeaguesService;
use App\Leagues\Services\RatingService;
use App\Matches\Enums\GameStatus;
use App\Matches\Models\MatchGame;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ratingService = new RatingService();
    $this->leaguesService = new LeaguesService();
});

it('fetches leagues.index.page correctly', function () {
    // Create test leagues
    $leagues = League::factory()->count(3)->create();

    // Fetch all leagues
    $result = $this->leaguesService->index();

    expect($result)
        ->toHaveCount(3)
        ->and($result->pluck('id')->all())->toEqual($leagues->pluck('id')->all())
    ;
});

it('stores a new league', function () {
    // Create a game to associate with the league
    $game = Game::factory()->create();

    // Create test data
    $leagueDTO = new App\Leagues\DataTransferObjects\PutLeagueDTO([
        'name'                           => 'Test League',
        'game_id'                        => $game->id,
        'picture'                        => 'https://example.com/image.jpg',
        'details'                        => 'League description',
        'has_rating'                     => true,
        'started_at'                     => now(),
        'finished_at'                    => now()->addMonths(3),
        'start_rating'                   => 1000,
        'rating_change_for_winners_rule' => json_encode([
            ['range' => [0, 100], 'strong' => 20, 'weak' => 30],
        ], JSON_THROW_ON_ERROR),
        'rating_change_for_losers_rule'  => json_encode([
            ['range' => [0, 100], 'strong' => -20, 'weak' => -30],
        ], JSON_THROW_ON_ERROR),
        'max_players'                    => 16,
        'max_score'                      => 7,
        'invite_days_expire'             => 2,
    ]);

    // Store the league
    $league = $this->leaguesService->store($leagueDTO);

    expect($league)->not
        ->toBeNull()
        ->and($league->name)->toBe('Test League')
        ->and($league->game_id)->toBe($game->id)
        ->and($league->max_players)->toBe(16)
        ->and($league->max_score)->toBe(7)
        ->and($league->has_rating)->toBeTrue()
        ->and($league->rating_type->value)->toBe('elo')
    ;

    // Verify league was stored in database
    $storedLeague = League::find($league->id);
    expect($storedLeague)->not->toBeNull();
});

it('updates an existing league', function () {
    // Create a league to update
    $league = League::factory()->create([
        'name'        => 'Original Name',
        'max_players' => 8,
        'max_score'   => 5,
        'invite_days_expire' => 3,
    ]);

    // Create update data
    $leagueDTO = new App\Leagues\DataTransferObjects\PutLeagueDTO([
        'name'                           => 'Updated Name',
        'game_id'                        => $league->game_id,
        'picture'                        => $league->picture,
        'details'                        => 'Updated details',
        'has_rating'                     => $league->has_rating,
        'started_at'                     => $league->started_at,
        'finished_at'                    => $league->finished_at,
        'start_rating'                   => $league->start_rating,
        'rating_change_for_winners_rule' => $league->rating_change_for_winners_rule,
        'rating_change_for_losers_rule'  => $league->rating_change_for_losers_rule,
        'max_players'                    => 16, // Updated
        'max_score'                      => 10,   // Updated
        'invite_days_expire' => 4, // Must be provided
    ]);

    // Update the league
    $updatedLeague = $this->leaguesService->update($leagueDTO, $league);

    expect($updatedLeague->name)
        ->toBe('Updated Name')
        ->and($updatedLeague->max_players)->toBe(16)
        ->and($updatedLeague->max_score)->toBe(10)
        ->and($updatedLeague->details)->toBe('Updated details')
        ->and($updatedLeague->invite_days_expire)->toBe(4)
    ;

    // Verify database was updated
    $refreshedLeague = League::find($league->id);
    expect($refreshedLeague->name)->toBe('Updated Name');
});

it('deletes a league', function () {
    // Create a league to delete
    $league = League::factory()->create();

    // Delete the league
    $this->leaguesService->destroy($league);

    // Verify the league was soft deleted
    expect(League::find($league->id))
        ->toBeNull()
        ->and(League::withTrashed()->find($league->id))->not->toBeNull();
});

it('gets games for a league', function () {
    // Create a league and a game
    $game = Game::factory()->create();
    $league = League::factory()->create(['game_id' => $game->id]);

    // Create users with ratings
    $users = User::factory()->count(2)->create();
    $ratings = [];

    foreach ($users as $i => $user) {
        $ratings[] = Rating::create([
            'league_id'    => $league->id,
            'user_id'      => $user->id,
            'rating'       => 1000 + ($i * 100),
            'position'     => $i + 1,
            'is_active'    => true,
            'is_confirmed' => true,
        ]);
    }

    // Create matches in the league
    $completedMatch = MatchGame::create([
        'game_id'            => $game->id,
        'league_id'          => $league->id,
        'first_rating_id'    => $ratings[0]->id,
        'second_rating_id'   => $ratings[1]->id,
        'status'             => GameStatus::COMPLETED,
        'first_user_score'   => 5,
        'second_user_score'  => 3,
        'winner_rating_id'   => $ratings[0]->id,
        'loser_rating_id'    => $ratings[1]->id,
        'invitation_sent_at' => now()->subDay(),
        'finished_at'        => now(),
    ]);

    $inProgressMatch = MatchGame::create([
        'game_id'            => $game->id,
        'league_id'          => $league->id,
        'first_rating_id'    => $ratings[0]->id,
        'second_rating_id'   => $ratings[1]->id,
        'status'             => GameStatus::IN_PROGRESS,
        'invitation_sent_at' => now(),
    ]);

    // Get games for the league
    $games = $this->leaguesService->games($league);

    expect($games)
        ->toHaveCount(2)
        // IN_PROGRESS should be first (priority order)
        ->and($games[0]->id)->toBe($inProgressMatch->id)
        ->and($games[1]->id)->toBe($completedMatch->id)
    ;
});

it('gets user leagues and challenges', function () {
    // Create a user
    $user = User::factory()->create();

    // Create multiple leagues
    $leagues = League::factory()->count(2)->create();
    $ratings = [];

    // Create active ratings for the user in each league
    foreach ($leagues as $i => $league) {
        $ratings[] = Rating::create([
            'league_id'    => $league->id,
            'user_id'      => $user->id,
            'rating'       => 1000 + ($i * 100),
            'position'     => $i + 1,
            'is_active'    => true,
            'is_confirmed' => true,
        ]);
    }

    // Create a match in the first league
    $match = MatchGame::create([
        'game_id'            => $leagues[0]->game_id,
        'league_id'          => $leagues[0]->id,
        'first_rating_id'    => $ratings[0]->id,
        'second_rating_id'   => Rating::factory()->create([
            'league_id'    => $leagues[0]->id,
            'user_id'      => User::factory()->create()->id,
            'is_active'    => true,
            'is_confirmed' => true,
        ])->id,
        'status'             => GameStatus::IN_PROGRESS,
        'invitation_sent_at' => now(),
    ]);

    // Get user leagues and challenges
    $result = $this->leaguesService->myLeaguesAndChallenges($user);

    expect($result)
        ->toHaveCount(2) // 2 leagues
        ->and(array_keys($result))->toContain($leagues[0]->id, $leagues[1]->id)
        ->and($result[$leagues[0]->id]['league']->id)->toBe($leagues[0]->id)
        ->and($result[$leagues[0]->id]['rating']->id)->toBe($ratings[0]->id)
        ->and($result[$leagues[0]->id]['activeMatches'])->toHaveCount(1)
        ->and($result[$leagues[1]->id]['activeMatches'])->toHaveCount(0)
    ;
});
