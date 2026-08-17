<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cobertura del flujo real de perfil (Users\ProfileController /
 * routes/modules/user-profile.php) — reescrita tras confirmar que el
 * scaffolding original de Breeze (import roto App\Models\Users, rutas
 * PATCH/DELETE /profile inexistentes, campo `name` que este proyecto no
 * tiene) nunca reflejó la implementación real: ProfileController usa
 * first_name/last_name, PUT /profile/update, PATCH /profile/password, y no
 * expone eliminación de cuenta propia.
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->putJson('/profile/update', [
                'first_name' => 'Test',
                'last_name'  => 'User',
                'username'   => $user->username,
                'email'      => 'updated@example.com',
                'country_id' => $user->country_id,
            ]);

        $response->assertOk();

        $user->refresh();

        $this->assertSame('Test', $user->first_name);
        $this->assertSame('User', $user->last_name);
        $this->assertSame('updated@example.com', $user->email);
    }

    public function test_profile_update_requires_valid_email(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->putJson('/profile/update', [
                'first_name' => 'Test',
                'username'   => $user->username,
                'email'      => 'not-an-email',
            ]);

        $response->assertStatus(422);
    }

    public function test_user_can_change_their_password_via_profile(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patchJson('/profile/password', [
                'current_password'          => 'password',
                'new_password'              => 'new-password-123',
                'new_password_confirmation' => 'new-password-123',
            ]);

        $response->assertOk();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('new-password-123', $user->fresh()->password));
    }

    public function test_change_password_requires_correct_current_password(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patchJson('/profile/password', [
                'current_password'          => 'wrong-password',
                'new_password'              => 'new-password-123',
                'new_password_confirmation' => 'new-password-123',
            ]);

        $response->assertStatus(422);
    }
}
