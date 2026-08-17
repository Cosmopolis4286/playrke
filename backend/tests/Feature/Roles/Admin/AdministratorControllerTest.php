<?php

namespace Tests\Feature\Roles\Admin;

use App\Models\Country;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdministratorControllerTest extends TestCase
{
    use RefreshDatabase;

    private static int $idCounter = 0;

    private function nextId(): string
    {
        return (string) (600000000000000000 + (++self::$idCounter));
    }

    private function seedRoles(): void
    {
        foreach (['superadmin', 'admin', 'player'] as $name) {
            if (! Role::where('name', $name)->where('guard_name', 'web')->exists()) {
                $role = new Role([
                    'name'       => $name,
                    'guard_name' => 'web',
                    'prefix'     => $name,
                    'type'       => 'web',
                    'status'     => true,
                    'description'=> ucfirst($name),
                ]);
                $role->id = $this->nextId();
                $role->save();
            }
        }
    }

    private function makeSuperadmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('superadmin');

        return $user;
    }

    private function makeAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function makePlayer(): User
    {
        $user = User::factory()->create();
        $user->assignRole('player');

        return $user;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    // ── Acceso ───────────────────────────────────────────────────────────

    public function test_non_superadmin_cannot_access_administrator_routes(): void
    {
        $player = $this->makePlayer();
        $target = $this->makeAdmin();

        $this->actingAs($player)->getJson('/admins/fetch-list')->assertStatus(403);
        $this->actingAs($player)->postJson('/admins', [])->assertStatus(403);
        $this->actingAs($player)->patchJson("/admins/{$target->getRouteKey()}", [])->assertStatus(403);
        $this->actingAs($player)->patchJson("/admins/{$target->getRouteKey()}/toggle-status")->assertStatus(403);
        $this->actingAs($player)->deleteJson("/admins/{$target->getRouteKey()}")->assertStatus(403);
    }

    public function test_admin_cannot_access_administrator_routes(): void
    {
        // La gestión de /admins es exclusiva de superadmin (a diferencia de
        // /roles-admin, donde un admin sí puede gestionar roles) — el
        // middleware de ruta ya bloquea a 'admin' aquí.
        $admin = $this->makeAdmin();
        $target = $this->makeAdmin();

        $this->actingAs($admin)->getJson('/admins/fetch-list')->assertStatus(403);
    }

    // ── Creación ─────────────────────────────────────────────────────────

    public function test_superadmin_can_create_administrator(): void
    {
        $superadmin = $this->makeSuperadmin();
        $country = Country::factory()->create();

        $response = $this->actingAs($superadmin)->postJson('/admins', [
            'first_name' => 'Nuevo',
            'last_name'  => 'Admin',
            'username'   => 'nuevoadmin',
            'email'      => 'nuevoadmin@example.com',
            'password'   => 'password123',
            'password_confirmation' => 'password123',
            'country_id' => $country->id,
        ]);

        $response->assertOk();
        $created = User::where('email', 'nuevoadmin@example.com')->firstOrFail();
        $this->assertTrue($created->hasRole('admin'));
        $this->assertFalse($created->hasRole('superadmin'));
        $this->assertSame('ACTIVE', $created->status);
    }

    public function test_create_requires_matching_password_confirmation(): void
    {
        $superadmin = $this->makeSuperadmin();
        $country = Country::factory()->create();

        $response = $this->actingAs($superadmin)->postJson('/admins', [
            'first_name' => 'Nuevo',
            'last_name'  => 'Admin',
            'username'   => 'nuevoadmin2',
            'email'      => 'nuevoadmin2@example.com',
            'password'   => 'password123',
            'password_confirmation' => 'distinta',
            'country_id' => $country->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_create_rejects_duplicate_username(): void
    {
        $superadmin = $this->makeSuperadmin();
        $existing = $this->makeAdmin();
        $country = Country::factory()->create();

        $response = $this->actingAs($superadmin)->postJson('/admins', [
            'first_name' => 'Nuevo',
            'last_name'  => 'Admin',
            'username'   => $existing->username,
            'email'      => 'otro@example.com',
            'password'   => 'password123',
            'password_confirmation' => 'password123',
            'country_id' => $country->id,
        ]);

        $response->assertStatus(422);
    }

    // ── Edición ──────────────────────────────────────────────────────────

    public function test_superadmin_can_update_administrator(): void
    {
        $superadmin = $this->makeSuperadmin();
        $target = $this->makeAdmin();

        $response = $this->actingAs($superadmin)->patchJson("/admins/{$target->getRouteKey()}", [
            'first_name' => 'Actualizado',
        ]);

        $response->assertOk();
        $this->assertSame('Actualizado', $target->fresh()->first_name);
    }

    /**
     * A diferencia de las acciones sensibles (status/password/delete), editar
     * datos básicos SÍ permite auto-edición — UserPolicy::editAdministrator()
     * (distinta de manageAdministrator()) no bloquea id === auth()->id().
     */
    public function test_superadmin_can_update_own_basic_data(): void
    {
        $superadmin = $this->makeSuperadmin();

        $response = $this->actingAs($superadmin)->patchJson("/admins/{$superadmin->getRouteKey()}", [
            'first_name' => 'Actualizado',
        ]);

        $response->assertOk();
        $this->assertSame('Actualizado', $superadmin->fresh()->first_name);
    }

    /**
     * El status nunca viaja por PATCH /admins/{user} (AdministratorUpdateDTO
     * no incluye ese campo) — enviarlo en el payload se ignora silenciosamente,
     * confirmando que la auto-edición no puede colarse como auto-desactivación.
     */
    public function test_update_endpoint_ignores_status_field(): void
    {
        $superadmin = $this->makeSuperadmin();

        $response = $this->actingAs($superadmin)->patchJson("/admins/{$superadmin->getRouteKey()}", [
            'first_name' => 'Actualizado',
            'status' => 'INACTIVE',
        ]);

        $response->assertOk();
        $this->assertSame('ACTIVE', $superadmin->fresh()->status);
    }

    // ── Status / Delete guards ──────────────────────────────────────────

    public function test_superadmin_can_toggle_status_of_administrator(): void
    {
        $superadmin = $this->makeSuperadmin();
        $target = $this->makeAdmin();
        // Con $superadmin + $target activos (2 total), desactivar a $target
        // es válido: queda $superadmin como administrador activo.

        $response = $this->actingAs($superadmin)->patchJson("/admins/{$target->getRouteKey()}/toggle-status");

        $response->assertOk();
        $this->assertSame('INACTIVE', $target->fresh()->status);
    }

    /**
     * El guard "no dejar el sistema sin administradores activos" no es
     * alcanzable a nivel HTTP: el actor debe estar ACTIVE para autenticarse
     * (ver seedRoles/middleware role:), así que el conteo de activos antes
     * de cualquier operación nunca es menor a 2 (actor + target) salvo que
     * el actor se apunte a sí mismo — caso ya bloqueado antes por la regla
     * de auto-modificación (UserPolicy::manageAdministrator). Se prueba
     * entonces directamente sobre el service, invocándolo como lo haría un
     * caller sin ese contexto HTTP (ej. un comando artisan futuro).
     */
    public function test_service_blocks_deactivating_the_last_active_administrator(): void
    {
        $onlyActiveAdmin = $this->makeAdmin();

        $this->actingAs($this->makeSuperadmin());

        $service = app(\App\Services\Roles\Admin\AdministratorService::class);

        $this->expectException(\App\Exceptions\Domain\Roles\Admin\Administrator\AdministratorException::class);
        $this->expectExceptionMessage('ADMINISTRATOR_CANNOT_REMOVE_LAST_ACTIVE');

        // auth()->id() dentro del service resuelve al actor logueado
        // (el superadmin de control), no a $onlyActiveAdmin — así que esto
        // no dispara la regla de auto-modificación, solo la de "último activo"
        // una vez que se deja al superadmin de control como inactivo primero.
        \App\Models\User::whereKeyNot($onlyActiveAdmin->id)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'superadmin']))
            ->update(['status' => 'INACTIVE']);

        $service->toggleStatus($onlyActiveAdmin->fresh());
    }

    public function test_superadmin_can_hard_delete_a_superadmin_when_others_remain(): void
    {
        $onlySuperadmin = $this->makeSuperadmin();
        // Actuando como un segundo superadmin para no chocar con la regla
        // de auto-modificación.
        $actor = $this->makeSuperadmin();

        $response = $this->actingAs($actor)->deleteJson("/admins/{$onlySuperadmin->getRouteKey()}/force-delete");

        // Hay 2 superadmins (onlySuperadmin + actor), así que esto sí se
        // permite — confirma que la regla es sobre el ÚLTIMO, no cualquiera.
        $response->assertOk();
    }

    public function test_cannot_hard_delete_the_last_superadmin(): void
    {
        // Mismo caso que test_service_blocks_deactivating_the_last_active_administrator:
        // el actor debe ser superadmin para pasar el middleware, así que
        // "el último superadmin" solo puede probarse invocando el service
        // directamente, no vía HTTP (el actor mismo ya sería ese último).
        $onlySuperadmin = $this->makeSuperadmin();
        // Actor distinto del target (evita el guard de auto-modificación) y
        // 'admin' en vez de 'superadmin' (evita que superadminCount suba a 2,
        // lo que ocultaría la regla que se quiere probar).
        $actor = $this->makeAdmin();

        $service = app(\App\Services\Roles\Admin\AdministratorService::class);
        $this->actingAs($actor);

        $this->expectException(\App\Exceptions\Domain\Roles\Admin\Administrator\AdministratorException::class);
        $this->expectExceptionMessage('ADMINISTRATOR_CANNOT_DELETE_LAST_SUPERADMIN');

        $service->destroy($onlySuperadmin->fresh());
    }

    // ── Reset password ───────────────────────────────────────────────────

    public function test_reset_password_requires_active_status(): void
    {
        $superadmin = $this->makeSuperadmin();
        $target = $this->makeAdmin();
        $target->update(['status' => 'INACTIVE']);

        $response = $this->actingAs($superadmin)->putJson("/admins/{$target->getRouteKey()}/reset-password");

        $response->assertStatus(422);
    }

    public function test_superadmin_can_reset_password_of_active_administrator(): void
    {
        $superadmin = $this->makeSuperadmin();
        $target = $this->makeAdmin();
        $originalHash = $target->password;

        $response = $this->actingAs($superadmin)->putJson("/admins/{$target->getRouteKey()}/reset-password");

        $response->assertOk();
        $this->assertArrayHasKey('password', $response->json());
        $this->assertNotSame($originalHash, $target->fresh()->password);
    }
}
