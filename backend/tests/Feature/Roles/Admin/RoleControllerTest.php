<?php

namespace Tests\Feature\Roles\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleControllerTest extends TestCase
{
    use RefreshDatabase;

    private static int $idCounter = 0;

    protected function setUp() : void
    {
        parent::setUp();
        $this->seedRoles();
    }

    // ── admin: puede gestionar roles, excepto 'superadmin' y 'admin' ───────

    public function test_admin_can_list_and_view_role_hierarchy() : void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->getJson('/roles-admin/fetch-list')->assertOk();
        $this->actingAs($admin)->getJson('/roles-admin/hierarchy')->assertOk();
    }

    public function test_admin_can_manage_a_regular_role() : void
    {
        $admin = $this->makeAdmin();
        $playerRole = Role::where('name', 'player')->where('guard_name', 'web')->firstOrFail();

        $response = $this->actingAs($admin)->patchJson("/roles-admin/{$playerRole->id}", [
            'description' => 'Actualizado por un admin',
        ]);
        $response->assertOk();
        $this->assertSame('Actualizado por un admin', $playerRole->fresh()->description);

        $this->actingAs($admin)->putJson("/roles-admin/{$playerRole->id}/status")->assertOk();
        $this->assertFalse($playerRole->fresh()->status);
    }

    public function test_admin_cannot_manage_admin_role() : void
    {
        $admin = $this->makeAdmin();
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'web')->firstOrFail();

        $this->actingAs($admin)->patchJson("/roles-admin/{$adminRole->id}", ['description' => 'x'])->assertStatus(403);
        $this->actingAs($admin)->putJson("/roles-admin/{$adminRole->id}/status")->assertStatus(403);
        $this->assertTrue($adminRole->fresh()->status);
    }

    public function test_admin_cannot_manage_superadmin_role() : void
    {
        $admin = $this->makeAdmin();
        $superadminRole = Role::where('name', 'superadmin')->where('guard_name', 'web')->firstOrFail();

        $this->actingAs($admin)->patchJson("/roles-admin/{$superadminRole->id}", ['description' => 'x'])->assertStatus(403);
        $this->actingAs($admin)->putJson("/roles-admin/{$superadminRole->id}/status")->assertStatus(403);
        $this->assertTrue($superadminRole->fresh()->status);
    }

    public function test_non_superadmin_cannot_access_role_routes() : void
    {
        $player = $this->makePlayer();
        $playerRole = Role::where('name', 'player')->where('guard_name', 'web')->firstOrFail();

        $this->actingAs($player)->getJson('/roles-admin/fetch-list')->assertStatus(403);
        $this->actingAs($player)->getJson('/roles-admin/hierarchy')->assertStatus(403);
        $this->actingAs($player)->patchJson("/roles-admin/{$playerRole->id}", ['description' => 'x'])->assertStatus(403);
        $this->actingAs($player)->putJson("/roles-admin/{$playerRole->id}/status")->assertStatus(403);
    }

    public function test_superadmin_can_list_roles() : void
    {
        $superadmin = $this->makeSuperadmin();

        $response = $this->actingAs($superadmin)->getJson('/roles-admin/fetch-list');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('roles')));
    }

    public function test_superadmin_can_manage_admin_role() : void
    {
        $superadmin = $this->makeSuperadmin();
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'web')->firstOrFail();

        $response = $this->actingAs($superadmin)->patchJson("/roles-admin/{$adminRole->id}", [
            'description' => 'Actualizado por superadmin',
        ]);
        $response->assertOk();
        $this->assertSame('Actualizado por superadmin', $adminRole->fresh()->description);
    }

    public function test_superadmin_can_toggle_role_status() : void
    {
        $superadmin = $this->makeSuperadmin();
        $playerRole = Role::where('name', 'player')->where('guard_name', 'web')->firstOrFail();

        $response = $this->actingAs($superadmin)->putJson("/roles-admin/{$playerRole->id}/status");

        $response->assertOk();
        $this->assertFalse($playerRole->fresh()->status);
    }

    public function test_superadmin_can_update_role_description() : void
    {
        $superadmin = $this->makeSuperadmin();
        $playerRole = Role::where('name', 'player')->where('guard_name', 'web')->firstOrFail();

        $response = $this->actingAs($superadmin)->patchJson("/roles-admin/{$playerRole->id}", [
            'description' => 'Jugador final de la plataforma',
        ]);

        $response->assertOk();
        $this->assertSame('Jugador final de la plataforma', $playerRole->fresh()->description);
    }

    public function test_superadmin_can_update_role_sort_order_icon_and_color() : void
    {
        $superadmin = $this->makeSuperadmin();
        $playerRole = Role::where('name', 'player')->where('guard_name', 'web')->firstOrFail();

        $response = $this->actingAs($superadmin)->patchJson("/roles-admin/{$playerRole->id}", [
            'sort_order' => 42,
            'icon' => 'fa-star',
            'color' => '#123456',
        ]);

        $response->assertOk();
        $fresh = $playerRole->fresh();
        $this->assertSame(42, $fresh->sort_order);
        $this->assertSame('fa-star', $fresh->icon);
        $this->assertSame('#123456', $fresh->color);
    }

    /** RoleHierarchyTree.vue manda `status` junto al resto de metadatos por el mismo PATCH, no por PUT .../status. */
    public function test_superadmin_can_update_role_status_via_patch() : void
    {
        $superadmin = $this->makeSuperadmin();
        $playerRole = Role::where('name', 'player')->where('guard_name', 'web')->firstOrFail();

        $response = $this->actingAs($superadmin)->patchJson("/roles-admin/{$playerRole->id}", [
            'status' => false,
        ]);

        $response->assertOk();
        $this->assertFalse($playerRole->fresh()->status);
    }

    public function test_superadmin_can_view_role_hierarchy() : void
    {
        $superadmin = $this->makeSuperadmin();

        $response = $this->actingAs($superadmin)->getJson('/roles-admin/hierarchy');

        $response->assertOk();
        $tree = $response->json('tree');
        $this->assertNotEmpty($tree);

        // 'superadmin' es raíz por DOMAIN_TREE (padre null) — buscarlo por
        // nombre en vez de asumir tree[0], ya que el orden entre raíces con
        // igual sort_order (default 0 en este seed de test, sin el sort_order
        // explícito por rol que sí trae RoleSeeder en producción) no está
        // garantizado.
        $superadminNode = collect($tree)->firstWhere('name', 'superadmin');
        $this->assertNotNull($superadminNode);
        $this->assertSame('SUPERADMIN', $superadminNode['prefix']);
        $this->assertArrayHasKey('children', $superadminNode);
        $this->assertArrayHasKey('users_count', $superadminNode);
        $this->assertArrayHasKey('permissions_count', $superadminNode);
    }

    // ── superadmin: rol raíz, no editable ni desactivable ──────────────────

    public function test_superadmin_role_cannot_be_deactivated() : void
    {
        $superadmin = $this->makeSuperadmin();
        $superadminRole = Role::where('name', 'superadmin')->where('guard_name', 'web')->firstOrFail();

        $response = $this->actingAs($superadmin)->putJson("/roles-admin/{$superadminRole->id}/status");

        $response->assertStatus(403);
        $this->assertTrue($superadminRole->fresh()->status);
    }

    public function test_superadmin_role_description_cannot_be_updated() : void
    {
        $superadmin = $this->makeSuperadmin();
        $superadminRole = Role::where('name', 'superadmin')->where('guard_name', 'web')->firstOrFail();

        $response = $this->actingAs($superadmin)->patchJson("/roles-admin/{$superadminRole->id}", [
            'description' => 'intento de cambio',
        ]);

        $response->assertStatus(403);
    }

    private function makeAdmin() : User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function makePlayer() : User
    {
        $user = User::factory()->create();
        $user->assignRole('player');

        return $user;
    }

    private function makeSuperadmin() : User
    {
        $user = User::factory()->create();
        $user->assignRole('superadmin');

        return $user;
    }

    private function nextId() : string
    {
        return (string) (500000000000000000 + (++self::$idCounter));
    }

    private function seedRoles() : void
    {
        foreach (['superadmin', 'admin', 'player'] as $name) {
            if (! Role::where('name', $name)->where('guard_name', 'web')->exists()) {
                $role = new Role([
                    'name' => $name,
                    'guard_name' => 'web',
                    'prefix' => $name,
                    'type' => 'web',
                    'status' => true,
                    'description' => ucfirst($name),
                ]);
                $role->id = $this->nextId();
                $role->save();
            }
        }
    }
}
