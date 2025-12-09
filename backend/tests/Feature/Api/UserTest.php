<?php

namespace Tests\Feature\Api;

use App\Models\Users\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_get_their_profile()
    {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/user/profile');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'profile' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'full_name' => 'John Doe',
                ],
            ]);
    }

    /** @test */
    public function user_can_update_their_profile()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->putJson('/api/user/profile', [
                'first_name' => 'Updated',
                'last_name' => 'Name',
                'phone' => '+58412345678',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Perfil actualizado exitosamente',
                'profile' => [
                    'first_name' => 'Updated',
                    'last_name' => 'Name',
                    'phone' => '+58412345678',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Updated',
            'last_name' => 'Name',
        ]);
    }

    /** @test */
    public function user_can_change_their_password()
    {
        $user = User::factory()->create([
            'password' => bcrypt('oldpassword'),
        ]);

        $response = $this->actingAs($user)
            ->putJson('/api/user/password', [
                'current_password' => 'oldpassword',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Contraseña actualizada exitosamente',
            ]);

        // Verify new password works
        $this->assertTrue(
            \Hash::check('newpassword123', $user->fresh()->password)
        );
    }

    /** @test */
    public function user_cannot_change_password_with_wrong_current_password()
    {
        $user = User::factory()->create([
            'password' => bcrypt('oldpassword'),
        ]);

        $response = $this->actingAs($user)
            ->putJson('/api/user/password', [
                'current_password' => 'wrongpassword',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    /** @test */
    public function user_can_upload_avatar()
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->actingAs($user)
            ->postJson('/api/user/avatar', [
                'avatar' => $file,
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Avatar actualizado exitosamente',
            ])
            ->assertJsonStructure(['avatar_url']);

        // Verify file was stored
        $this->assertNotNull($user->fresh()->avatar);
        Storage::disk('public')->assertExists($user->fresh()->avatar);
    }

    /** @test */
    public function user_can_delete_avatar()
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'avatar' => 'avatars/test.jpg',
        ]);

        // Create fake file
        Storage::disk('public')->put('avatars/test.jpg', 'content');

        $response = $this->actingAs($user)
            ->deleteJson('/api/user/avatar');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Avatar eliminado exitosamente',
            ]);

        $this->assertNull($user->fresh()->avatar);
        Storage::disk('public')->assertMissing('avatars/test.jpg');
    }

    /** @test */
    public function user_can_get_their_preferences()
    {
        $user = User::factory()->create([
            'preferences' => [
                'theme' => 'dark',
                'language' => 'es',
                'notifications' => true,
            ],
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/user/preferences');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'preferences' => [
                    'theme' => 'dark',
                    'language' => 'es',
                    'notifications' => true,
                ],
            ]);
    }

    /** @test */
    public function user_can_update_their_preferences()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->putJson('/api/user/preferences', [
                'theme' => 'dark',
                'language' => 'en',
                'sound_enabled' => false,
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Preferencias actualizadas exitosamente',
                'preferences' => [
                    'theme' => 'dark',
                    'language' => 'en',
                    'sound_enabled' => false,
                ],
            ]);

        $preferences = $user->fresh()->preferences;
        $this->assertEquals('dark', $preferences['theme']);
        $this->assertEquals('en', $preferences['language']);
        $this->assertFalse($preferences['sound_enabled']);
    }

    /** @test */
    public function user_can_get_their_stats()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/user/stats');

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'stats' => [
                    'total_games',
                    'total_wins',
                    'win_rate',
                    'total_spent',
                    'total_won',
                ],
            ]);
    }

    /** @test */
    public function guest_cannot_access_user_endpoints()
    {
        $endpoints = [
            ['GET', '/api/user/profile'],
            ['PUT', '/api/user/profile'],
            ['PUT', '/api/user/password'],
            ['GET', '/api/user/preferences'],
            ['GET', '/api/user/stats'],
        ];

        foreach ($endpoints as [$method, $url]) {
            $response = $this->json($method, $url);
            $response->assertStatus(401);
        }
    }

    /** @test */
    public function avatar_must_be_an_image()
    {
        $user = User::factory()->create();

        $file = UploadedFile::fake()->create('document.pdf');

        $response = $this->actingAs($user)
            ->postJson('/api/user/avatar', [
                'avatar' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['avatar']);
    }

    /** @test */
    public function avatar_size_must_not_exceed_2mb()
    {
        $user = User::factory()->create();

        $file = UploadedFile::fake()->image('avatar.jpg')->size(3000); // 3MB

        $response = $this->actingAs($user)
            ->postJson('/api/user/avatar', [
                'avatar' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['avatar']);
    }
}
