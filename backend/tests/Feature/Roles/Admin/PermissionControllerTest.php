<?php

namespace Tests\Feature\Roles\Admin;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionControllerTest extends TestCase
{
    use RefreshDatabase;

    private static int $idCounter = 0;

    private function nextId(): string
    {
        return (string) (300000000000000000 + (++self::$idCounter));
    }

    private function seedRoles(): void
    {
        // roles.id es Snowflake (no autoincrement) y Spatie\Role no conoce el
        // trait SnowflakeId — mismo patrón que BingoTest::seedRolesAndPermissions().
        foreach (['superadmin', 'player'] as $name) {
            if (! Role::where('name', $name)->where('guard_name', 'web')->exists()) {
                $role = new Role([
                    'name'       => $name,
                    'guard_name' => 'web',
                    'prefix'     => $name,
                    'type'       => 'web',
                    'status'     => true,
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

    // ── Protección de permisos de sistema (is_system) ──────────────────────

    public function test_system_permission_cannot_be_updated_even_by_superadmin(): void
    {
        $superadmin = $this->makeSuperadmin();
        $sysPerm    = Permission::create(['name' => 'roles.view', 'guard_name' => 'web', 'is_system' => true]);

        $response = $this->actingAs($superadmin)->patchJson("/permissions/{$sysPerm->id}", [
            'name'       => 'roles.view.renamed',
            'guard_name' => 'web',
        ]);

        $response->assertStatus(403);
        $this->assertSame('roles.view', $sysPerm->fresh()->name);
    }

    public function test_system_permission_cannot_be_deleted_even_by_superadmin(): void
    {
        $superadmin = $this->makeSuperadmin();
        $sysPerm    = Permission::create(['name' => 'system.backup', 'guard_name' => 'web', 'is_system' => true]);

        $response = $this->actingAs($superadmin)->deleteJson("/permissions/{$sysPerm->id}");

        $response->assertStatus(403);
        $this->assertNotNull(Permission::find($sysPerm->id));
    }

    public function test_normal_permission_can_be_updated_and_deleted_by_superadmin(): void
    {
        $superadmin = $this->makeSuperadmin();
        $perm       = Permission::create(['name' => 'reports.view', 'guard_name' => 'web', 'is_system' => false]);

        $update = $this->actingAs($superadmin)->patchJson("/permissions/{$perm->id}", [
            'name'       => 'reports.view.renamed',
            'guard_name' => 'web',
        ]);
        $update->assertOk();
        $this->assertSame('reports.view.renamed', $perm->fresh()->name);

        $delete = $this->actingAs($superadmin)->deleteJson("/permissions/{$perm->id}");
        $delete->assertOk();
        $this->assertNull(Permission::find($perm->id));
    }

    // ── Autorización por rol ────────────────────────────────────────────────

    public function test_non_superadmin_cannot_access_permissions_routes(): void
    {
        $player = $this->makePlayer();
        $perm   = Permission::create(['name' => 'reports.view', 'guard_name' => 'web']);

        $this->actingAs($player)->getJson('/permissions/fetch-list')->assertStatus(403);
        $this->actingAs($player)->postJson('/permissions', ['name' => 'x.y', 'guard_name' => 'web'])->assertStatus(403);
        $this->actingAs($player)->deleteJson("/permissions/{$perm->id}")->assertStatus(403);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->getJson('/permissions/fetch-list')->assertStatus(401);
    }

    // ── Asignación/revocación refleja el cambio real (regresión del bug de
    //    Role::hasPermissionTo() comparando instancias con == en vez de por id) ──

    public function test_assign_and_revoke_permission_reflects_immediately_on_role(): void
    {
        $superadmin = $this->makeSuperadmin();
        $player     = Role::where('name', 'player')->where('guard_name', 'web')->firstOrFail();
        $perm       = Permission::create(['name' => 'reports.view', 'guard_name' => 'web']);

        $this->assertFalse($player->fresh()->hasPermissionTo('reports.view'));

        $assign = $this->actingAs($superadmin)->postJson("/permissions/roles/{$player->id}/assign", [
            'permission_ids' => [$perm->id],
        ]);
        $assign->assertOk();
        $this->assertTrue($player->fresh()->hasPermissionTo('reports.view'));

        $revoke = $this->actingAs($superadmin)->postJson("/permissions/roles/{$player->id}/revoke", [
            'permission_ids' => [$perm->id],
        ]);
        $revoke->assertOk();
        $this->assertFalse($player->fresh()->hasPermissionTo('reports.view'));
    }

    public function test_assign_permissions_to_nonexistent_role_returns_404(): void
    {
        $superadmin = $this->makeSuperadmin();
        $perm       = Permission::create(['name' => 'reports.view', 'guard_name' => 'web']);

        $response = $this->actingAs($superadmin)->postJson('/permissions/roles/999999999999999999/assign', [
            'permission_ids' => [$perm->id],
        ]);

        $response->assertStatus(404);
    }
}
