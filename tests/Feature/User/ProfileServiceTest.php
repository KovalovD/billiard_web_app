<?php

use App\Core\Models\City;
use App\Core\Models\Club;
use App\Core\Models\User;
use App\User\Http\Requests\UpdatePasswordRequest;
use App\User\Http\Requests\UpdateProfileRequest;
use App\User\Services\ProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProfileService $service;

    /** @test */
    public function it_updates_user_profile()
    {
        // Arrange
        $user = User::factory()->create([
            'firstname' => 'John',
            'lastname'  => 'Doe',
            'email'     => 'john.doe@example.com',
            'phone'     => '123-456-7890',
        ]);

        $updatedData = [
            'firstname'    => 'Jane',
            'lastname'     => 'Smith',
            'email'        => 'jane.smith@example.com',
            'phone'        => '987-654-3210',
            'home_city_id' => null,
            'home_club_id' => null,
        ];

        $request = $this->mock(UpdateProfileRequest::class);
        $request
            ->shouldReceive('validated')
            ->once()
            ->andReturn($updatedData)
        ;

        // Act
        $updatedUser = $this->service->updateProfile($user, $request);

        // Assert
        $this->assertEquals('Jane', $updatedUser->firstname);
        $this->assertEquals('Smith', $updatedUser->lastname);
        $this->assertEquals('jane.smith@example.com', $updatedUser->email);
        $this->assertEquals('987-654-3210', $updatedUser->phone);
        $this->assertNull($updatedUser->email_verified_at); // Should be null since email changed
    }

    /** @test */
    public function it_keeps_email_verified_when_email_not_changed()
    {
        // Arrange
        $verifiedAt = now()->subDay();
        $user = User::factory()->create([
            'firstname'         => 'John',
            'lastname'          => 'Doe',
            'email'             => 'john.doe@example.com',
            'email_verified_at' => $verifiedAt,
            'phone'             => '123-456-7890',
        ]);

        $updatedData = [
            'firstname'    => 'Johnny',
            'lastname'     => 'Doe',
            'email'        => 'john.doe@example.com', // Same email
            'phone'        => '987-654-3210',
            'home_city_id' => null,
            'home_club_id' => null,
        ];

        $request = $this->mock(UpdateProfileRequest::class);
        $request
            ->shouldReceive('validated')
            ->once()
            ->andReturn($updatedData)
        ;

        // Act
        $updatedUser = $this->service->updateProfile($user, $request);

        // Assert
        $this->assertEquals('Johnny', $updatedUser->firstname);
        $this->assertEquals('987-654-3210', $updatedUser->phone);
        $this->assertEquals($verifiedAt->timestamp,
            $updatedUser->email_verified_at->timestamp); // Should remain the same
    }

    /** @test */
    public function it_updates_user_password()
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
            ->shouldReceive('validated')
            ->once()
            ->andReturn($passwordData)
        ;

        // Act
        $result = $this->service->updatePassword($user, $request);

        // Assert
        $this->assertTrue($result);

        // Verify the password was updated
        $updatedUser = User::find($user->id);
        $this->assertTrue(Hash::check('new-secure-password', $updatedUser->password));
    }

    /** @test */
    public function it_deletes_user_account()
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $result = $this->service->deleteAccount($user);

        // Assert
        $this->assertTrue($result);

        // Verify the user was soft deleted
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    /** @test */
    public function it_handles_city_and_club_references()
    {
        // Arrange
        $user = User::factory()->create();

        // Create city and club
        $city = City::factory()->create();
        $club = Club::factory()->create();

        $updatedData = [
            'firstname'    => $user->firstname,
            'lastname'     => $user->lastname,
            'email'        => $user->email,
            'phone'        => $user->phone,
            'home_city_id' => $city->id,
            'home_club_id' => $club->id,
        ];

        $request = $this->mock(UpdateProfileRequest::class);
        $request
            ->shouldReceive('validated')
            ->once()
            ->andReturn($updatedData)
        ;

        // Act
        $updatedUser = $this->service->updateProfile($user, $request);

        // Assert
        $this->assertEquals($city->id, $updatedUser->home_city_id);
        $this->assertEquals($club->id, $updatedUser->home_club_id);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ProfileService();
    }
}
