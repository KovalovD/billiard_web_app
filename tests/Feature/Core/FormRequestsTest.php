<?php

use App\Auth\Http\Requests\LoginRequest;
use App\Auth\Http\Requests\LogoutRequest;
use App\Auth\Http\Requests\RegisterRequest;
use App\Core\Http\Requests\BaseFormRequest;
use App\Core\Models\User;
use App\Leagues\Http\Requests\PutLeagueRequest;
use App\Leagues\Http\Requests\SendGameRequest;
use App\Leagues\Http\Requests\SendResultRequest;
use App\Matches\Http\Requests\CreateMultiplayerGameRequest;
use App\User\Http\Requests\UpdatePasswordRequest;
use App\User\Http\Requests\UpdateProfileRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Validator;
use Tests\TestCase;

class FormRequestsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function base_form_request_handles_failed_validation()
    {
        // Create a mock validator
        $validator = $this->mock(Validator::class);
        $validator
            ->shouldReceive('errors')
            ->once()
            ->andReturn([
                'field' => ['The field is required.'],
            ])
        ;

        // Create a concrete implementation of BaseFormRequest for testing
        $request = new class extends BaseFormRequest {
            public function rules(): array
            {
                return [
                    'field' => 'required',
                ];
            }

            // Expose protected method for testing
            public function testFailedValidation(Validator $validator): void
            {
                $this->failedValidation($validator);
            }
        };

        // Act & Assert
        $this->expectException(HttpResponseException::class);
        $request->testFailedValidation($validator);
    }

    /** @test */
    public function login_request_has_expected_rules()
    {
        $request = new LoginRequest();

        $rules = $request->rules();

        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('password', $rules);
        $this->assertArrayHasKey('deviceName', $rules);

        $this->assertEquals(['required', 'string', 'email'], $rules['email']);
        $this->assertEquals(['required', 'string'], $rules['password']);
        $this->assertEquals(['sometimes', 'string'], $rules['deviceName']);
    }

    /** @test */
    public function login_request_prepares_devicename_from_header()
    {
        $request = new LoginRequest();
        $request->headers->set('User-Agent', 'Test User Agent');

        // Use reflection to access protected method
        $method = new ReflectionMethod(LoginRequest::class, 'prepareForValidation');
        $method->setAccessible(true);
        $method->invoke($request);

        $this->assertEquals('Test User Agent', $request->deviceName);
    }

    /** @test */
    public function logout_request_has_expected_rules()
    {
        $request = new LogoutRequest();

        $rules = $request->rules();

        $this->assertArrayHasKey('deviceName', $rules);
        $this->assertEquals(['sometimes', 'string'], $rules['deviceName']);
    }

    /** @test */
    public function logout_request_prepares_devicename_from_header()
    {
        $request = new LogoutRequest();
        $request->headers->set('User-Agent', 'Test User Agent');

        // Use reflection to access protected method
        $method = new ReflectionMethod(LogoutRequest::class, 'prepareForValidation');
        $method->setAccessible(true);
        $method->invoke($request);

        $this->assertEquals('Test User Agent', $request->deviceName);
    }

    /** @test */
    public function register_request_has_expected_rules()
    {
        $request = new RegisterRequest();

        $rules = $request->rules();

        $this->assertArrayHasKey('firstname', $rules);
        $this->assertArrayHasKey('lastname', $rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('phone', $rules);
        $this->assertArrayHasKey('password', $rules);

        $this->assertEquals(['required', 'string', 'max:255'], $rules['firstname']);
        $this->assertEquals(['required', 'string', 'max:255'], $rules['lastname']);
        $this->assertContains('required', $rules['email']);
        $this->assertContains('email', $rules['email']);
        $this->assertContains('unique:'.User::class, $rules['email']);
        $this->assertContains('required', $rules['phone']);
        $this->assertContains('confirmed', $rules['password']);
    }

    /** @test */
    public function put_league_request_has_expected_rules()
    {
        $request = new PutLeagueRequest();

        $rules = $request->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('game_id', $rules);
        $this->assertArrayHasKey('has_rating', $rules);
        $this->assertArrayHasKey('start_rating', $rules);
        $this->assertArrayHasKey('max_players', $rules);
        $this->assertArrayHasKey('max_score', $rules);
        $this->assertArrayHasKey('invite_days_expire', $rules);

        $this->assertEquals(['required', 'string', 'max:255'], $rules['name']);
        $this->assertEquals(['required', 'integer', 'min:0'], $rules['start_rating']);
        $this->assertEquals(['required', 'integer', 'min:0'], $rules['max_players']);
        $this->assertEquals(['required', 'integer', 'min:0'], $rules['max_score']);
        $this->assertEquals(['required', 'integer', 'min:1'], $rules['invite_days_expire']);
    }

    /** @test */
    public function put_league_request_handles_zero_max_players()
    {
        $request = new PutLeagueRequest();

        // Mock the request with max_players = 0
        $request->merge(['max_players' => 0]);

        // Use reflection to access protected method
        $method = new ReflectionMethod(PutLeagueRequest::class, 'prepareForValidation');
        $method->setAccessible(true);
        $method->invoke($request);

        // 0 means unlimited, so it should stay as 0
        $this->assertEquals(0, $request->max_players);
    }

    /** @test */
    public function send_game_request_has_expected_rules()
    {
        $request = new SendGameRequest();

        $rules = $request->rules();

        $this->assertArrayHasKey('stream_url', $rules);
        $this->assertArrayHasKey('details', $rules);
        $this->assertArrayHasKey('club_id', $rules);

        $this->assertEquals(['nullable', 'url', 'max:2048'], $rules['stream_url']);
        $this->assertEquals(['nullable', 'string', 'max:1000'], $rules['details']);
        $this->assertEquals(['nullable', 'integer', 'exists:clubs,id'], $rules['club_id']);
    }

    /** @test */
    public function send_result_request_has_expected_rules()
    {
        $request = new SendResultRequest();

        $rules = $request->rules();

        $this->assertArrayHasKey('first_user_score', $rules);
        $this->assertArrayHasKey('second_user_score', $rules);

        $this->assertEquals(['required', 'integer'], $rules['first_user_score']);
        $this->assertEquals(['required', 'integer'], $rules['second_user_score']);
    }

    /** @test */
    public function create_multiplayer_game_request_has_expected_rules()
    {
        $request = new CreateMultiplayerGameRequest();

        $rules = $request->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('max_players', $rules);
        $this->assertArrayHasKey('registration_ends_at', $rules);
        $this->assertArrayHasKey('allow_player_targeting', $rules);
        $this->assertArrayHasKey('entrance_fee', $rules);
        $this->assertArrayHasKey('first_place_percent', $rules);
        $this->assertArrayHasKey('second_place_percent', $rules);
        $this->assertArrayHasKey('grand_final_percent', $rules);

        $this->assertEquals(['required', 'string', 'max:255'], $rules['name']);
        $this->assertEquals(['nullable', 'integer', 'min:2', 'max:24'], $rules['max_players']);
        $this->assertEquals(['nullable', 'date', 'after:now'], $rules['registration_ends_at']);
        $this->assertEquals(['nullable', 'boolean'], $rules['allow_player_targeting']);
        $this->assertEquals(['nullable', 'integer', 'min:0'], $rules['entrance_fee']);
    }

    /** @test */
    public function create_multiplayer_game_request_corrects_prize_percentages()
    {
        $request = new CreateMultiplayerGameRequest();

        // Mock the request with percentages that don't add up to 100%
        $request->merge([
            'first_place_percent'  => 50,
            'second_place_percent' => 20,
            'grand_final_percent'  => 10, // Total: 80%
        ]);

        // Use reflection to access protected method
        $method = new ReflectionMethod(CreateMultiplayerGameRequest::class, 'prepareForValidation');
        $method->setAccessible(true);
        $method->invoke($request);

        // Should be corrected to default values that add up to 100%
        $this->assertEquals(60, $request->first_place_percent);
        $this->assertEquals(20, $request->second_place_percent);
        $this->assertEquals(20, $request->grand_final_percent);
    }

    /** @test */
    public function update_password_request_has_expected_rules()
    {
        $request = new UpdatePasswordRequest();

        $rules = $request->rules();

        $this->assertArrayHasKey('current_password', $rules);
        $this->assertArrayHasKey('password', $rules);

        $this->assertEquals(['required', 'current_password'], $rules['current_password']);
        $this->assertContains('required', $rules['password']);
        $this->assertContains('confirmed', $rules['password']);
    }

    /** @test */
    public function update_profile_request_has_expected_rules()
    {
        $request = new UpdateProfileRequest();

        $rules = $request->rules();

        $this->assertArrayHasKey('firstname', $rules);
        $this->assertArrayHasKey('lastname', $rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('phone', $rules);
        $this->assertArrayHasKey('home_city_id', $rules);
        $this->assertArrayHasKey('home_club_id', $rules);

        $this->assertEquals(['required', 'string', 'max:255'], $rules['firstname']);
        $this->assertEquals(['required', 'string', 'max:255'], $rules['lastname']);
        $this->assertContains('required', $rules['email']);
        $this->assertContains('email', $rules['email']);
        $this->assertContains('required', $rules['phone']);
        $this->assertEquals(['nullable', 'exists:cities,id'], $rules['home_city_id']);
        $this->assertEquals(['nullable', 'exists:clubs,id'], $rules['home_club_id']);
    }
}
