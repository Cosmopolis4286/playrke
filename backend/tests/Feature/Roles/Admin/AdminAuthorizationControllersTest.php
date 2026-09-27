<?php

declare(strict_types=1);

namespace Tests\Feature\Roles\Admin;

use App\Domains\Platform\WinEvent\Models\WinEvent;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Cierra el hueco de defensa en profundidad de Frente 1 para
 * AdminUserController, AdminPlayerController y AdminWinEventController —
 * ninguno llamaba authorize() en sus métodos mutantes/de acceso, solo
 * estaban protegidos por middleware de ruta (multiPermission:users.* para
 * AdminUser, role:admin para AdminPlayer, role:superadmin,admin para
 * AdminWinEvent). Este test confirma que las Policies nuevas (AdminUserPolicy,
 * AdminPlayerPolicy, AdminWinEventPolicy) no rompen el acceso legítimo
 * (falso-positivo 403 sería la peor regresión posible aquí) y que un actor
 * sin el permiso/rol correspondiente queda bloqueado.
 *
 * User (vía HasHashedRouteKey) usa Hashids como route key en las rutas que
 * type-hintean User $param (implicit route model binding real, a diferencia
 * de los controllers de Trivia) — las URLs deben construirse con
 * ->getRouteKey() (alias hashed_id).
 */
class AdminAuthorizationControllersTest extends TestCase
{
    use RefreshDatabase;

    private static int $idCounter = 0;

    private function nextId(): string
    {
        return (string) (600000000000000000 + (++self::$idCounter));
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

        return $role;
    }

    private function makeUserWithRole(string $roleName): User
    {
        $this->makeRole($roleName);
        $user = User::factory()->create();
        $user->assignRole($roleName);

        return $user;
    }

    // ---------------------------------------------------------------
    // AdminUserController — permission-based (multiPermission:users.*)
    // ---------------------------------------------------------------

    #[Test]
    public function user_without_users_edit_permission_cannot_toggle_another_users_status(): void
    {
        // Sin el permiso users.edit, ni siquiera pasa el middleware de ruta
        // (multiPermission:users.edit) — la mutación no debe ocurrir.
        $actor = $this->makeUserWithRole('player');
        $target = $this->makeUserWithRole('player');

        $response = $this->actingAs($actor)
            ->putJson("/admin/user/{$target->getRouteKey()}/status");

        $this->assertNotEquals(200, $response->getStatusCode());
        $this->assertSame($target->fresh()->status, $target->status);
    }

    #[Test]
    public function admin_with_users_edit_permission_can_toggle_another_users_status(): void
    {
        $admin = $this->makeUserWithRole('admin');
        Permission::firstOrCreate(['name' => 'users.edit', 'guard_name' => 'web']);
        Role::where('name', 'admin')->firstOrFail()->givePermissionTo('users.edit');

        $target = $this->makeUserWithRole('player');
        $originalStatus = $target->status;

        $response = $this->actingAs($admin)
            ->putJson("/admin/user/{$target->getRouteKey()}/status");

        // No debe ser 403: el nuevo authorize('edit', AdminUserPolicy::class)
        // no debe bloquear a quien sí tiene el permiso real.
        $this->assertNotEquals(403, $response->getStatusCode());
        $response->assertOk();
        $this->assertNotEquals($originalStatus, $target->fresh()->status);
    }

    // ---------------------------------------------------------------
    // AdminPlayerController — role-based (role:admin)
    // ---------------------------------------------------------------

    #[Test]
    public function player_cannot_toggle_status_of_another_player_via_admin_endpoint(): void
    {
        $player = $this->makeUserWithRole('player');
        $target = $this->makeUserWithRole('player');

        $response = $this->actingAs($player)
            ->putJson("/admin/player/{$target->getRouteKey()}/status");

        $this->assertNotEquals(200, $response->getStatusCode());
        $this->assertSame($target->fresh()->status, $target->status);
    }

    #[Test]
    public function admin_can_toggle_status_of_a_player(): void
    {
        $admin = $this->makeUserWithRole('admin');
        $target = $this->makeUserWithRole('player');
        $originalStatus = $target->status;

        $response = $this->actingAs($admin)
            ->putJson("/admin/player/{$target->getRouteKey()}/status");

        $this->assertNotEquals(403, $response->getStatusCode());
        $response->assertOk();
        $this->assertNotEquals($originalStatus, $target->fresh()->status);
    }

    // ---------------------------------------------------------------
    // AdminWinEventController — role-based (role:superadmin,admin)
    // ---------------------------------------------------------------

    #[Test]
    public function player_cannot_view_win_event_list(): void
    {
        $player = $this->makeUserWithRole('player');

        $response = $this->actingAs($player)->getJson('/admin/win-event/fetch-list');

        $this->assertNotEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function admin_can_view_win_event_list(): void
    {
        $admin = $this->makeUserWithRole('admin');

        $response = $this->actingAs($admin)->getJson('/admin/win-event/fetch-list');

        $this->assertNotEquals(403, $response->getStatusCode());
        $response->assertOk();
    }

    #[Test]
    public function superadmin_can_view_win_event_list(): void
    {
        $superadmin = $this->makeUserWithRole('superadmin');

        $response = $this->actingAs($superadmin)->getJson('/admin/win-event/fetch-list');

        $this->assertNotEquals(403, $response->getStatusCode());
        $response->assertOk();
    }
}
