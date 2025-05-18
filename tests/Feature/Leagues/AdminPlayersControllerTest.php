<?php

use App\Core\Models\User;
use App\Leagues\Http\Controllers\AdminPlayersController;
use App\Leagues\Http\Resources\RatingResource;
use App\Leagues\Models\League;
use App\Leagues\Models\Rating;
use App\Leagues\Services\RatingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Tests\TestCase;

class AdminPlayersControllerTest extends TestCase
{
    use RefreshDatabase;

    private $controller;
    private $mockRatingService;

    /** @test */
    public function it_gets_pending_players()
    {
        // Arrange
        $league = League::factory()->create();
        $users = User::factory()->count(3)->create();
        $pendingRatings = collect();

        foreach ($users as $i => $user) {
            $pendingRatings->push(Rating::create([
                'league_id'    => $league->id,
                'user_id'      => $user->id,
                'rating'       => 1000,
                'position'     => $i + 1,
                'is_active'    => true,
                'is_confirmed' => false, // Pending players
            ]));
        }

        // Add a confirmed player that shouldn't be included
        Rating::create([
            'league_id'    => $league->id,
            'user_id'      => User::factory()->create()->id,
            'rating'       => 1000,
            'position'     => 4,
            'is_active'    => true,
            'is_confirmed' => true,
        ]);

        // Act
        $result = $this->controller->pendingPlayers($league);

        // Assert
        $this->assertInstanceOf(AnonymousResourceCollection::class, $result);
        $this->assertEquals(3, $result->count());
        $this->assertInstanceOf(RatingResource::class, $result[0]);
    }

    /** @test */
    public function it_confirms_player()
    {
        // Arrange
        $league = League::factory()->create();
        $user = User::factory()->create();

        $rating = Rating::create([
            'league_id'    => $league->id,
            'user_id'      => $user->id,
            'rating'       => 1000,
            'position'     => 1,
            'is_active'    => true,
            'is_confirmed' => false,
        ]);

        $this->mockRatingService
            ->shouldReceive('rearrangePositions')
            ->once()
            ->with($league->id)
        ;

        // Act
        $result = $this->controller->confirmPlayer($league, $rating);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());

        // Check if the rating was updated
        $this->assertEquals(true, $rating->fresh()->is_confirmed);
    }

    /** @test */
    public function it_rejects_player()
    {
        // Arrange
        $league = League::factory()->create();
        $user = User::factory()->create();

        $rating = Rating::create([
            'league_id'    => $league->id,
            'user_id'      => $user->id,
            'rating'       => 1000,
            'position'     => 1,
            'is_active'    => true,
            'is_confirmed' => false,
        ]);

        $this->mockRatingService
            ->shouldReceive('rearrangePositions')
            ->once()
            ->with($league->id)
        ;

        // Act
        $result = $this->controller->rejectPlayer($league, $rating);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());

        // Check if the rating was updated
        $updatedRating = $rating->fresh();
        $this->assertEquals(false, $updatedRating->is_active);
        $this->assertEquals(false, $updatedRating->is_confirmed);
    }

    /** @test */
    public function it_prevents_confirming_player_from_different_league()
    {
        // Arrange
        $league1 = League::factory()->create();
        $league2 = League::factory()->create();
        $user = User::factory()->create();

        $rating = Rating::create([
            'league_id'    => $league2->id, // Different league
            'user_id'      => $user->id,
            'rating'       => 1000,
            'position'     => 1,
            'is_active'    => true,
            'is_confirmed' => false,
        ]);

        // Act
        $result = $this->controller->confirmPlayer($league1, $rating);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(400, $result->status());
    }

    /** @test */
    public function it_bulk_confirms_players()
    {
        // Arrange
        $league = League::factory()->create();
        $users = User::factory()->count(3)->create();
        $ratingIds = [];

        foreach ($users as $i => $user) {
            $rating = Rating::create([
                'league_id'    => $league->id,
                'user_id'      => $user->id,
                'rating'       => 1000,
                'position'     => $i + 1,
                'is_active'    => true,
                'is_confirmed' => false,
            ]);
            $ratingIds[] = $rating->id;
        }

        $this->mockRatingService
            ->shouldReceive('rearrangePositions')
            ->once()
            ->with($league->id)
        ;

        $request = $this->mock(\Illuminate\Http\Request::class);
        $request
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['rating_ids' => $ratingIds])
        ;

        $request->rating_ids = $ratingIds;

        // Act
        $result = $this->controller->bulkConfirmPlayers($request, $league);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());

        // Check if ratings were updated
        foreach ($ratingIds as $id) {
            $this->assertEquals(true, Rating::find($id)->is_confirmed);
        }
    }

    /** @test */
    public function it_deactivates_confirmed_player()
    {
        // Arrange
        $league = League::factory()->create();
        $user = User::factory()->create();

        $rating = Rating::create([
            'league_id'    => $league->id,
            'user_id'      => $user->id,
            'rating'       => 1000,
            'position'     => 1,
            'is_active'    => true,
            'is_confirmed' => true,
        ]);

        $this->mockRatingService
            ->shouldReceive('rearrangePositions')
            ->once()
            ->with($league->id)
        ;

        // Act
        $result = $this->controller->deactivatePlayer($league, $rating);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());

        // Check if the rating was updated
        $updatedRating = $rating->fresh();
        $this->assertEquals(false, $updatedRating->is_active);
        $this->assertEquals(false, $updatedRating->is_confirmed);
    }

    /** @test */
    public function it_prevents_deactivating_already_inactive_player()
    {
        // Arrange
        $league = League::factory()->create();
        $user = User::factory()->create();

        $rating = Rating::create([
            'league_id'    => $league->id,
            'user_id'      => $user->id,
            'rating'       => 1000,
            'position'     => 1,
            'is_active'    => false, // Already inactive
            'is_confirmed' => false,
        ]);

        // Act
        $result = $this->controller->deactivatePlayer($league, $rating);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(400, $result->status());
    }

    /** @test */
    public function it_gets_confirmed_players()
    {
        // Arrange
        $league = League::factory()->create();
        $users = User::factory()->count(3)->create();
        $confirmedRatings = collect();

        foreach ($users as $i => $user) {
            $confirmedRatings->push(Rating::create([
                'league_id'    => $league->id,
                'user_id'      => $user->id,
                'rating'       => 1000,
                'position'     => $i + 1,
                'is_active'    => true,
                'is_confirmed' => true, // Confirmed players
            ]));
        }

        // Add a pending player that shouldn't be included
        Rating::create([
            'league_id'    => $league->id,
            'user_id'      => User::factory()->create()->id,
            'rating'       => 1000,
            'position'     => 4,
            'is_active'    => true,
            'is_confirmed' => false,
        ]);

        // Act
        $result = $this->controller->confirmedPlayers($league);

        // Assert
        $this->assertInstanceOf(AnonymousResourceCollection::class, $result);
        $this->assertEquals(3, $result->count());
        $this->assertInstanceOf(RatingResource::class, $result[0]);
    }

    /** @test */
    public function it_bulk_deactivates_players()
    {
        // Arrange
        $league = League::factory()->create();
        $users = User::factory()->count(3)->create();
        $ratingIds = [];

        foreach ($users as $i => $user) {
            $rating = Rating::create([
                'league_id'    => $league->id,
                'user_id'      => $user->id,
                'rating'       => 1000,
                'position'     => $i + 1,
                'is_active'    => true,
                'is_confirmed' => true,
            ]);
            $ratingIds[] = $rating->id;
        }

        $this->mockRatingService
            ->shouldReceive('rearrangePositions')
            ->once()
            ->with($league->id)
        ;

        $request = $this->mock(\Illuminate\Http\Request::class);
        $request
            ->shouldReceive('validate')
            ->once()
            ->andReturn(['rating_ids' => $ratingIds])
        ;

        $request->rating_ids = $ratingIds;

        // Act
        $result = $this->controller->bulkDeactivatePlayers($request, $league);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());

        // Check if ratings were updated
        foreach ($ratingIds as $id) {
            $updatedRating = Rating::find($id);
            $this->assertEquals(false, $updatedRating->is_active);
            $this->assertEquals(false, $updatedRating->is_confirmed);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRatingService = $this->mock(RatingService::class);
        $this->controller = new AdminPlayersController($this->mockRatingService);
    }
}
