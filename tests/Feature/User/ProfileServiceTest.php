<?php

namespace Tests\Feature\User;

use App\Core\Models\User;
use App\User\Http\Requests\UpdatePasswordRequest;
use App\User\Services\ProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProfileServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProfileService $service;

    #[Test] public function it_updates_user_password(): void
    {
        // Arrange
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $passwordData = [
            'current_password' => 'old-password',
            'password'         => 'new-secure-password',
        ];

        $request = $this->mock(UpdatePasswordRequest::class);
        $request
            ->expects('validated')
            ->andReturns($passwordData)
        ;

        // Act
        $result = $this->service->updatePassword($user, $request);

        // Assert
        $this->assertTrue($result);

        // Verify the password was updated
        $updatedUser = User::find($user->id);
        $this->assertTrue(Hash::check('new-secure-password', $updatedUser->password));
    }

    #[Test] public function it_deletes_user_account(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $result = $this->service->deleteAccount($user);

        // Assert
        $this->assertTrue($result);

        // Verify the user was soft deleted
        $updatedUser = User::find($user->id);

        $this->assertFalse($updatedUser->is_active);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ProfileService();
    }
}
