<?php

use App\Core\Models\User;
use App\User\Http\Controllers\ProfileController;
use App\User\Http\Requests\UpdatePasswordRequest;
use App\User\Http\Requests\UpdateProfileRequest;
use App\User\Services\ProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\UnauthorizedException;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    private ProfileController $controller;
    private ProfileService $mockProfileService;

    /** @test */
    public function it_updates_profile()
    {
        // Arrange
        $user = User::factory()->create();
        $updatedUser = User::factory()->make([
            'id'        => $user->id,
            'firstname' => 'Updated',
            'lastname'  => 'User',
        ]);

        $this->mockAuth::shouldReceive('user')
            ->once()
            ->andReturn($user)
        ;

        $request = $this->mock(UpdateProfileRequest::class);

        $this->mockProfileService
            ->shouldReceive('updateProfile')
            ->once()
            ->with($user, $request)
            ->andReturn($updatedUser)
        ;

        // Act
        $result = $this->controller->update($request);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Updated', $data['firstname']);
        $this->assertEquals('User', $data['lastname']);
    }

    /** @test */
    public function it_updates_password()
    {
        // Arrange
        $user = User::factory()->create();

        $this->mockAuth::shouldReceive('user')
            ->once()
            ->andReturn($user)
        ;

        $request = $this->mock(UpdatePasswordRequest::class);

        $this->mockProfileService
            ->shouldReceive('updatePassword')
            ->once()
            ->with($user, $request)
            ->andReturn(true)
        ;

        // Act
        $result = $this->controller->updatePassword($request);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Password updated successfully.', $data['message']);
    }

    /** @test */
    public function it_deletes_account()
    {
        // Arrange
        $user = User::factory()->create();

        $this->mockAuth::shouldReceive('user')
            ->twice()
            ->andReturn($user)
        ;

        $this->mockAuth::shouldReceive('logout')
            ->once()
        ;

        $request = $this->mock(\Illuminate\Http\Request::class);
        $request
            ->shouldReceive('validate')
            ->once()
            ->with(['password' => ['required', 'current_password']])
            ->andReturn(true)
        ;

        $request
            ->shouldReceive('session->invalidate')
            ->once()
        ;

        $request
            ->shouldReceive('session->regenerateToken')
            ->once()
        ;

        $this->mockProfileService
            ->shouldReceive('deleteAccount')
            ->once()
            ->with($user)
            ->andReturn(true)
        ;

        // Act
        $result = $this->controller->destroy($request);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->status());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Account deleted successfully.', $data['message']);
    }

    /** @test */
    public function it_throws_exception_when_destroying_unauthenticated()
    {
        // Arrange
        $this->mockAuth::shouldReceive('user')
            ->once()
            ->andReturn(null)
        ;

        $request = $this->mock(\Illuminate\Http\Request::class);
        $request
            ->shouldReceive('validate')
            ->once()
            ->with(['password' => ['required', 'current_password']])
            ->andReturn(true)
        ;

        // Act & Assert
        $this->expectException(UnauthorizedException::class);
        $this->controller->destroy($request);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockProfileService = $this->mock(ProfileService::class);
        $this->controller = new ProfileController($this->mockProfileService);

        // Mock Auth facade
        $this->mockAuth = $this->mock('Illuminate\Support\Facades\Auth');
    }
}
