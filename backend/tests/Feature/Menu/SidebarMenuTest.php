<?php

namespace Tests\Feature\Menu;

use App\Models\Role;
use App\Models\SidebarMenuVisibility;
use App\Models\User;
use App\Models\UserSidebarFavorite;
use App\Services\Menu\SidebarMenuService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarMenuTest extends TestCase
{
    use RefreshDatabase;

    private static int $idCounter = 0;

    protected function setUp() : void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_dashboard_renders_sidebar_with_filter_and_favorite_controls() : void
    {
        $user = $this->makeSuperadmin();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('sidebar-filter-input', false);
        $response->assertSee('sidebar-favorite-toggle', false);
        $response->assertSee('data-menu-key', false);
    }

    public function test_favorite_to_item_hidden_by_role_is_not_returned() : void
    {
        $user = $this->makeSuperadmin();

        UserSidebarFavorite::create([
            'user_id' => $user->id,
            'item_key' => 'business.win_events',
            'sort_order' => 0,
        ]);

        $role = Role::where('name', 'superadmin')->first();
        SidebarMenuVisibility::create([
            'item_key' => 'business.win_events',
            'role_id' => $role->id,
            'is_visible' => false,
        ]);

        $service = app(SidebarMenuService::class);
        $this->actingAs($user);
        $favorites = $service->getFavorites($user);

        $this->assertEmpty(collect($favorites)->where('item_key', 'business.win_events'));
    }

    public function test_search_route_returns_menu_and_is_reachable() : void
    {
        $user = $this->makeSuperadmin();

        $response = $this->actingAs($user)->get('/search?q=Tablero');

        $response->assertOk();
        $response->assertSee('Tablero');
    }

    public function test_sort_order_override_reorders_root_menu_for_role() : void
    {
        $user = $this->makeSuperadmin();
        $role = Role::where('name', 'superadmin')->first();

        // 'system' normalmente aparece después de 'users' en $menuStructure;
        // forzamos que aparezca primero para superadmin.
        SidebarMenuVisibility::create([
            'item_key' => 'system',
            'role_id' => $role->id,
            'is_visible' => true,
            'sort_order' => 0,
        ]);

        $this->actingAs($user);
        $menu = app(SidebarMenuService::class)->generateMenu();

        $this->assertSame('system', $menu->keys()->first());
    }

    public function test_user_can_favorite_and_unfavorite_a_menu_item() : void
    {
        $user = $this->makeSuperadmin();

        $store = $this->actingAs($user)
            ->postJson('/sidebar/favorites', ['item_key' => 'dashboard']);
        $store->assertOk();

        $this->assertDatabaseHas('user_sidebar_favorites', [
            'user_id' => (string) $user->id,
            'item_key' => 'dashboard',
        ]);

        $service = app(SidebarMenuService::class);
        $this->actingAs($user);
        $favorites = $service->getFavorites($user);
        $this->assertNotEmpty(collect($favorites)->firstWhere('item_key', 'dashboard'));

        $destroy = $this->actingAs($user)
            ->deleteJson('/sidebar/favorites/dashboard');
        $destroy->assertOk();

        $this->assertDatabaseMissing('user_sidebar_favorites', [
            'user_id' => (string) $user->id,
            'item_key' => 'dashboard',
        ]);
    }

    public function test_wallet_movements_badge_uses_generic_counter() : void
    {
        $user = $this->makeSuperadmin();
        $this->actingAs($user);

        $menu = app(SidebarMenuService::class)->generateMenu();
        $walletSubmenu = collect($menu->get('wallet')['submenu'] ?? []);
        $movements = $walletSubmenu->firstWhere('route', 'wallet-movements.index');

        $this->assertNotNull($movements);
        $this->assertArrayHasKey('badge', $movements);
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
