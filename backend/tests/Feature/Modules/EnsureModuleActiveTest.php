<?php

namespace Tests\Feature\Modules;

use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Módulo apagado desde el panel: sus rutas quedan cerradas para jugadores
 * (antes solo desaparecía del menú) y abiertas para admin/superadmin.
 */
class EnsureModuleActiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp() : void
    {
        parent::setUp();
        Route::middleware(['web', 'module:games_trivia'])->get('/__test/module-gated', fn () => 'ok');
    }

    public function test_active_or_unknown_module_lets_everyone_in() : void
    {
        $this->actingAs(User::factory()->create())->get('/__test/module-gated')->assertOk();

        Module::create(['key' => 'games_trivia', 'label' => 'Trivia', 'is_active' => true]);
        $this->actingAs(User::factory()->create())->get('/__test/module-gated')->assertOk();
    }

    public function test_disabled_module_blocks_players_but_not_staff() : void
    {
        $module = Module::create(['key' => 'games_trivia', 'label' => 'Trivia', 'is_active' => false]);

        $this->actingAs(User::factory()->create())->get('/__test/module-gated')->assertNotFound();
        $this->actingAs(User::factory()->create())->getJson('/__test/module-gated')
            ->assertNotFound()
            ->assertJsonPath('message', 'Esta sección no está disponible por ahora.');

        $role = new Role(['name' => 'admin', 'guard_name' => 'web', 'prefix' => 'ADMIN', 'type' => 'web']);
        $role->id = Role::generateSnowflakeId();
        $role->save();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin)->get('/__test/module-gated')->assertOk();

        // Reactivarlo desde el panel se aplica en la siguiente request (caché invalidada).
        $module->update(['is_active' => true]);
        $this->actingAs(User::factory()->create())->get('/__test/module-gated')->assertOk();
    }

    public function test_real_trivia_and_marketplace_routes_are_gated() : void
    {
        foreach (['trivia.lobby' => 'games_trivia', 'marketplace.index' => 'marketplace'] as $name => $key) {
            $this->assertContains("module:{$key}", Route::getRoutes()->getByName($name)->gatherMiddleware(), $name);
        }
    }
}
