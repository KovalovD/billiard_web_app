<?php

use App\Auth\DataTransferObjects\LoginDTO;
use App\Auth\DataTransferObjects\LogoutDTO;
use App\Auth\DataTransferObjects\RegisterDTO;
use App\Auth\Http\Controllers\AuthController;
use App\Auth\Services\AuthServiceInterface;
use App\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    private $controller;
    private $stubAuthService;

    /** @test */
    public function it_logs_in_user()
    {
        // Arrange
        $user = User::factory()->create();
        $token = 'test-token-123';

        // Use PHP's ReflectionClass to create a request without Mockery
        $request = new class {
            public function fromRequest()
            {
                return new LoginDTO([
                    'email'      => 'user@example.com',
                    'password'   => 'password',
                    'deviceName' => 'web',
                ]);
            }
        };

        $this->stubAuthService->loginResult = [
            'user'  => $user,
            'token' => $token,
        ];

        // Act
        $result = $this->controller->login($request);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());

        $data = json_decode($result->getContent(), true);
        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('token', $data);
        $this->assertEquals($token, $data['token']);
    }

    /** @test */
    public function it_handles_login_validation_exception()
    {
        // Arrange
        $request = new class {
            public function fromRequest()
            {
                return new LoginDTO([
                    'email'      => 'user@example.com',
                    'password'   => 'wrong-password',
                    'deviceName' => 'web',
                ]);
            }
        };

        $this->stubAuthService->loginException = ValidationException::withMessages([
            'email' => ['These credentials do not match our records.'],
        ]);

        // Act
        $result = $this->controller->login($request);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(422, $result->status());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Invalid credentials', $data['message']);
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('email', $data['errors']);
    }

    /** @test */
    public function it_registers_new_user()
    {
        // Arrange
        $user = User::factory()->create();
        $token = 'test-token-123';

        $request = new class {
            public function fromRequest()
            {
                return new RegisterDTO([
                    'firstname' => 'John',
                    'lastname'  => 'Doe',
                    'email'     => 'john.doe@example.com',
                    'phone'     => '123-456-7890',
                    'password'  => 'password123',
                ]);
            }
        };

        $this->stubAuthService->registerResult = [
            'user'  => $user,
            'token' => $token,
        ];

        // Act
        $result = $this->controller->register($request);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());

        $data = json_decode($result->getContent(), true);
        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('token', $data);
        $this->assertEquals($token, $data['token']);
    }

    /** @test */
    public function it_handles_registration_validation_exception()
    {
        // Arrange
        $request = new class {
            public function fromRequest()
            {
                return new RegisterDTO([
                    'firstname' => 'John',
                    'lastname'  => 'Doe',
                    'email'     => 'invalid-email',
                    'phone'     => '123-456-7890',
                    'password'  => 'password123',
                ]);
            }
        };

        $this->stubAuthService->registerException = ValidationException::withMessages([
            'email' => ['The email must be a valid email address.'],
        ]);

        // Act
        $result = $this->controller->register($request);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(422, $result->status());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Validation failed', $data['message']);
        $this->assertArrayHasKey('errors', $data);
    }

    /** @test */
    public function it_logs_out_user()
    {
        // Arrange
        $user = User::factory()->create();

        $request = new class {
            public function fromRequest()
            {
                return new LogoutDTO([
                    'deviceName' => 'web',
                ]);
            }
        };

        $this->stubAuthService->logoutResult = [
            'success' => true,
            'message' => 'Successfully logged out.',
        ];

        // Set the authenticated user for this test
        $this->actingAs($user);

        // Act
        $result = $this->controller->logout($request);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());

        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('Successfully logged out.', $data['message']);
    }

    /** @test */
    public function it_handles_logout_with_no_user()
    {
        // Arrange
        $request = new class {
            public function fromRequest()
            {
                return new LogoutDTO([
                    'deviceName' => 'web',
                ]);
            }
        };

        // Ensure no user is authenticated
        $this->app['auth']->forgetGuards();

        // Act
        $result = $this->controller->logout($request);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(401, $result->status());

        $data = json_decode($result->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('No authenticated user found', $data['message']);
    }

    /** @test */
    public function it_gets_authenticated_user()
    {
        // Arrange
        $user = User::factory()->create();

        // Set the authenticated user for this test
        $this->actingAs($user);

        // Act
        $result = $this->controller->user();

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());

        // UserResource should contain the user's data
        $data = json_decode($result->getContent(), true);
        $this->assertEquals($user->id, $data['id']);
        $this->assertEquals($user->email, $data['email']);
    }

    /** @test */
    public function it_returns_error_when_getting_user_without_auth()
    {
        // Ensure no user is authenticated
        $this->app['auth']->forgetGuards();

        // Act
        $result = $this->controller->user();

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(401, $result->status());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Unauthenticated', $data['error']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Create a stub implementation that doesn't use Mockery
        $this->stubAuthService = new class implements AuthServiceInterface {
            public $loginResult;
            public $registerResult;
            public $logoutResult;
            public $loginException;
            public $registerException;

            public function login(LoginDTO $loginDTO): array
            {
                if (isset($this->loginException)) {
                    throw $this->loginException;
                }
                return $this->loginResult ?? [];
            }

            public function register(RegisterDTO $registerDTO): array
            {
                if (isset($this->registerException)) {
                    throw $this->registerException;
                }
                return $this->registerResult ?? [];
            }

            public function logout(User $user, LogoutDTO $logoutDTO): array
            {
                return $this->logoutResult ?? [];
            }
        };

        // Create the controller with our stub service
        $this->controller = new AuthController($this->stubAuthService);

        // Clear any lingering authentication
        $this->app['auth']->forgetGuards();
    }
}
