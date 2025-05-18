<?php

use App\Core\DataTransferObjects\BaseDTO;
use App\Core\Models\Club;
use App\Core\Models\User;
use App\Leagues\DataTransferObjects\PutLeagueDTO;
use App\Leagues\DataTransferObjects\SendGameDTO;
use App\Leagues\DataTransferObjects\SendResultDTO;
use App\Leagues\Models\League;
use App\Matches\Models\MatchGame;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataTransferObjectsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function base_dto_can_be_created_from_array()
    {
        // Create a test DTO that extends BaseDTO
        $testDTO = new class(['name' => 'Test', 'value' => 123]) extends BaseDTO {
            public string $name;
            public int $value;
        };

        $this->assertEquals('Test', $testDTO->name);
        $this->assertEquals(123, $testDTO->value);
    }

    /** @test */
    public function base_dto_can_be_converted_to_array()
    {
        // Create a test DTO
        $testDTO = new class(['name' => 'Test', 'value' => 123, 'private' => 'hidden']) extends BaseDTO {
            public string $name;
            public int $value;
            private string $private;
        };

        $array = $testDTO->toArray();

        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('value', $array);
        $this->assertEquals('Test', $array['name']);
        $this->assertEquals(123, $array['value']);
        $this->assertArrayNotHasKey('private', $array); // Private properties should not be included
    }

    /** @test */
    public function base_dto_can_exclude_properties_when_converting_to_array()
    {
        // Create a test DTO
        $testDTO = new class(['name' => 'Test', 'value' => 123, 'extra' => 'data']) extends BaseDTO {
            public string $name;
            public int $value;
            public string $extra;
        };

        $array = $testDTO->toArray(['extra']);

        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayNotHasKey('extra', $array); // Should be excluded
    }

    /** @test */
    public function base_dto_can_be_created_from_request()
    {
        // Create a mock FormRequest
        $request = $this->mock(FormRequest::class);
        $request
            ->shouldReceive('validated')
            ->once()
            ->andReturn(['name' => 'Test', 'value' => 123])
        ;

        // Create the DTO from the request
        $testDTO = new class extends BaseDTO {
            public string $name;
            public int $value;

            public static function fromRequest(FormRequest $request): static
            {
                return parent::fromRequest($request);
            }
        };

        $result = $testDTO::fromRequest($request);

        $this->assertEquals('Test', $result->name);
        $this->assertEquals(123, $result->value);
    }

    /** @test */
    public function put_league_dto_prepares_data_correctly()
    {
        // Test date values
        $startDate = '2023-01-01';
        $endDate = '2023-12-31';

        // Test winner/loser rules
        $winnerRules = json_encode([
            ['range' => [0, 50], 'strong' => 25, 'weak' => 25],
            ['range' => [51, 100], 'strong' => 20, 'weak' => 30],
        ]);

        $loserRules = json_encode([
            ['range' => [0, 50], 'strong' => -25, 'weak' => -25],
            ['range' => [51, 100], 'strong' => -20, 'weak' => -30],
        ]);

        // Prepare input data
        $inputData = [
            'name'                           => 'Test League',
            'game_id'                        => 1,
            'picture'                        => 'https://example.com/image.jpg',
            'details'                        => 'Test details',
            'has_rating'                     => '1', // String value that should be converted to boolean
            'started_at'                     => $startDate,
            'finished_at'                    => $endDate,
            'start_rating'                   => 1000,
            'rating_change_for_winners_rule' => $winnerRules,
            'rating_change_for_losers_rule'  => $loserRules,
            'max_players'                    => 16,
            'max_score'                      => 7,
            'invite_days_expire'             => 2,
        ];

        // Prepare data using the DTO's static method
        $preparedData = PutLeagueDTO::prepareDataBeforeCreation($inputData);

        // Assert date conversion
        $this->assertInstanceOf(Carbon::class, $preparedData['started_at']);
        $this->assertInstanceOf(Carbon::class, $preparedData['finished_at']);
        $this->assertEquals($startDate, $preparedData['started_at']->format('Y-m-d'));
        $this->assertEquals($endDate, $preparedData['finished_at']->format('Y-m-d'));

        // Assert boolean conversion
        $this->assertTrue($preparedData['has_rating']);

        // Assert JSON string preservation
        $this->assertJson($preparedData['rating_change_for_winners_rule']);
        $this->assertJson($preparedData['rating_change_for_losers_rule']);
    }

    /** @test */
    public function send_game_dto_handles_all_properties()
    {
        // Create test objects
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $league = League::factory()->create();
        $club = Club::factory()->create();

        // Create DTO
        $dto = new SendGameDTO([
            'sender'     => $sender,
            'receiver'   => $receiver,
            'league'     => $league,
            'stream_url' => 'https://example.com/stream',
            'details'    => 'Test match details',
            'club_id'    => $club->id,
        ]);

        // Assert properties were set correctly
        $this->assertSame($sender, $dto->sender);
        $this->assertSame($receiver, $dto->receiver);
        $this->assertSame($league, $dto->league);
        $this->assertEquals('https://example.com/stream', $dto->stream_url);
        $this->assertEquals('Test match details', $dto->details);
        $this->assertEquals($club->id, $dto->club_id);
    }

    /** @test */
    public function send_result_dto_handles_all_properties()
    {
        // Create test objects
        $matchGame = MatchGame::factory()->create();

        // Create DTO
        $dto = new SendResultDTO([
            'first_user_score'  => 5,
            'second_user_score' => 3,
            'matchGame'         => $matchGame,
        ]);

        // Assert properties were set correctly
        $this->assertEquals(5, $dto->first_user_score);
        $this->assertEquals(3, $dto->second_user_score);
        $this->assertSame($matchGame, $dto->matchGame);
    }

    /** @test */
    public function dto_ignores_unknown_properties()
    {
        // Create a test DTO with unknown properties in the input
        $testDTO = new class(['name' => 'Test', 'unknown' => 'value']) extends BaseDTO {
            public string $name;
            // No property for 'unknown'
        };

        // The unknown property should be ignored
        $this->assertEquals('Test', $testDTO->name);
        $this->assertFalse(isset($testDTO->unknown));
    }
}
