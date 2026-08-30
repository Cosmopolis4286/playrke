<?php

namespace Tests\Feature\Api;

use App\Models\Country;
use App\Models\Currency;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cobertura del flujo real de autenticación (routes/auth.php), reescrita
 * tras confirmar que /api/auth/* (routes/api.php) nunca se activó — esa
 * capa era para un frontend Nuxt que no está en uso. El registro real es
 * un flujo Inertia (redirect), login/logout devuelven JSON.
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    private Country $country;

    protected function setUp(): void
    {
        parent::setUp();

        $this->country = Country::factory()->create(['iso_code_2' => 'VE']);

        // RegisteredUserController::store() crea una Wallet resolviendo la
        // moneda del país vía Currency::getValueByCountry() — sin una
        // Currency asociada al país, el registro real fallaría.
        Currency::factory()->create([
            'country_id' => $this->country->id,
            'iso_code' => 'VES',
        ]);

        // RegisteredUserController::store() asigna el rol 'player' al usuario
        // recién creado — sin sembrarlo, assignRole('player') falla.
        if (!Role::where('name', 'player')->where('guard_name', 'web')->exists()) {
            $role = new Role([
                'name' => 'player',
                'guard_name' => 'web',
                'prefix' => 'player',
                'type' => 'web',
                'status' => true,
            ]);
            $role->id = (string) random_int(500000000000000000, 599999999999999999);
            $role->save();
        }
    }

    /** @test */
    public function user_can_register()
    {
        $response = $this->post('/register', [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'country' => $this->country->id,
            'terms' => true,
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', [
            'username' => 'testuser',
            'email' => 'test@example.com',
        ]);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('wallets', ['user_id' => $user->id]);
    }

    /** @test */
    public function user_cannot_register_with_duplicate_email()
    {
        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->post('/register', [
            'username' => 'testuser2',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'country' => $this->country->id,
            'terms' => true,
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    /** @test */
    public function user_can_login_with_email()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/login', [
            'login' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function user_can_login_with_username()
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/login', [
            'login' => 'testuser',
            'password' => 'password123',
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function login_fails_with_incorrect_credentials()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/login', [
            'login' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['login']);
        $this->assertGuest();
    }

    /** @test */
    public function user_can_logout()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/logout');

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
        $response = $this->post('/register', []);

        $response->assertSessionHasErrors(['email', 'username', 'country', 'password']);
    }

    /** @test */
    public function password_must_be_confirmed()
    {
        $response = $this->post('/register', [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'differentpassword',
            'country' => $this->country->id,
            'terms' => true,
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertGuest();
    }
}
