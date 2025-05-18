<?php

use App\Auth\DataTransferObjects\LoginDTO;
use App\Auth\DataTransferObjects\LogoutDTO;
use App\Auth\DataTransferObjects\RegisterDTO;
use App\Auth\Repositories\AuthRepository;
use App\Auth\Services\AuthService;
use App\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private $mockRepository;

    /** @test */
    public function it_registers_new_user()
    {
        // Arrange
        $registerDTO = new RegisterDTO([
            'firstname' => 'John',
            'lastname'  => 'Doe',
            'email'     => 'john.doe@example.com',
            'phone'     => '123-456-7890',
            'password'  => 'password123',
        ]);

        // Use a token spy instead of mocking
        $tokenSpy = $this->spy(NewAccessToken::class);
        $tokenSpy->plainTextToken = 'test-token-123';

        $this->mockRepository
            ->shouldReceive('createToken')
            ->once()
            ->andReturn($tokenSpy)
        ;

        // Create the service with our mock repository
        $service = new AuthService($this->mockRepository);

        // Swap the Auth facade with our test implementation
        $this->swap('auth', new class {
            public function login($user, $remember = false)
            {
                return true;
            }
        });

        // Act
        $result = $service->register($registerDTO);

        // Assert
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertEquals('test-token-123', $result['token']);

        // Verify user was created in the database
        $this->assertDatabaseHas('users', [
            'firstname' => 'John',
            'lastname'  => 'Doe',
            'email'     => 'john.doe@example.com',
            'phone'     => '123-456-7890',
        ]);

        // Verify password was hashed
        $user = User::where('email', 'john.doe@example.com')->first();
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    /** @test */
    public function it_logs_in_user()
    {
        // Arrange
        $user = User::factory()->create([
            'email'    => 'user@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $loginDTO = new LoginDTO([
            'email'      => 'user@example.com',
            'password'   => 'correct-password',
            'deviceName' => 'web',
        ]);

        // Use a token spy instead of mocking
        $tokenSpy = $this->spy(NewAccessToken::class);
        $tokenSpy->plainTextToken = 'test-token-123';

        $this->mockRepository
            ->shouldReceive('createToken')
            ->once()
            ->andReturn($tokenSpy)
        ;

        // Create a mock auth implementation
        $authImpl = new class($user) {
            private $user;

            public function __construct($user)
            {
                $this->user = $user;
            }

            public function attempt($credentials, $remember)
            {
                return true;
            }

            public function user()
            {
                return $this->user;
            }
        };

        // Swap the Auth facade with our implementation
        $this->swap('auth', $authImpl);

        // Create the service with our mock repository
        $service = new AuthService($this->mockRepository);

        // Act
        $result = $service->login($loginDTO);

        // Assert
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertSame($user, $result['user']);
        $this->assertEquals('test-token-123', $result['token']);
    }

    /** @test */
    public function it_throws_exception_when_login_fails()
    {
        // Arrange
        $loginDTO = new LoginDTO([
            'email'      => 'user@example.com',
            'password'   => 'wrong-password',
            'deviceName' => 'web',
        ]);

        // Create a mock auth implementation that fails authentication
        $authImpl = new class {
            public function attempt($credentials, $remember)
            {
                return false;
            }
        };

        // Swap the Auth facade with our implementation
        $this->swap('auth', $authImpl);

        // Create the service with our mock repository
        $service = new AuthService($this->mockRepository);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $service->login($loginDTO);
    }

    /** @test */
    public function it_logs_out_user()
    {
        // Arrange
        $user = User::factory()->create();

        // Create a spy for the tokens relationship
        $tokensSpy = $this->spy('\Illuminate\Database\Eloquent\Relations\MorphMany');

        // Replace the tokens relationship on the user
        $user = $this->partialMock(User::class, function ($mock) use ($tokensSpy) {
            $mock->shouldReceive('tokens')->andReturn($tokensSpy);
        });

        $logoutDTO = new LogoutDTO([
            'deviceName' => 'web',
        ]);

        $this->mockRepository
            ->shouldReceive('invalidateToken')
            ->once()
            ->with($user, 'web')
        ;

        // Create mock implementations for Auth and Session
        $authImpl = new class {
            public function guard()
            {
                return new class {
                    public function logout()
                    {
                        // Do nothing in the test
                    }
                };
            }
        };

        $sessionImpl = new class {
            public function invalidate()
            {
                // Do nothing in the test
            }

            public function regenerateToken()
            {
                // Do nothing in the test
            }
        };

        // Swap the facades with our implementations
        $this->swap('auth', $authImpl);
        $this->swap('session', $sessionImpl);

        // Create the service with our mock repository
        $service = new AuthService($this->mockRepository);

        // Act
        $result = $service->logout($user, $logoutDTO);

        // Assert
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertTrue($result['success']);
        $this->assertEquals('Successfully logged out.', $result['message']);

        // Verify tokens were deleted
        $tokensSpy->shouldHaveReceived('delete');
    }

    /** @test */
    public function it_handles_mobile_device_logout()
    {
        // Arrange
        $user = User::factory()->create();

        $logoutDTO = new LogoutDTO([
            'deviceName' => 'mobile-device',
        ]);

        $this->mockRepository
            ->shouldReceive('invalidateToken')
            ->once()
            ->with($user, 'mobile-device')
        ;

        // Create the service with our mock repository
        $service = new AuthService($this->mockRepository);

        // Act
        $result = $service->logout($user, $logoutDTO);

        // Assert
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertTrue($result['success']);
        $this->assertEquals('Successfully logged out.', $result['message']);
    }

    /** @test */
    public function it_handles_logout_exception()
    {
        // Arrange
        $user = User::factory()->create();

        $logoutDTO = new LogoutDTO([
            'deviceName' => 'web',
        ]);

        $this->mockRepository
            ->shouldReceive('invalidateToken')
            ->once()
            ->andThrow(new Exception('Test exception'))
        ;

        // Create the service with our mock repository
        $service = new AuthService($this->mockRepository);

        // Act
        $result = $service->logout($user, $logoutDTO);

        // Assert
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertFalse($result['success']);
        $this->assertEquals('An error occurred during logout, but your session has been terminated.',
            $result['message']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Create a partial mock of the repository
        $this->mockRepository = $this->partialMock(AuthRepository::class);
    }
}
