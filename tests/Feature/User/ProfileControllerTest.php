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
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\UnauthorizedException;
use JsonException;
use Mockery;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    private $controller;
    private $profileService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create fresh mocks for each test
        $this->profileService = Mockery::mock(ProfileService::class);
        $this->controller = new ProfileController($this->profileService);
    }

    /** @test
     * @throws JsonException
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

        // Create a one-time Auth facade mock
        $auth = Mockery::mock(Auth::class);
        $auth
            ->expects('user')
            ->andReturns($user)
        ;
        $this->app->instance(Auth::class, $auth);

        $request = Mockery::mock(UpdateProfileRequest::class);

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
     * @throws JsonException
     */
    public function it_updates_password(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Create a one-time Auth facade mock
        $auth = Mockery::mock(Auth::class);
        $auth
            ->expects('user')
            ->andReturns($user)
        ;
        $this->app->instance(Auth::class, $auth);

        $request = Mockery::mock(UpdatePasswordRequest::class);

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
     * @throws JsonException
     */
    public function it_deletes_account(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Create a one-time Auth facade mock for the first call
        $auth = Mockery::mock(Auth::class);
        $auth
            ->expects('user')
            ->andReturns($user)
        ;
        $auth->expects('logout');
        $this->app->instance(Auth::class, $auth);

        $request = Mockery::mock(Request::class);
        $request
            ->expects('validate')
            ->with(['password' => ['required', 'current_password']])
            ->andReturns(true)
        ;

        $request
            ->expects('session->invalidate')
        ;

        $request
            ->expects('session->regenerateToken')
        ;

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

    /** @test */
    public function it_throws_exception_when_destroying_unauthenticated(): void
    {
        // Arrange
        // Create a one-time Auth facade mock
        $auth = Mockery::mock(Auth::class);
        $auth
            ->expects('user')
            ->andReturns(null)
        ;
        $this->app->instance(Auth::class, $auth);

        $request = Mockery::mock(Request::class);
        $request
            ->expects('validate')
            ->with(['password' => ['required', 'current_password']])
            ->andReturns(true)
        ;

        // Act & Assert
        $this->expectException(UnauthorizedException::class);
        $this->controller->destroy($request);
    }
}
