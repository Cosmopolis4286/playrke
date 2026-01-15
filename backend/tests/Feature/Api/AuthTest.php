<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a default country for testing
        Country::factory()->create([
            'id' => 1,
            'name' => 'Venezuela',
            'iso_code_2' => 'VE',
        ]);
    }

    /** @test */
    public function user_can_register()
    {
        $response = $this->postJson('/api/auth/register', [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'country_id' => 1,
            'terms' => true,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Usuario registrado exitosamente',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'user' => [
                    'id',
                    'username',
                    'email',
                    'first_name',
                    'last_name',
                    'full_name',
                    'role',
                    'roles',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'username' => 'testuser',
            'email' => 'test@example.com',
        ]);
    }

    /** @test */
    public function user_cannot_register_with_duplicate_email()
    {
        User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->postJson('/api/auth/register', [
            'username' => 'testuser2',
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'country_id' => 1,
            'terms' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function user_can_login_with_email()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'login' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Autenticación exitosa',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'user' => [
                    'id',
                    'username',
                    'email',
                    'role',
                ],
            ]);

        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function user_can_login_with_username()
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'login' => 'testuser',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function user_cannot_login_with_invalid_credentials()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'login' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Credenciales inválidas',
            ]);

        $this->assertGuest();
    }

    /** @test */
    public function authenticated_user_can_get_their_info()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/auth/user');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                ],
            ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_get_user_info()
    {
        $response = $this->getJson('/api/auth/user');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'No autenticado',
            ]);
    }

    /** @test */
    public function user_can_logout()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/auth/logout');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Sesión cerrada exitosamente',
            ]);

        $this->assertGuest();
    }

    /** @test */
    public function registration_requires_all_fields()
    {
        $response = $this->postJson('/api/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'username',
                'email',
                'first_name',
                'last_name',
                'password',
                'country_id',
                'terms',
            ]);
    }

    /** @test */
    public function password_must_be_confirmed()
    {
        $response = $this->postJson('/api/auth/register', [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
            'password' => 'password123',
            'password_confirmation' => 'differentpassword',
            'country_id' => 1,
            'terms' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function user_is_assigned_player_role_on_registration()
    {
        $this->postJson('/api/auth/register', [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'country_id' => 1,
            'terms' => true,
        ]);

        $user = User::where('email', 'test@example.com')->first();

        $this->assertTrue($user->hasRole('player'));
    }
}
