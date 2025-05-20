<?php

use App\Core\Models\Game;
use App\Core\Models\User;
use App\Leagues\Models\League;
use App\Leagues\Models\Rating;
use App\Matches\Enums\GameStatus;
use App\Matches\Models\MatchGame;
use App\User\Services\UserStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new UserStatsService();
});

it('gets user ratings across all leagues', function () {
    // Create a user
    $user = User::factory()->create();

    // Create multiple leagues
    $leagues = League::factory()->count(3)->create();
    $ratings = [];

    // Create ratings in each league for the user
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

    // Get user ratings
    $userRatings = $this->service->getUserRatings($user);

    expect($userRatings)
        ->toHaveCount(3)
        ->and($userRatings->pluck('id')->toArray())->toContain($ratings[0]->id, $ratings[1]->id, $ratings[2]->id)
        ->and($userRatings->pluck('rating')->toArray())
        ->toContain(1000, 1100, 1200)
    ;
});

it('gets match history for a user', function () {
    // Create a user
    $user = User::factory()->create();
    $opponent = User::factory()->create();

    // Create a league
    $league = League::factory()->create();

    // Create ratings for both users
    $userRating = Rating::create([
        'league_id'    => $league->id,
        'user_id'      => $user->id,
        'rating'       => 1000,
        'position'     => 1,
        'is_active'    => true,
        'is_confirmed' => true,
    ]);

    $opponentRating = Rating::create([
        'league_id'    => $league->id,
        'user_id'      => $opponent->id,
        'rating'       => 1100,
        'position'     => 2,
        'is_active'    => true,
        'is_confirmed' => true,
    ]);

    // Create matches in different statuses
    $mustBeConfirmedMatch = MatchGame::create([
        'game_id'            => Game::factory()->create()->id,
        'league_id'          => $league->id,
        'first_rating_id'    => $userRating->id,
        'second_rating_id'   => $opponentRating->id,
        'status'             => GameStatus::MUST_BE_CONFIRMED,
        'invitation_sent_at' => now()->subDay(),
        'first_user_score'   => 5,
        'second_user_score'  => 3,
    ]);

    $inProgressMatch = MatchGame::create([
        'game_id'            => Game::factory()->create()->id,
        'league_id'          => $league->id,
        'first_rating_id'    => $userRating->id,
        'second_rating_id'   => $opponentRating->id,
        'status'             => GameStatus::IN_PROGRESS,
        'invitation_sent_at' => now()->subDays(2),
    ]);

    $completedMatch = MatchGame::create([
        'game_id'            => Game::factory()->create()->id,
        'league_id'          => $league->id,
        'first_rating_id'    => $userRating->id,
        'second_rating_id'   => $opponentRating->id,
        'status'             => GameStatus::COMPLETED,
        'winner_rating_id'   => $userRating->id,
        'loser_rating_id'    => $opponentRating->id,
        'invitation_sent_at' => now()->subDays(3),
        'finished_at'        => now()->subDay(),
    ]);

    // Create a match that the user isn't part of
    MatchGame::create([
        'game_id'            => Game::factory()->create()->id,
        'league_id'          => $league->id,
        'first_rating_id'    => Rating::factory()->create()->id,
        'second_rating_id'   => Rating::factory()->create()->id,
        'status'             => GameStatus::COMPLETED,
        'invitation_sent_at' => now()->subDays(3),
        'finished_at'        => now()->subDay(),
    ]);

    // Get user match history
    $userMatches = $this->service->getUserMatches($user);

    expect($userMatches)
        ->toHaveCount(3)
        // Ordered by status priority then date
        ->and($userMatches[0]->id)->toBe($mustBeConfirmedMatch->id)
        ->and($userMatches[1]->id)->toBe($inProgressMatch->id)
        ->and($userMatches[2]->id)->toBe($completedMatch->id)
    ;
});

it('gets overall statistics for a user', function () {
    // Create a user
    $user = User::factory()->create();
    $opponent = User::factory()->create();

    // Create leagues
    $leagues = League::factory()->count(2)->create();
    $userRatings = [];
    $opponentRatings = [];

    // Create ratings in each league
    foreach ($leagues as $i => $league) {
        $userRatings[] = Rating::create([
            'league_id'    => $league->id,
            'user_id'      => $user->id,
            'rating'       => 1000 + ($i * 100),
            'position'     => 1,
            'is_active'    => true,
            'is_confirmed' => true,
        ]);

        $opponentRatings[] = Rating::create([
            'league_id'    => $league->id,
            'user_id'      => $opponent->id,
            'rating'       => 1100 + ($i * 100),
            'position'     => 2,
            'is_active'    => true,
            'is_confirmed' => true,
        ]);
    }

    // Create completed matches
    // League 1: User has 2 wins, 1 loss
    MatchGame::create([
        'game_id'            => Game::factory()->create()->id,
        'league_id'          => $leagues[0]->id,
        'first_rating_id'    => $userRatings[0]->id,
        'second_rating_id'   => $opponentRatings[0]->id,
        'status'             => GameStatus::COMPLETED,
        'winner_rating_id'   => $userRatings[0]->id,
        'loser_rating_id'    => $opponentRatings[0]->id,
        'invitation_sent_at' => now()->subDays(3),
        'finished_at'        => now()->subDay(),
    ]);

    MatchGame::create([
        'game_id'            => Game::factory()->create()->id,
        'league_id'          => $leagues[0]->id,
        'first_rating_id'    => $userRatings[0]->id,
        'second_rating_id'   => $opponentRatings[0]->id,
        'status'             => GameStatus::COMPLETED,
        'winner_rating_id'   => $userRatings[0]->id,
        'loser_rating_id'    => $opponentRatings[0]->id,
        'invitation_sent_at' => now()->subDays(3),
        'finished_at'        => now()->subDay(),
    ]);

    MatchGame::create([
        'game_id'            => Game::factory()->create()->id,
        'league_id'          => $leagues[0]->id,
        'first_rating_id'    => $userRatings[0]->id,
        'second_rating_id'   => $opponentRatings[0]->id,
        'status'             => GameStatus::COMPLETED,
        'winner_rating_id'   => $opponentRatings[0]->id,
        'loser_rating_id'    => $userRatings[0]->id,
        'invitation_sent_at' => now()->subDays(3),
        'finished_at'        => now()->subDay(),
    ]);

    // League 2: User has 1 win, 0 losses, 1 in progress
    MatchGame::create([
        'game_id'            => Game::factory()->create()->id,
        'league_id'          => $leagues[1]->id,
        'first_rating_id'    => $userRatings[1]->id,
        'second_rating_id'   => $opponentRatings[1]->id,
        'status'             => GameStatus::COMPLETED,
        'winner_rating_id'   => $userRatings[1]->id,
        'loser_rating_id'    => $opponentRatings[1]->id,
        'invitation_sent_at' => now()->subDays(3),
        'finished_at'        => now()->subDay(),
    ]);

    MatchGame::create([
        'game_id'            => Game::factory()->create()->id,
        'league_id'          => $leagues[1]->id,
        'first_rating_id'    => $userRatings[1]->id,
        'second_rating_id'   => $opponentRatings[1]->id,
        'status'             => GameStatus::IN_PROGRESS,
        'invitation_sent_at' => now()->subDays(3),
    ]);

    // Get user stats
    $stats = $this->service->getUserStats($user);

    expect($stats['total_matches'])
        ->toBe(5) // All matches including in_progress
        ->and($stats['completed_matches'])->toBe(4) // Only completed matches
        ->and($stats['wins'])->toBe(3) // 2 in league 1, 1 in league 2
        ->and($stats['losses'])->toBe(1) // 1 in league 1
        ->and($stats['win_rate'])->toBe(75.0) // 3 wins out of 4 completed = 75%
        ->and($stats['leagues_count'])->toBe(2)
        ->and($stats['highest_rating'])->toBe(1100) // Highest rating in league 2
        ->and($stats['average_rating'])->toBe(1050.0)
    ; // Average of 1000 and 1100
});

it('gets game type statistics for a user', function () {
    // Create a user
    $user = User::factory()->create();
    $opponent = User::factory()->create();

    // Create games of different types
    $poolGame = Game::factory()->create(['type' => 'pool']);
    $snookerGame = Game::factory()->create(['type' => 'snooker']);

    // Create leagues for each game type
    $poolLeague = League::factory()->create(['game_id' => $poolGame->id]);
    $snookerLeague = League::factory()->create(['game_id' => $snookerGame->id]);

    // Create ratings for the user in each league
    $poolRating = Rating::create([
        'league_id'    => $poolLeague->id,
        'user_id'      => $user->id,
        'rating'       => 1000,
        'position'     => 1,
        'is_active'    => true,
        'is_confirmed' => true,
    ]);

    $snookerRating = Rating::create([
        'league_id'    => $snookerLeague->id,
        'user_id'      => $user->id,
        'rating'       => 1100,
        'position'     => 1,
        'is_active'    => true,
        'is_confirmed' => true,
    ]);

    // Create ratings for the opponent
    $opponentPoolRating = Rating::create([
        'league_id'    => $poolLeague->id,
        'user_id'      => $opponent->id,
        'rating'       => 1050,
        'position'     => 2,
        'is_active'    => true,
        'is_confirmed' => true,
    ]);

    $opponentSnookerRating = Rating::create([
        'league_id'    => $snookerLeague->id,
        'user_id'      => $opponent->id,
        'rating'       => 1150,
        'position'     => 2,
        'is_active'    => true,
        'is_confirmed' => true,
    ]);

    // Create matches
    // Pool: 2 wins, 1 loss
    MatchGame::create([
        'game_id'            => $poolGame->id,
        'league_id'          => $poolLeague->id,
        'first_rating_id'    => $poolRating->id,
        'second_rating_id'   => $opponentPoolRating->id,
        'status'             => GameStatus::COMPLETED,
        'winner_rating_id'   => $poolRating->id,
        'loser_rating_id'    => $opponentPoolRating->id,
        'invitation_sent_at' => now()->subDays(3),
        'finished_at'        => now()->subDay(),
    ]);

    MatchGame::create([
        'game_id'            => $poolGame->id,
        'league_id'          => $poolLeague->id,
        'first_rating_id'    => $poolRating->id,
        'second_rating_id'   => $opponentPoolRating->id,
        'status'             => GameStatus::COMPLETED,
        'winner_rating_id'   => $poolRating->id,
        'loser_rating_id'    => $opponentPoolRating->id,
        'invitation_sent_at' => now()->subDays(3),
        'finished_at'        => now()->subDay(),
    ]);

    MatchGame::create([
        'game_id'            => $poolGame->id,
        'league_id'          => $poolLeague->id,
        'first_rating_id'    => $poolRating->id,
        'second_rating_id'   => $opponentPoolRating->id,
        'status'             => GameStatus::COMPLETED,
        'winner_rating_id'   => $opponentPoolRating->id,
        'loser_rating_id'    => $poolRating->id,
        'invitation_sent_at' => now()->subDays(3),
        'finished_at'        => now()->subDay(),
    ]);

    // Snooker: 1 win, 2 losses
    MatchGame::create([
        'game_id'            => $snookerGame->id,
        'league_id'          => $snookerLeague->id,
        'first_rating_id'    => $snookerRating->id,
        'second_rating_id'   => $opponentSnookerRating->id,
        'status'             => GameStatus::COMPLETED,
        'winner_rating_id'   => $snookerRating->id,
        'loser_rating_id'    => $opponentSnookerRating->id,
        'invitation_sent_at' => now()->subDays(3),
        'finished_at'        => now()->subDay(),
    ]);

    MatchGame::create([
        'game_id'            => $snookerGame->id,
        'league_id'          => $snookerLeague->id,
        'first_rating_id'    => $snookerRating->id,
        'second_rating_id'   => $opponentSnookerRating->id,
        'status'             => GameStatus::COMPLETED,
        'winner_rating_id'   => $opponentSnookerRating->id,
        'loser_rating_id'    => $snookerRating->id,
        'invitation_sent_at' => now()->subDays(3),
        'finished_at'        => now()->subDay(),
    ]);

    MatchGame::create([
        'game_id'            => $snookerGame->id,
        'league_id'          => $snookerLeague->id,
        'first_rating_id'    => $snookerRating->id,
        'second_rating_id'   => $opponentSnookerRating->id,
        'status'             => GameStatus::COMPLETED,
        'winner_rating_id'   => $opponentSnookerRating->id,
        'loser_rating_id'    => $snookerRating->id,
        'invitation_sent_at' => now()->subDays(3),
        'finished_at'        => now()->subDay(),
    ]);

    // Get game type stats
    $gameTypeStats = $this->service->getGameTypeStats($user);

    expect($gameTypeStats)
        ->toHaveCount(2)
        ->and(array_keys($gameTypeStats))->toContain('pool', 'snooker')
        ->and($gameTypeStats['pool']['matches'])->toBe(3)
        ->and($gameTypeStats['pool']['wins'])->toBe(2)
        ->and($gameTypeStats['pool']['losses'])->toBe(1)
        ->and($gameTypeStats['pool']['win_rate'])->toBe(67.0) // 2/3 = 67%
        ->and($gameTypeStats['snooker']['matches'])->toBe(3)
        ->and($gameTypeStats['snooker']['wins'])->toBe(1)
        ->and($gameTypeStats['snooker']['losses'])->toBe(2)
        ->and($gameTypeStats['snooker']['win_rate'])->toBe(33.0)
    ; // 1/3 = 33%
});

it('handles user with no ratings', function () {
    // Create a user with no ratings
    $user = User::factory()->create();

    // Get stats for user with no ratings
    $stats = $this->service->getUserStats($user);

    expect($stats['total_matches'])
        ->toBe(0)
        ->and($stats['completed_matches'])->toBe(0)
        ->and($stats['wins'])->toBe(0)
        ->and($stats['losses'])->toBe(0)
        ->and($stats['win_rate'])->toBe(0)
        ->and($stats['leagues_count'])->toBe(0)
        ->and($stats['highest_rating'])->toBe(0)
        ->and($stats['average_rating'])->toBe(0)
    ;

    // Get ratings (should be empty)
    $userRatings = $this->service->getUserRatings($user);
    expect($userRatings)->toHaveCount(0);

    // Get matches (should be empty)
    $userMatches = $this->service->getUserMatches($user);
    expect($userMatches)->toHaveCount(0);

    // Get game type stats (should be empty)
    $gameTypeStats = $this->service->getGameTypeStats($user);
    expect($gameTypeStats)->toBeEmpty();
});
