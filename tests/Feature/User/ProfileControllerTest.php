<?php

namespace Tests\Feature\User;

use App\Core\Models\User;
use App\User\Http\Controllers\ProfileController;
use App\User\Http\Requests\UpdatePasswordRequest;
use App\User\Http\Requests\UpdateProfileRequest;
use App\User\Services\ProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Session\Store;
use Illuminate\Validation\UnauthorizedException;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    private $controller;
    private $profileService;
    private $authMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock service
        $this->profileService = $this->mock(ProfileService::class);
        $this->controller = new ProfileController($this->profileService);

        // Create local auth mock
        $this->authMock = Mockery::mock('alias:Illuminate\Support\Facades\Auth');
    }

    protected function tearDown(): void
    {
        // Clean up mockery instances
        $this->profileService = null;
        $this->authMock = null;
        $this->controller = null;

        parent::tearDown();
    }

    #[Test] public function it_updates_profile(): void
    {
        // Arrange
        $user = User::factory()->create();
        $updatedUser = User::factory()->make([
            'id'        => $user->id,
            'firstname' => 'Updated',
            'lastname'  => 'User',
        ]);

        // Use the local auth mock
        $this->authMock->allows('user')->andReturn($user);

        $request = $this->mock(UpdateProfileRequest::class);

        $this->profileService
            ->expects('updateProfile')
            ->with($user, $request)
            ->andReturns($updatedUser)
        ;

        // Act
        $result = $this->controller->update($request);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());
    }

    #[Test] public function it_updates_password(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Use the local auth mock
        $this->authMock->allows('user')->andReturn($user);

        $request = $this->mock(UpdatePasswordRequest::class);

        $this->profileService
            ->expects('updatePassword')
            ->with($user, $request)
            ->andReturns(true)
        ;

        // Act
        $result = $this->controller->updatePassword($request);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());
    }

    #[Test] public function it_deletes_account(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Use the local auth mock
        $this->authMock->allows('user')->andReturn($user);

        // Create mock request
        $request = Mockery::mock(Request::class);
        $request
            ->expects('validate')
            ->andReturns(['password' => 'correct-password'])
        ;

        // Create mock session
        $session = Mockery::mock(Store::class);
        $session->expects('invalidate');
        $session->expects('regenerateToken');

        $request
            ->allows('session')
            ->andReturns($session)
        ;

        // Stub auth logout
        $this->authMock->expects('logout');

        $this->profileService
            ->expects('deleteAccount')
            ->with($user)
            ->andReturns(true)
        ;

        // Act
        $result = $this->controller->destroy($request);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());
    }

    #[Test] public function it_throws_exception_when_destroying_unauthenticated(): void
    {
        // Arrange
        // Mock Auth facade returning null (unauthenticated)
        $this->authMock->allows('user')->andReturn(null);

        $request = Mockery::mock(Request::class);
        $request
            ->expects('validate')
            ->andReturns(['password' => 'password123'])
        ;

        // Act & Assert
        $this->expectException(UnauthorizedException::class);
        $this->controller->destroy($request);
    }
}
