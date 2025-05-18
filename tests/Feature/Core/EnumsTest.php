<?php

use App\Leagues\Enums\RatingType;
use App\Matches\Enums\GameStatus;
use App\Matches\Enums\GameType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnumsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function game_type_enum_has_correct_values()
    {
        // Check the enum cases have the expected values
        $this->assertEquals('pool', GameType::Pool->value);
        $this->assertEquals('pyramid', GameType::Pyramid->value);
        $this->assertEquals('snooker', GameType::Snooker->value);

        // Check we can get all cases
        $allCases = [
            GameType::Pool,
            GameType::Pyramid,
            GameType::Snooker,
        ];

        foreach ($allCases as $case) {
            $this->assertInstanceOf(GameType::class, $case);
        }
    }

    /** @test */
    public function game_status_enum_has_correct_values()
    {
        // Check the enum cases have the expected values
        $this->assertEquals('pending', GameStatus::PENDING->value);
        $this->assertEquals('in_progress', GameStatus::IN_PROGRESS->value);
        $this->assertEquals('completed', GameStatus::COMPLETED->value);
        $this->assertEquals('must_be_confirmed', GameStatus::MUST_BE_CONFIRMED->value);

        // Check we can get all cases
        $allCases = [
            GameStatus::PENDING,
            GameStatus::IN_PROGRESS,
            GameStatus::COMPLETED,
            GameStatus::MUST_BE_CONFIRMED,
        ];

        foreach ($allCases as $case) {
            $this->assertInstanceOf(GameStatus::class, $case);
        }
    }

    /** @test */
    public function game_status_has_not_allowed_to_invite_statuses_method()
    {
        $notAllowedStatuses = GameStatus::notAllowedToInviteStatuses();

        $this->assertIsArray($notAllowedStatuses);
        $this->assertContains(GameStatus::PENDING, $notAllowedStatuses);
        $this->assertContains(GameStatus::IN_PROGRESS, $notAllowedStatuses);
        $this->assertContains(GameStatus::MUST_BE_CONFIRMED, $notAllowedStatuses);
        $this->assertNotContains(GameStatus::COMPLETED, $notAllowedStatuses);
    }

    /** @test */
    public function rating_type_enum_has_correct_values()
    {
        // Check the enum cases have the expected values
        $this->assertEquals('elo', RatingType::Elo->value);
        $this->assertEquals('killer_pool', RatingType::KillerPool->value);

        // Check we can get all cases
        $allCases = [
            RatingType::Elo,
            RatingType::KillerPool,
        ];

        foreach ($allCases as $case) {
            $this->assertInstanceOf(RatingType::class, $case);
        }
    }

    /** @test */
    public function can_perform_enum_comparisons()
    {
        // Game types
        $this->assertTrue(GameType::Pool === GameType::Pool);
        $this->assertFalse(GameType::Pool === GameType::Snooker);

        // Game statuses
        $this->assertTrue(GameStatus::PENDING === GameStatus::PENDING);
        $this->assertFalse(GameStatus::PENDING === GameStatus::IN_PROGRESS);

        // Rating types
        $this->assertTrue(RatingType::Elo === RatingType::Elo);
        $this->assertFalse(RatingType::Elo === RatingType::KillerPool);
    }

    /** @test */
    public function can_use_enum_values_in_conditions()
    {
        // Test with GameType
        $gameType = GameType::Pool;

        $result = match ($gameType) {
            GameType::Pool => 'pool',
            GameType::Pyramid => 'pyramid',
            GameType::Snooker => 'snooker',
        };

        $this->assertEquals('pool', $result);

        // Test with GameStatus
        $gameStatus = GameStatus::IN_PROGRESS;

        $result = match ($gameStatus) {
            GameStatus::PENDING => 'pending',
            GameStatus::IN_PROGRESS => 'in_progress',
            GameStatus::COMPLETED => 'completed',
            GameStatus::MUST_BE_CONFIRMED => 'must_be_confirmed',
        };

        $this->assertEquals('in_progress', $result);

        // Test with RatingType
        $ratingType = RatingType::KillerPool;

        $result = match ($ratingType) {
            RatingType::Elo => 'elo',
            RatingType::KillerPool => 'killer_pool',
        };

        $this->assertEquals('killer_pool', $result);
    }

    /** @test */
    public function can_create_enum_from_database_string()
    {
        // Simulate how Laravel casts enum values from database strings
        $gameType = GameType::from('snooker');
        $gameStatus = GameStatus::from('must_be_confirmed');
        $ratingType = RatingType::from('elo');

        $this->assertEquals(GameType::Snooker, $gameType);
        $this->assertEquals(GameStatus::MUST_BE_CONFIRMED, $gameStatus);
        $this->assertEquals(RatingType::Elo, $ratingType);
    }

    /** @test */
    public function enums_can_be_converted_to_strings()
    {
        $gameType = GameType::Pyramid;
        $gameStatus = GameStatus::COMPLETED;
        $ratingType = RatingType::KillerPool;

        $this->assertEquals('pyramid', $gameType->value);
        $this->assertEquals('completed', $gameStatus->value);
        $this->assertEquals('killer_pool', $ratingType->value);
    }

    /** @test */
    public function invalid_enum_values_throw_exceptions()
    {
        $this->expectException(ValueError::class);
        GameType::from('invalid_game_type');

        $this->expectException(ValueError::class);
        GameStatus::from('invalid_status');

        $this->expectException(ValueError::class);
        RatingType::from('invalid_rating_type');
    }
}
