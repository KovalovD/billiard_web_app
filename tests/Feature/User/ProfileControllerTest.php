<?php

namespace Tests\Feature\User;

use App\Core\Models\User;
use App\User\Http\Controllers\ProfileController;
use App\User\Http\Requests\UpdatePasswordRequest;
use App\User\Http\Requests\UpdateProfileRequest;
use App\User\Services\ProfileService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Session\Store;
use Illuminate\Validation\UnauthorizedException;
use JsonException;
use PHPUnit\Framework\MockObject\Exception;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    private $controller;
    private $profileService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock service
        $this->profileService = $this->mock(ProfileService::class);
        $this->controller = new ProfileController($this->profileService);
    }

    /** @test
     * @throws JsonException|Exception
     */
    public function it_updates_profile(): void
    {
        // Arrange
        $user = User::factory()->create();
        $updatedUser = User::factory()->make([
            'id'        => $user->id,
            'firstname' => 'Updated',
            'lastname'  => 'User',
        ]);

        // Mock Auth facade with app container
        $this->app->instance('auth', $auth = $this->createMock(Guard::class));
        $auth->method('user')->willReturn($user);

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

        $data = json_decode($result->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('Updated', $data['firstname']);
        $this->assertEquals('User', $data['lastname']);
    }

    /** @test
     * @throws JsonException|Exception
     */
    public function it_updates_password(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Mock Auth facade with app container
        $this->app->instance('auth', $auth = $this->createMock(Guard::class));
        $auth->method('user')->willReturn($user);

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

        $data = json_decode($result->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('Password updated successfully', $data['message']);
    }

    /** @test
     * @throws JsonException|Exception
     */
    public function it_deletes_account(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Mock Auth facade with app container
        $this->app->instance('auth', $auth = $this->createMock(Guard::class));
        $auth->method('user')->willReturn($user);

        // Mock Auth with additional facade method
        $this->app->instance('auth.logout', function () {
            // Mock logout functionality
        });

        $request = $this->createMock(Request::class);
        $request->method('validate')->willReturn(true);

        // Mock session methods
        $sessionMock = $this->createMock(Store::class);
        $sessionMock->expects($this->once())->method('invalidate');
        $sessionMock->expects($this->once())->method('regenerateToken');

        $request->method('session')->willReturn($sessionMock);

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

        $data = json_decode($result->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('Account deleted successfully', $data['message']);
    }

    /** @test
     * @throws Exception
     */
    public function it_throws_exception_when_destroying_unauthenticated(): void
    {
        // Arrange
        // Mock Auth facade with app container returning null (unauthenticated)
        $this->app->instance('auth', $auth = $this->createMock(Guard::class));
        $auth->method('user')->willReturn(null);

        $request = $this->createMock(Request::class);
        $request->method('validate')->willReturn(true);

        // Act & Assert
        $this->expectException(UnauthorizedException::class);
        $this->controller->destroy($request);
    }
}
