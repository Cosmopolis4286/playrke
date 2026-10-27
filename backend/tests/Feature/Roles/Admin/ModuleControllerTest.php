<?php

namespace Tests\Feature\Roles\Admin;

use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleControllerTest extends TestCase
{
    use RefreshDatabase;

    private static int $idCounter = 0;

    protected function setUp() : void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_guest_is_redirected_to_login() : void
    {
        $this->getJson('/modules/fetch-list')->assertStatus(401);
    }

    public function test_non_superadmin_cannot_access_modules_routes() : void
    {
        $player = $this->makePlayer();
        $module = Module::create(['key' => 'dashboard', 'label' => 'Tablero']);

        $this->actingAs($player)->getJson('/modules/fetch-list')->assertStatus(403);
        $this->actingAs($player)->putJson("/modules/{$module->id}/status")->assertStatus(403);
    }

    public function test_superadmin_can_list_modules() : void
    {
        $superadmin = $this->makeSuperadmin();
        Module::create(['key' => 'dashboard', 'label' => 'Tablero']);

        $response = $this->actingAs($superadmin)->getJson('/modules/fetch-list');

        $response->assertOk()->assertJsonCount(1, 'modules');
    }

    public function test_superadmin_can_toggle_module_status() : void
    {
        $superadmin = $this->makeSuperadmin();
        $module = Module::create(['key' => 'wallet', 'label' => 'Billetera', 'is_active' => true]);

        $response = $this->actingAs($superadmin)->putJson("/modules/{$module->id}/status");

        $response->assertOk();
        $this->assertFalse($module->fresh()->is_active);

        $this->actingAs($superadmin)->putJson("/modules/{$module->id}/status")->assertOk();
        $this->assertTrue($module->fresh()->is_active);
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
        foreach (['superadmin', 'player'] as $name) {
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
