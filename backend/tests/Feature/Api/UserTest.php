<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Cobertura del perfil de usuario contra el flujo real (routes/modules/user-profile.php
 * + ProfileController), reescrita tras confirmar que /api/user/* (routes/api.php)
 * nunca se activó.
 */
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

        $response = $this->actingAs($user)->getJson('/profile/data');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => (string) $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'name' => 'John Doe',
                ],
            ]);
    }

    /** @test */
    public function user_can_update_their_profile()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->putJson('/profile/update', [
                'first_name' => 'Updated',
                'last_name' => 'Name',
                'username' => $user->username,
                'email' => $user->email,
                'phone' => '+58412345678',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
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
            ->patchJson('/profile/password', [
                'current_password' => 'oldpassword',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
            ]);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertTrue(
            Hash::check('newpassword123', $user->fresh()->password)
        );
    }

    /** @test */
    public function user_cannot_change_password_with_wrong_current_password()
    {
        $user = User::factory()->create([
            'password' => bcrypt('oldpassword'),
        ]);

        $response = $this->actingAs($user)
            ->patchJson('/profile/password', [
                'current_password' => 'wrongpassword',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
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
            ->postJson('/profile/avatar', [
                'avatar' => $file,
            ]);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['avatar']]);

        $this->assertNotNull($user->fresh()->profile_photo_url);
    }

    /** @test */
    public function user_can_delete_avatar()
    {
        Storage::fake('public');

        Storage::disk('public')->put('avatars/test.jpg', 'content');

        $user = User::factory()->create([
            'profile_photo_url' => '/storage/avatars/test.jpg',
        ]);

        $response = $this->actingAs($user)->deleteJson('/profile/avatar');

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertNull($user->fresh()->profile_photo_url);
        Storage::disk('public')->assertMissing('avatars/test.jpg');
    }

    /** @test */
    public function user_can_get_their_preferences()
    {
        $user = User::factory()->create([
            'user_preferences' => [
                'language' => 'en',
                'timezone' => 'America/Caracas',
            ],
        ]);

        $response = $this->actingAs($user)->getJson('/profile/preferences');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'language' => 'en',
                    'timezone' => 'America/Caracas',
                ],
            ]);
    }

    /** @test */
    public function user_can_update_their_preferences()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->putJson('/profile/preferences', [
                'language' => 'en',
                'timezone' => 'UTC',
                'game_settings' => ['sound_enabled' => false],
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'language' => 'en',
                    'timezone' => 'UTC',
                    'game_settings' => ['sound_enabled' => false],
                ],
            ]);

        $preferences = $user->fresh()->user_preferences;
        $this->assertEquals('en', $preferences['language']);
    }

    /** @test */
    public function user_can_get_their_stats()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/profile/stats');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'games_played',
                    'games_won',
                    'total_winnings',
                    'level',
                    'experience_points',
                ],
            ]);
    }

    /** @test */
    public function guest_cannot_access_user_endpoints()
    {
        $endpoints = [
            ['GET', '/profile/data'],
            ['PUT', '/profile/update'],
            ['PATCH', '/profile/password'],
            ['GET', '/profile/preferences'],
            ['GET', '/profile/stats'],
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
            ->postJson('/profile/avatar', [
                'avatar' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['avatar']);
    }

    /** @test */
    public function avatar_size_must_not_exceed_5mb()
    {
        $user = User::factory()->create();

        // ProfileController::uploadAvatar() valida max:5120 (KB) = 5MB.
        $file = UploadedFile::fake()->image('avatar.jpg')->size(6000);

        $response = $this->actingAs($user)
            ->postJson('/profile/avatar', [
                'avatar' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['avatar']);
    }
}
