<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePictureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_profile_picture()
    {
        // Create a user and authenticate
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create a fake image file
        Storage::fake('public');
        $file = UploadedFile::fake()->image('avatar.jpg', 600, 600);

        // Send request to update avatar - Laravel handles CSRF automatically
        $response = $this->post('/profile/avatar', [
            'avatar' => $file,
        ]);

        // Assert the response redirects
        $response->assertRedirect(route('profile.show'));
        $response->assertSessionHas('success', 'Profile picture updated successfully.');

        // Refresh user data from database
        $user->refresh();

        // Assert the avatar was saved to database
        $this->assertNotNull($user->avatar);
        $this->assertStringContainsString('jpg', $user->avatar);

        // Assert the file was stored in the correct location
        Storage::disk('public')->assertExists('avatars/' . $user->avatar);
    }

    public function test_profile_picture_validation_fails_with_invalid_file()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create an invalid file (not an image)
        $file = UploadedFile::fake()->create('document.txt', 100, 'text/plain');

        $response = $this->post('/profile/avatar', [
            'avatar' => $file,
        ]);

        // Should redirect back with validation errors
        $response->assertRedirect();
        $response->assertSessionHasErrors('avatar');
    }

    public function test_profile_picture_validation_fails_with_large_file()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create a file larger than 2MB (max allowed)
        $file = UploadedFile::fake()->image('large_avatar.jpg', 1000, 1000)->size(3000); // 3MB

        $response = $this->post('/profile/avatar', [
            'avatar' => $file,
        ]);

        // Should redirect back with validation errors
        $response->assertRedirect();
        $response->assertSessionHasErrors('avatar');
    }

    public function test_user_can_see_profile_picture_on_profile_page()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Manually set an avatar for testing
        $user->update(['avatar' => 'test_avatar.jpg']);
        
        // Store a test file
        Storage::disk('public')->put('avatars/test_avatar.jpg', 'dummy content');

        $response = $this->get('/profile');

        $response->assertStatus(200);
        $response->assertSee('storage/avatars/test_avatar.jpg');
    }

    public function test_avatar_deletion_when_updating_to_new_one()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Storage::fake('public');
        
        // Upload first avatar
        $firstFile = UploadedFile::fake()->image('first_avatar.jpg', 600, 600);
        $this->post('/profile/avatar', [
            'avatar' => $firstFile
        ]);

        $user->refresh();
        $firstAvatar = $user->avatar;

        // Upload second avatar (this should delete the first one)
        $secondFile = UploadedFile::fake()->image('second_avatar.jpg', 600, 600);
        $this->post('/profile/avatar', [
            'avatar' => $secondFile
        ]);
        
        $user->refresh();
        $secondAvatar = $user->avatar;
        
        // First avatar should be deleted, second should exist
        Storage::disk('public')->assertMissing('avatars/' . $firstAvatar);
        Storage::disk('public')->assertExists('avatars/' . $secondAvatar);
    }

    public function test_avatar_field_is_updated_in_database()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Storage::fake('public');
        $file = UploadedFile::fake()->image('test_avatar.jpg', 600, 600);

        $this->post('/profile/avatar', [
            'avatar' => $file,
        ]);

        $user->refresh();

        // Check that the avatar field in the database has been updated
        $this->assertNotNull($user->avatar);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'avatar' => $user->avatar,
        ]);
    }
}