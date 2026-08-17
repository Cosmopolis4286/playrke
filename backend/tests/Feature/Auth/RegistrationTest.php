<?php

namespace Tests\Feature\Auth;

use App\Models\Country;
use App\Models\Currency;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cobertura de RegisteredUserController::store() enfocada en los datos
 * persistidos (Tests\Feature\Api\AuthTest ya cubre el flujo HTTP general:
 * redirect, validación, email duplicado) — en particular los dos bugs
 * corregidos ahí: country_id (antes escribía a la columna inexistente
 * 'country') y assignRole('player') (antes faltaba por completo).
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private Country $country;

    protected function setUp(): void
    {
        parent::setUp();

        $this->country = Country::factory()->create(['iso_code_2' => 'VE']);

        Currency::factory()->create([
            'country_id' => $this->country->id,
            'iso_code' => 'VES',
        ]);

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

        // RegisteredUserController::create() redirige a /login si el setting
        // REGISTER no está activo (registro deshabilitado por defecto).
        Setting::updateOrCreate(
            ['param' => 'REGISTER'],
            ['value' => 'true', 'status' => Setting::STATUS_ACTIVE]
        );
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_user_is_persisted_with_country_id_and_active_status(): void
    {
        $this->post('/register', [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'country' => $this->country->id,
            'politicas' => true,
        ]);

        $user = User::where('email', 'test@example.com')->firstOrFail();

        $this->assertSame($this->country->id, $user->country_id);
        $this->assertSame('ACTIVE', $user->status);
    }

    public function test_new_user_is_assigned_the_player_role(): void
    {
        $this->post('/register', [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'country' => $this->country->id,
            'politicas' => true,
        ]);

        $user = User::where('email', 'test@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole('player'));
    }

    public function test_new_user_gets_a_wallet_with_currency_matching_their_country(): void
    {
        $this->post('/register', [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'country' => $this->country->id,
            'politicas' => true,
        ]);

        $user = User::where('email', 'test@example.com')->firstOrFail();
        $currency = Currency::where('country_id', $this->country->id)->firstOrFail();

        $wallet = Wallet::where('user_id', $user->id)->firstOrFail();
        // assertEquals (no assertSame): currency_id puede llegar como
        // int|string según el cast en juego (Snowflake ID), el valor real
        // es lo que importa aquí, no el tipo exacto de PHP.
        $this->assertEquals($currency->id, $wallet->currency_id);
        $this->assertSame(0.0, (float) $wallet->balance);
    }
}
