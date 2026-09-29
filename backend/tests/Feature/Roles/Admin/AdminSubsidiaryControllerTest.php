<?php

namespace Tests\Feature\Roles\Admin;

use App\Models\Country;
use App\Models\Currency;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Roles\Commerce;
use App\Models\Roles\Subsidiary;
use App\Models\Shared\UniqueCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Fija el fix de IDOR en AdminSubsidiaryController: delete()/toggleStatus()/
 * resetPassword() no llamaban authorize() (no son acciones resource estándar,
 * authorizeResource() del constructor no las cubre automáticamente), así que
 * un usuario 'commerce' con permiso subsidiaries.edit/delete podía mutar la
 * Subsidiary de OTRO comercio con solo cambiar el {subsidiary} de la URL.
 *
 * Subsidiary (vía BaseModel -> HasHashedRouteKey) usa Hashids como route
 * key, no el id crudo — las URLs deben construirse con getRouteKey(), nunca
 * con ->id directo (eso resuelve siempre 404, "no encontrado", porque
 * resolveRouteBinding() intenta decodificar el id crudo como si fuera un
 * hash válido).
 */
class AdminSubsidiaryControllerTest extends TestCase
{
    use RefreshDatabase;

    private static int $idCounter = 0;

    private function nextId(): string
    {
        return (string) (400000000000000000 + (++self::$idCounter));
    }

    private function makeRole(string $name): Role
    {
        $role = Role::where('name', $name)->where('guard_name', 'web')->first();
        if ($role) {
            return $role;
        }

        $role = new Role([
            'name' => $name,
            'guard_name' => 'web',
            'prefix' => strtoupper($name),
            'type' => 'web',
            'status' => true,
        ]);
        $role->id = $this->nextId();
        $role->save();

        // guard_name/type NO están en Role::$fillable — el constructor-array
        // los descarta en silencio, y el objeto en memoria queda con
        // guard_name ausente aunque el INSERT sí persiste 'web' (default de
        // columna). Sin este refresh(), cualquier código que lea
        // $role->guard_name directamente en memoria (ej. Spatie\Permission\
        // Guard::getNames() dentro de givePermissionTo()) puede lanzar
        // GuardDoesNotMatch pese a que la fila en BD ya está correcta —
        // confirmado real en VisibilityAuthorizationControllersTest.
        return $role->refresh();
    }

    private function makeCommerceWithUser(string $label): array
    {
        $country = Country::firstOrCreate(
            ['iso_code_2' => 'TC'],
            ['name' => 'Test Country', 'iso_code_3' => 'TCO'],
        );

        $user = User::factory()->create(['country_id' => $country->id]);
        $user->assignRole('commerce');

        $commerce = Commerce::create([
            'name' => "Comercio {$label}",
            'country_id' => $country->id,
            'phone' => '+58414' . random_int(1000000, 9999999),
            'user_id' => $user->id,
            'unique_code_id' => UniqueCode::create([
                'rke_code' => 'RKE-' . random_int(100000, 999999),
                'uuid_code' => (string) Str::uuid(),
                'user_id' => $user->id,
                'status' => 'ACTIVE',
            ])->id,
            'commission' => 5.00,
            'status' => 'ACTIVE',
        ]);

        return [$user, $commerce, $country];
    }

    private function makeSubsidiaryForCommerce(Commerce $commerce, Country $country, User $subsidiaryOwner): Subsidiary
    {
        $currency = Currency::firstOrCreate(['iso_code' => 'USD'], ['name' => 'Dollar', 'symbol' => '$', 'type' => 'FIAT']);

        return Subsidiary::create([
            'name' => 'Sucursal de ' . $commerce->name,
            'commerce_id' => $commerce->id,
            'user_id' => $subsidiaryOwner->id,
            'country_id' => $country->id,
            'currency_id' => $currency->id,
            'commission' => 2.00,
            'phone' => '+58414' . random_int(1000000, 9999999),
            'status' => 'ACTIVE',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->makeRole('commerce');
        $this->makeRole('admin');

        foreach (['subsidiaries.view', 'subsidiaries.edit', 'subsidiaries.delete'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    public function test_commerce_cannot_toggle_status_of_another_commerces_subsidiary(): void
    {
        [$ownCommerceUser, , $country] = $this->makeCommerceWithUser('propio');
        $ownCommerceUser->givePermissionTo(['subsidiaries.view', 'subsidiaries.edit', 'subsidiaries.delete']);

        [, $otherCommerce] = $this->makeCommerceWithUser('ajeno');
        $subsidiaryOwner = User::factory()->create(['country_id' => $country->id]);
        $foreignSubsidiary = $this->makeSubsidiaryForCommerce($otherCommerce, $country, $subsidiaryOwner);

        $response = $this->actingAs($ownCommerceUser)
            ->putJson("/admin/subsidiary/{$foreignSubsidiary->getRouteKey()}/status");

        $response->assertForbidden();
        $this->assertSame('ACTIVE', $foreignSubsidiary->fresh()->status);
    }

    public function test_commerce_cannot_delete_another_commerces_subsidiary(): void
    {
        [$ownCommerceUser, , $country] = $this->makeCommerceWithUser('propio');
        $ownCommerceUser->givePermissionTo(['subsidiaries.view', 'subsidiaries.edit', 'subsidiaries.delete']);

        [, $otherCommerce] = $this->makeCommerceWithUser('ajeno');
        $subsidiaryOwner = User::factory()->create(['country_id' => $country->id]);
        $foreignSubsidiary = $this->makeSubsidiaryForCommerce($otherCommerce, $country, $subsidiaryOwner);

        $response = $this->actingAs($ownCommerceUser)
            ->putJson("/admin/subsidiary/{$foreignSubsidiary->getRouteKey()}/delete");

        $response->assertForbidden();
        $this->assertSame('ACTIVE', $foreignSubsidiary->fresh()->status);
    }

    public function test_commerce_cannot_reset_password_of_another_commerces_subsidiary(): void
    {
        [$ownCommerceUser, , $country] = $this->makeCommerceWithUser('propio');
        $ownCommerceUser->givePermissionTo(['subsidiaries.view', 'subsidiaries.edit', 'subsidiaries.delete']);

        [, $otherCommerce] = $this->makeCommerceWithUser('ajeno');
        $subsidiaryOwner = User::factory()->create(['country_id' => $country->id]);
        $foreignSubsidiary = $this->makeSubsidiaryForCommerce($otherCommerce, $country, $subsidiaryOwner);

        $response = $this->actingAs($ownCommerceUser)
            ->putJson("/admin/subsidiary/{$foreignSubsidiary->getRouteKey()}/reset-password");

        $response->assertForbidden();
    }

    public function test_commerce_can_toggle_status_of_own_subsidiary(): void
    {
        [$ownCommerceUser, $ownCommerce, $country] = $this->makeCommerceWithUser('propio');
        $ownCommerceUser->givePermissionTo(['subsidiaries.view', 'subsidiaries.edit', 'subsidiaries.delete']);

        $subsidiaryOwner = User::factory()->create(['country_id' => $country->id]);
        $ownSubsidiary = $this->makeSubsidiaryForCommerce($ownCommerce, $country, $subsidiaryOwner);

        $response = $this->actingAs($ownCommerceUser)
            ->putJson("/admin/subsidiary/{$ownSubsidiary->getRouteKey()}/status");

        $response->assertOk();
        $this->assertSame('INACTIVE', $ownSubsidiary->fresh()->status);
    }

    /**
     * AdminSubsidiaryForm envía commission: null con el toggle has_commission
     * apagado. Antes: 422 (numeric sobre null) — una agencia "sin comisión"
     * era imposible de guardar desde la UI; y aun pasando, el update
     * descartaba el null en vez de guardar 0.
     */
    public function test_admin_can_turn_off_subsidiary_commission(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $admin->givePermissionTo(['subsidiaries.view', 'subsidiaries.edit', 'subsidiaries.delete']);

        [, $commerce, $country] = $this->makeCommerceWithUser('propio');
        $subsidiaryOwner = User::factory()->create(['country_id' => $country->id]);
        $subsidiary = $this->makeSubsidiaryForCommerce($commerce, $country, $subsidiaryOwner);

        $this->actingAs($admin)
            ->putJson("/admin/subsidiary/{$subsidiary->getRouteKey()}/update", ['commission' => null])
            ->assertOk();

        $this->assertEqualsWithDelta(0.0, (float) $subsidiary->fresh()->commission, 0.001);
    }

    public function test_admin_can_toggle_status_of_any_subsidiary(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $admin->givePermissionTo(['subsidiaries.view', 'subsidiaries.edit', 'subsidiaries.delete']);

        [, $commerce, $country] = $this->makeCommerceWithUser('ajeno');
        $subsidiaryOwner = User::factory()->create(['country_id' => $country->id]);
        $subsidiary = $this->makeSubsidiaryForCommerce($commerce, $country, $subsidiaryOwner);

        $response = $this->actingAs($admin)
            ->putJson("/admin/subsidiary/{$subsidiary->getRouteKey()}/status");

        $response->assertOk();
        $this->assertSame('INACTIVE', $subsidiary->fresh()->status);
    }
}
