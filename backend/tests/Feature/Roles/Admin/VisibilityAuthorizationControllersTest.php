<?php

declare(strict_types=1);

namespace Tests\Feature\Roles\Admin;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Frente 2: termina la migración role: -> multiPermission: para
 * SidebarMenuVisibilityController y DashboardWidgetVisibilityController —
 * antes exigían role:superadmin puro en la ruta y no tenían ningún test.
 * Ahora usan multiPermission (sidebar-menu.* / dashboard-widgets.*) en la
 * ruta, más SidebarMenuVisibilityPolicy/DashboardWidgetVisibilityPolicy
 * permission-based (controller) — ver migración
 * 2026_09_27_000001_add_sidebar_and_dashboard_visibility_permissions.
 *
 * 'admin' deliberadamente NO tiene estos permisos por diseño (mismo nivel
 * de exclusividad que permissions/modules/roles-admin) — se usa aquí como
 * el rol "autenticado pero sin el permiso correcto".
 */
class VisibilityAuthorizationControllersTest extends TestCase
{
    use RefreshDatabase;

    private static int $idCounter = 0;

    private function nextId(): string
    {
        return (string) (700000000000000000 + (++self::$idCounter));
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
        // los descarta en silencio (fill() los ignora), así que el objeto en
        // memoria queda con guard_name ausente aunque el INSERT sí persiste
        // 'web' (default de columna en el schema). Spatie\Permission\Guard::
        // getNames() lee el atributo en memoria directamente — sin este
        // refresh(), givePermissionTo() sobre este mismo objeto lanza
        // GuardDoesNotMatch (confirmado real, no solo teórico) pese a que la
        // fila en BD ya tiene guard_name='web' correcto.
        return $role->refresh();
    }

    private function makeUserWithRole(string $roleName): User
    {
        $this->makeRole($roleName);
        $user = User::factory()->create();
        $user->assignRole($roleName);

        return $user;
    }

    private function makeSuperadminWithVisibilityPermissions(): User
    {
        $role = $this->makeRole('superadmin');
        // Pasar objetos Permission (no strings) a givePermissionTo(): con
        // strings, Spatie resuelve por el guard "default" de la config, que
        // no necesariamente coincide con el guard 'web' explícito de estos
        // permisos — confirmado real (GuardDoesNotMatch), no solo teórico.
        $permissions = collect([
            'sidebar-menu.view', 'sidebar-menu.manage',
            'dashboard-widgets.view', 'dashboard-widgets.manage',
        ])->map(fn (string $name) => Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));

        $role->givePermissionTo($permissions);

        $user = User::factory()->create();
        $user->assignRole('superadmin');

        return $user;
    }

    // ---------------------------------------------------------------
    // SidebarMenuVisibilityController
    // ---------------------------------------------------------------

    #[Test]
    public function admin_without_sidebar_menu_permission_cannot_view_the_matrix(): void
    {
        $admin = $this->makeUserWithRole('admin');

        $response = $this->actingAs($admin)->getJson('/sidebar-menu/fetch-list');

        $this->assertNotEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function admin_without_sidebar_menu_permission_cannot_save_changes(): void
    {
        $admin = $this->makeUserWithRole('admin');
        $targetRole = $this->makeRole('player');

        $response = $this->actingAs($admin)->postJson('/sidebar-menu/save', [
            'changes' => [
                ['item_key' => 'dashboard', 'role_id' => $targetRole->id, 'is_visible' => false],
            ],
        ]);

        $this->assertNotEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function superadmin_with_permission_can_view_the_sidebar_matrix(): void
    {
        $superadmin = $this->makeSuperadminWithVisibilityPermissions();

        $response = $this->actingAs($superadmin)->getJson('/sidebar-menu/fetch-list');

        $this->assertNotEquals(403, $response->getStatusCode());
        $response->assertOk();
    }

    #[Test]
    public function superadmin_with_permission_can_save_sidebar_changes(): void
    {
        $superadmin = $this->makeSuperadminWithVisibilityPermissions();
        $targetRole = $this->makeRole('player');

        $response = $this->actingAs($superadmin)->postJson('/sidebar-menu/save', [
            'changes' => [
                ['item_key' => 'dashboard', 'role_id' => $targetRole->id, 'is_visible' => false, 'sort_order' => 5],
            ],
        ]);

        $this->assertNotEquals(403, $response->getStatusCode());
        $response->assertOk();
        $this->assertDatabaseHas('sidebar_menu_visibilities', [
            'item_key' => 'dashboard',
            'role_id' => $targetRole->id,
            'is_visible' => false,
        ]);
    }

    // ---------------------------------------------------------------
    // DashboardWidgetVisibilityController
    // ---------------------------------------------------------------

    #[Test]
    public function admin_without_dashboard_widgets_permission_cannot_view_the_matrix(): void
    {
        $admin = $this->makeUserWithRole('admin');

        $response = $this->actingAs($admin)->getJson('/dashboard-widgets/fetch-list');

        $this->assertNotEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function admin_without_dashboard_widgets_permission_cannot_save_changes(): void
    {
        $admin = $this->makeUserWithRole('admin');
        $targetRole = $this->makeRole('player');

        $response = $this->actingAs($admin)->postJson('/dashboard-widgets/save', [
            'changes' => [
                ['item_key' => 'total_users', 'role_id' => $targetRole->id, 'is_visible' => false],
            ],
        ]);

        $this->assertNotEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function superadmin_with_permission_can_view_the_dashboard_widgets_matrix(): void
    {
        $superadmin = $this->makeSuperadminWithVisibilityPermissions();

        $response = $this->actingAs($superadmin)->getJson('/dashboard-widgets/fetch-list');

        $this->assertNotEquals(403, $response->getStatusCode());
        $response->assertOk();
    }

    #[Test]
    public function superadmin_with_permission_can_save_dashboard_widget_changes(): void
    {
        $superadmin = $this->makeSuperadminWithVisibilityPermissions();
        $targetRole = $this->makeRole('player');

        $response = $this->actingAs($superadmin)->postJson('/dashboard-widgets/save', [
            'changes' => [
                ['item_key' => 'total_users', 'role_id' => $targetRole->id, 'is_visible' => false],
            ],
        ]);

        $this->assertNotEquals(403, $response->getStatusCode());
        $response->assertOk();
        $this->assertDatabaseHas('dashboard_widget_visibilities', [
            'item_key' => 'total_users',
            'role_id' => $targetRole->id,
            'is_visible' => false,
        ]);
    }
}
