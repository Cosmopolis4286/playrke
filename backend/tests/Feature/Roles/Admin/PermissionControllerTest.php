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

    public function test_system_permission_cannot_be_assigned_or_revoked_in_batch_even_by_superadmin(): void
    {
        $superadmin = $this->makeSuperadmin();
        $player     = Role::where('name', 'player')->where('guard_name', 'web')->firstOrFail();
        $sysPerm    = Permission::create(['name' => 'roles.view', 'guard_name' => 'web', 'is_system' => true]);

        $assign = $this->actingAs($superadmin)->postJson("/permissions/roles/{$player->id}/assign", [
            'permission_ids' => [$sysPerm->id],
        ]);
        $assign->assertOk();
        $this->assertFalse($player->fresh()->hasPermissionTo('roles.view'));

        $player->givePermissionTo($sysPerm);
        $revoke = $this->actingAs($superadmin)->postJson("/permissions/roles/{$player->id}/revoke", [
            'permission_ids' => [$sysPerm->id],
        ]);
        $revoke->assertOk();
        $this->assertTrue($player->fresh()->hasPermissionTo('roles.view'));
    }

    // ── SuperAdmin: 100% de permisos por defecto, no mutable ────────────────

    public function test_new_permission_is_auto_assigned_to_superadmin(): void
    {
        $superadmin = $this->makeSuperadmin();

        $this->actingAs($superadmin)->postJson('/permissions', [
            'name'       => 'reports.view',
            'guard_name' => 'web',
        ])->assertOk();

        $this->assertTrue($superadmin->fresh()->hasPermissionTo('reports.view'));
    }

    public function test_superadmin_permissions_cannot_be_revoked(): void
    {
        $superadmin = $this->makeSuperadmin();
        $superadminRole = Role::where('name', 'superadmin')->where('guard_name', 'web')->firstOrFail();
        $perm = Permission::create(['name' => 'reports.view', 'guard_name' => 'web']);
        $superadminRole->givePermissionTo($perm);

        $response = $this->actingAs($superadmin)->postJson("/permissions/roles/{$superadminRole->id}/revoke", [
            'permission_ids' => [$perm->id],
        ]);

        $response->assertOk();
        $this->assertTrue($superadminRole->fresh()->hasPermissionTo('reports.view'));
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

    // ── Guardado transaccional de la matriz (PATCH /permissions/matrix) ────

    public function test_save_matrix_applies_grants_and_revokes_for_multiple_roles_atomically(): void
    {
        $superadmin = $this->makeSuperadmin();
        $player     = Role::where('name', 'player')->where('guard_name', 'web')->firstOrFail();
        $permA      = Permission::create(['name' => 'reports.view', 'guard_name' => 'web']);
        $permB      = Permission::create(['name' => 'reports.export', 'guard_name' => 'web']);

        $player->givePermissionTo($permB);

        $response = $this->actingAs($superadmin)->patchJson('/permissions/matrix', [
            'changes' => [
                ['role_id' => $player->id, 'permission_id' => $permA->id, 'granted' => true],
                ['role_id' => $player->id, 'permission_id' => $permB->id, 'granted' => false],
            ],
        ]);

        $response->assertOk();
        $this->assertTrue($player->fresh()->hasPermissionTo('reports.view'));
        $this->assertFalse($player->fresh()->hasPermissionTo('reports.export'));
    }

    public function test_save_matrix_excludes_system_permissions_and_protects_superadmin(): void
    {
        $superadmin     = $this->makeSuperadmin();
        $superadminRole = Role::where('name', 'superadmin')->where('guard_name', 'web')->firstOrFail();
        $player         = Role::where('name', 'player')->where('guard_name', 'web')->firstOrFail();
        $sysPerm        = Permission::create(['name' => 'roles.view', 'guard_name' => 'web', 'is_system' => true]);
        $normalPerm     = Permission::create(['name' => 'reports.view', 'guard_name' => 'web']);

        $superadminRole->givePermissionTo($normalPerm);

        $response = $this->actingAs($superadmin)->patchJson('/permissions/matrix', [
            'changes' => [
                ['role_id' => $player->id, 'permission_id' => $sysPerm->id, 'granted' => true],
                ['role_id' => $superadminRole->id, 'permission_id' => $normalPerm->id, 'granted' => false],
            ],
        ]);

        $response->assertOk();
        $this->assertFalse($player->fresh()->hasPermissionTo('roles.view'));
        $this->assertTrue($superadminRole->fresh()->hasPermissionTo('reports.view'));
    }

    public function test_save_matrix_validates_changes_shape(): void
    {
        $superadmin = $this->makeSuperadmin();

        $response = $this->actingAs($superadmin)->patchJson('/permissions/matrix', [
            'changes' => [
                ['role_id' => '999999999999999999', 'permission_id' => '999999999999999999', 'granted' => true],
            ],
        ]);

        $response->assertStatus(422);
    }

    // ── Copiar permisos entre roles (POST /permissions/roles/{role}/copy-from/{sourceRole}) ──

    public function test_copy_permissions_merges_into_target_role_without_removing_existing(): void
    {
        $superadmin = $this->makeSuperadmin();
        $source     = Role::where('name', 'superadmin')->where('guard_name', 'web')->firstOrFail();
        $target     = Role::where('name', 'player')->where('guard_name', 'web')->firstOrFail();
        $permA      = Permission::create(['name' => 'reports.view', 'guard_name' => 'web']);
        $permB      = Permission::create(['name' => 'reports.export', 'guard_name' => 'web']);
        $sysPerm    = Permission::create(['name' => 'roles.view', 'guard_name' => 'web', 'is_system' => true]);
        $existing   = Permission::create(['name' => 'reports.print', 'guard_name' => 'web']);

        $source->givePermissionTo([$permA, $permB, $sysPerm]);
        $target->givePermissionTo($existing);

        $response = $this->actingAs($superadmin)->postJson("/permissions/roles/{$target->id}/copy-from/{$source->id}");

        $response->assertOk();
        $target = $target->fresh();
        $this->assertTrue($target->hasPermissionTo('reports.view'));
        $this->assertTrue($target->hasPermissionTo('reports.export'));
        $this->assertTrue($target->hasPermissionTo('reports.print'));
        $this->assertFalse($target->hasPermissionTo('roles.view'));
    }

    public function test_copy_permissions_from_nonexistent_role_returns_404(): void
    {
        $superadmin = $this->makeSuperadmin();
        $target     = Role::where('name', 'player')->where('guard_name', 'web')->firstOrFail();

        $response = $this->actingAs($superadmin)->postJson("/permissions/roles/{$target->id}/copy-from/999999999999999999");

        $response->assertStatus(404);
    }
}
