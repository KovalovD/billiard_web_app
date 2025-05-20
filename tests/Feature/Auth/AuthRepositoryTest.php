<?php

namespace Tests\Feature\Auth;

use App\Auth\Repositories\AuthRepository;
use App\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private AuthRepository $repository;

    /** @test */
    public function it_creates_token_for_user(): void
    {
        // Arrange
        $user = User::factory()->create();
        $deviceName = 'test-device';

        // Act
        $token = $this->repository->createToken($user, $deviceName);

        // Assert
        $this->assertInstanceOf(NewAccessToken::class, $token);
        $this->assertNotEmpty($token->plainTextToken);

        // Verify token was created in the database
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id'   => $user->id,
            'tokenable_type' => User::class,
            'name'           => $deviceName,
        ]);
    }

    /** @test */
    public function it_invalidates_only_specified_device_token(): void
    {
        // Arrange
        $user = User::factory()->create();
        $deviceName1 = 'device-1';
        $deviceName2 = 'device-2';

        // Create tokens for different devices
        $token1 = $user->createToken($deviceName1)->plainTextToken;
        $token2 = $user->createToken($deviceName2)->plainTextToken;

        // Verify both tokens exist
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id'   => $user->id,
            'tokenable_type' => User::class,
            'name'           => $deviceName1,
        ]);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id'   => $user->id,
            'tokenable_type' => User::class,
            'name'           => $deviceName2,
        ]);

        // Act - invalidate only device1 token
        $this->repository->invalidateToken($user, $deviceName1);

        // Assert - device1 token should be removed, device2 token should remain
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id'   => $user->id,
            'tokenable_type' => User::class,
            'name'           => $deviceName1,
        ]);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id'   => $user->id,
            'tokenable_type' => User::class,
            'name'           => $deviceName2,
        ]);
    }

    /** @test */
    public function it_creates_new_token_after_invalidating_existing(): void
    {
        // Arrange
        $user = User::factory()->create();
        $deviceName = 'test-device';

        // Create a token for the user
        $oldToken = $user->createToken($deviceName);

        // Get the token's ID for comparison
        $oldTokenId = PersonalAccessToken::where('tokenable_id', $user->id)
            ->where('name', $deviceName)
            ->first()
            ->id;

        // Act - create a new token (which should invalidate the old one)
        $newToken = $this->repository->createToken($user, $deviceName);

        // Assert
        $this->assertInstanceOf(NewAccessToken::class, $newToken);
        $this->assertNotEmpty($newToken->plainTextToken);

        // Get the new token's ID
        $newTokenId = PersonalAccessToken::where('tokenable_id', $user->id)
            ->where('name', $deviceName)
            ->first()
            ->id;

        // Verify old token was replaced
        $this->assertNotEquals($oldTokenId, $newTokenId);

        // Verify only one token exists for this device name
        $this->assertEquals(
            1,
            PersonalAccessToken::where('tokenable_id', $user->id)
                ->where('name', $deviceName)
                ->count(),
        );
    }

    /** @test */
    public function it_handles_invalidating_nonexistent_token(): void
    {
        // Arrange
        $user = User::factory()->create();
        $deviceName = 'nonexistent-device';

        // Act & Assert - should not throw an exception
        $this->repository->invalidateToken($user, $deviceName);

        // No assertions needed, we're just making sure it doesn't throw an exception
        $this->addToAssertionCount(1);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new AuthRepository();
    }
}
