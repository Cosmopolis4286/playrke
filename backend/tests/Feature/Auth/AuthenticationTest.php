<?php

namespace Tests\Feature\Auth;

use App\Models\Users\User;
use App\Models\Countries\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use BradieTilley\Snowflake\Snowflake;
use Tests\TestCase;

/**
 * Tests de Integración para Autenticación
 *
 * Valida el flujo completo de autenticación entre frontend y backend:
 * - Login con email/username
 * - Registro de nuevos usuarios
 * - Logout
 * - Validaciones y seguridad
 */
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected $defaultCountryId;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear país por defecto para tests
        $country = Country::create([
            'name' => 'Colombia',
            'iso_code_2' => 'CO',
            'iso_code_3' => 'COL',
            'phone_code' => '57',
        ]);
        $this->defaultCountryId = $country->id;

        // Crear rol player para tests (usando Spatie con Snowflake ID y campos custom)
        $snowflake = new Snowflake();
        $role = new Role();
        $role->id = $snowflake->id();
        $role->name = 'player';
        $role->guard_name = 'web';
        $role->prefix = 'player';
        $role->type = 'web';
        $role->description = 'Test player role';
        $role->status = true;
        $role->save();
    }

    /**
     * @test
     * Usuario puede hacer login con email
     */
    public function user_can_login_with_email(): void
    {
        // Arrange: Crear usuario
        $user = User::create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
            'password' => Hash::make('password123'),
            'country_id' => $this->defaultCountryId,
            'referral_code' => 'TEST001',
        ]);
        $user->assignRole('player');

        // Act: Intentar login
        $response = $this->postJson('/api/auth/login', [
            'login' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Assert: Verificar respuesta exitosa
        $response->assertStatus(200)
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
            ])
            ->assertJson([
                'success' => true,
                'user' => [
                    'email' => 'test@example.com',
                ],
            ]);

        // Verificar que el usuario está autenticado
        $this->assertAuthenticatedAs($user);
    }

    /**
     * @test
     * Usuario puede hacer login con username
     */
    public function user_can_login_with_username(): void
    {
        // Arrange
        $user = User::create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
            'password' => Hash::make('password123'),
            'country_id' => $this->defaultCountryId,
            'referral_code' => 'TEST002',
        ]);
        $user->assignRole('player');

        // Act
        $response = $this->postJson('/api/auth/login', [
            'login' => 'testuser',
            'password' => 'password123',
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'user' => [
                    'username' => 'testuser',
                ],
            ]);

        $this->assertAuthenticatedAs($user);
    }

    /**
     * @test
     * Login falla con credenciales incorrectas
     */
    public function login_fails_with_incorrect_credentials(): void
    {
        // Arrange
        User::create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
            'password' => Hash::make('correct-password'),
            'country_id' => $this->defaultCountryId,
            'referral_code' => 'TEST003',
        ]);

        // Act
        $response = $this->postJson('/api/auth/login', [
            'login' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertGuest();
    }

    /**
     * @test
     * Usuario puede registrarse
     */
    public function user_can_register(): void
    {
        // Act: Registrar nuevo usuario
        $response = $this->postJson('/api/auth/register', [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'first_name' => 'New',
            'last_name' => 'User',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'country_id' => $this->defaultCountryId,
            'terms' => true,
        ]);

        // Assert: Registro exitoso
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'user',
            ])
            ->assertJson([
                'success' => true,
                'user' => [
                    'username' => 'newuser',
                    'email' => 'newuser@example.com',
                    'role' => 'player',
                ],
            ]);

        // Verificar que el usuario existe en la BD
        $this->assertDatabaseHas('users', [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
        ]);

        // Verificar que está autenticado
        $this->assertTrue(auth()->check());

        // Verificar que tiene rol player
        $user = User::where('email', 'newuser@example.com')->first();
        $this->assertTrue($user->hasRole('player'));
    }

    /**
     * @test
     * Usuario autenticado puede obtener sus datos
     */
    public function authenticated_user_can_get_user_data(): void
    {
        // Arrange: Usuario autenticado
        $user = User::create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
            'password' => Hash::make('password123'),
            'country_id' => $this->defaultCountryId,
            'referral_code' => 'TEST004',
        ]);
        $user->assignRole('player');

        // Act: Obtener datos del usuario
        $response = $this->actingAs($user)
            ->getJson('/api/user');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                ],
            ]);
    }

    /**
     * @test
     * Usuario no autenticado recibe 401
     */
    public function unauthenticated_user_receives_401(): void
    {
        // Act: Intentar obtener datos sin autenticación
        $response = $this->getJson('/api/user');

        // Assert: 401 Unauthorized
        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'No autenticado',
            ]);
    }

    /**
     * @test
     * Usuario puede hacer logout
     */
    public function user_can_logout(): void
    {
        // Arrange: Usuario autenticado
        $user = User::create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
            'password' => Hash::make('password123'),
            'country_id' => $this->defaultCountryId,
            'referral_code' => 'TEST005',
        ]);
        $this->actingAs($user);

        // Act: Hacer logout
        $response = $this->postJson('/api/auth/logout');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Sesión cerrada exitosamente',
            ]);

        // Verificar que ya no está autenticado
        $this->assertGuest();
    }
}
