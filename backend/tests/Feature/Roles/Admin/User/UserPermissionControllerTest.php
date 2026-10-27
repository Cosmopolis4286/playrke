<?php

namespace Tests\Feature\Roles\Admin\User;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPermissionControllerTest extends TestCase
{
    use RefreshDatabase;

    private static int $idCounter = 0;

    protected function setUp() : void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_admin_cannot_manage_permissions_of_other_users() : void
    {
        $admin = $this->makeAdmin();
        $player = $this->makePlayer();

        $this->actingAs($admin)->getJson("/admin/user/{$player->hashed_id}/permissions")->assertStatus(403);
        $this->actingAs($admin)->putJson("/admin/user/{$player->hashed_id}/permissions", ['permissions' => []])->assertStatus(403);
    }

    public function test_expiration_command_revokes_due_grants_and_notifies() : void
    {
        $superadmin = $this->makeSuperadmin();
        $player = $this->makePlayer();
        Permission::firstOrCreate(['name' => 'reports.view', 'guard_name' => 'web']);

        $grant = \App\Models\UserSpecificPermissionGrant::create([
            'user_id' => $player->id,
            'permission_name' => 'reports.view',
            'expires_at' => now()->subHour(),
        ]);

        $this->artisan('permissions:expire-specific-grants')->assertSuccessful();

        $this->assertNotNull($grant->fresh()->revoked_at);
        $this->assertEquals(
            \App\Models\UserSpecificPermissionGrant::REVOKED_REASON_EXPIRED,
            $grant->fresh()->revoked_reason,
        );
        $this->assertDatabaseHas('notifications', [
            'user_id' => $superadmin->id,
            'type' => 'SYSTEM',
        ]);
    }

    public function test_expired_grant_no_longer_grants_access() : void
    {
        $player = $this->makePlayer();
        Permission::firstOrCreate(['name' => 'reports.view', 'guard_name' => 'web']);

        \App\Models\UserSpecificPermissionGrant::create([
            'user_id' => $player->id,
            'permission_name' => 'reports.view',
            'expires_at' => now()->subHour(),
        ]);

        $this->assertFalse($player->fresh()->can('reports.view'));
    }

    public function test_get_user_permissions_distinguishes_role_and_specific_origin() : void
    {
        $superadmin = $this->makeSuperadmin();
        $player = $this->makePlayer();
        $rolePerm = Permission::firstOrCreate(['name' => 'games.bingo', 'guard_name' => 'web']);
        $specific = Permission::firstOrCreate(['name' => 'reports.view', 'guard_name' => 'web']);

        Role::where('name', 'player')->firstOrFail()->givePermissionTo($rolePerm);
        $player->giveSpecificPermission('reports.view');

        $response = $this->actingAs($superadmin)->getJson("/admin/user/{$player->hashed_id}/permissions");

        $response->assertOk()
            ->assertJsonPath('via_roles', ['games.bingo'])
            ->assertJsonPath('specific.0.name', 'reports.view')
            ->assertJsonPath('specific.0.expires_at', null);
    }

    public function test_granting_permission_rejects_past_expiry_date() : void
    {
        $superadmin = $this->makeSuperadmin();
        $player = $this->makePlayer();
        $perm = Permission::firstOrCreate(['name' => 'reports.view', 'guard_name' => 'web']);

        $response = $this->actingAs($superadmin)->putJson("/admin/user/{$player->hashed_id}/permissions", [
            'permissions' => [['name' => $perm->name, 'expires_at' => now()->subDay()->toISOString()]],
        ]);

        $response->assertStatus(422);
    }

    // ── Expiración de permisos individuales ─────────────────────────────────

    public function test_granting_permission_with_future_expiry_is_active_until_expiry() : void
    {
        $superadmin = $this->makeSuperadmin();
        $player = $this->makePlayer();
        $perm = Permission::firstOrCreate(['name' => 'reports.view', 'guard_name' => 'web']);

        $response = $this->actingAs($superadmin)->putJson("/admin/user/{$player->hashed_id}/permissions", [
            'permissions' => [['name' => $perm->name, 'expires_at' => now()->addDays(3)->toISOString()]],
        ]);

        $response->assertOk();
        $this->assertTrue($player->fresh()->can('reports.view'));

        $grant = \App\Models\UserSpecificPermissionGrant::where('user_id', $player->id)
            ->where('permission_name', 'reports.view')
            ->first();
        $this->assertNotNull($grant->expires_at);
        $this->assertNull($grant->revoked_at);
    }

    // ── syncSpecificPermissions refleja el cambio real de inmediato ─────────
    // (mismo tipo de regresión ya cubierta para Role::hasPermissionTo() —
    // aquí se verifica que User::hasPermissionTo() siga funcionando bien
    // tras otorgar/revocar vía este nuevo endpoint).

    public function test_granting_specific_permission_reflects_immediately() : void
    {
        $superadmin = $this->makeSuperadmin();
        $player = $this->makePlayer();
        $perm = Permission::firstOrCreate(['name' => 'reports.view', 'guard_name' => 'web']);

        $this->assertFalse($player->fresh()->can('reports.view'));

        $response = $this->actingAs($superadmin)->putJson("/admin/user/{$player->hashed_id}/permissions", [
            'permissions' => [['name' => $perm->name, 'expires_at' => null]],
        ]);

        $response->assertOk();
        $this->assertTrue($player->fresh()->can('reports.view'));
    }

    public function test_guest_is_redirected_to_login() : void
    {
        $player = $this->makePlayer();

        $this->getJson("/admin/user/{$player->hashed_id}/permissions")->assertStatus(401);
    }

    public function test_revoking_specific_permission_does_not_remove_access_granted_via_role() : void
    {
        $superadmin = $this->makeSuperadmin();
        $player = $this->makePlayer();
        $rolePerm = Permission::firstOrCreate(['name' => 'games.bingo', 'guard_name' => 'web']);
        $specific = Permission::firstOrCreate(['name' => 'reports.view', 'guard_name' => 'web']);

        Role::where('name', 'player')->firstOrFail()->givePermissionTo($rolePerm);
        $player->giveSpecificPermission('reports.view');

        $this->actingAs($superadmin)->putJson("/admin/user/{$player->hashed_id}/permissions", [
            'permissions' => [], // revoca 'reports.view' específico
        ])->assertOk();

        $fresh = $player->fresh();
        $this->assertFalse($fresh->can('reports.view')); // el específico sí se revocó
        $this->assertTrue($fresh->can('games.bingo'));   // el heredado por rol sigue intacto
    }

    public function test_revoking_specific_permission_removes_access() : void
    {
        $superadmin = $this->makeSuperadmin();
        $player = $this->makePlayer();
        $perm = Permission::firstOrCreate(['name' => 'reports.view', 'guard_name' => 'web']);

        $player->giveSpecificPermission('reports.view');
        $this->assertTrue($player->fresh()->can('reports.view'));

        $response = $this->actingAs($superadmin)->putJson("/admin/user/{$player->hashed_id}/permissions", [
            'permissions' => [],
        ]);

        $response->assertOk();
        $this->assertFalse($player->fresh()->can('reports.view'));
    }

    public function test_superadmin_can_manage_permissions_of_another_admin() : void
    {
        // Decisión de producto: sin restricción por rol del usuario objetivo.
        $superadmin = $this->makeSuperadmin();
        $targetAdmin = $this->makeAdmin();
        $perm = Permission::firstOrCreate(['name' => 'reports.view', 'guard_name' => 'web']);

        $response = $this->actingAs($superadmin)->putJson("/admin/user/{$targetAdmin->hashed_id}/permissions", [
            'permissions' => [['name' => $perm->name, 'expires_at' => null]],
        ]);

        $response->assertOk();
        $this->assertTrue($targetAdmin->fresh()->hasSpecificPermission('reports.view'));
    }

    // ── Autorización ─────────────────────────────────────────────────────────

    public function test_superadmin_can_view_permissions_of_any_user() : void
    {
        $superadmin = $this->makeSuperadmin();
        $player = $this->makePlayer();

        $response = $this->actingAs($superadmin)->getJson("/admin/user/{$player->hashed_id}/permissions");

        $response->assertOk()
            ->assertJsonStructure(['via_roles', 'specific', 'catalog']);
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
        return (string) (400000000000000000 + (++self::$idCounter));
    }

    private function seedRoles() : void
    {
        // roles.id es Snowflake (no autoincrement) y Spatie\Role no conoce el
        // trait SnowflakeId — mismo patrón que PermissionControllerTest::seedRoles().
        foreach (['superadmin', 'admin', 'player'] as $name) {
            if (! Role::where('name', $name)->where('guard_name', 'web')->exists()) {
                $role = new Role([
                    'name' => $name,
                    'guard_name' => 'web',
                    'prefix' => $name,
                    'type' => 'web',
                    'status' => true,
                ]);
                $role->id = $this->nextId();
                $role->save();
            }
        }
    }
}
