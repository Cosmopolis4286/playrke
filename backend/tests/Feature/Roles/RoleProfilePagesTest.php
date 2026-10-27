<?php

namespace Tests\Feature\Roles;

use App\Models\Country;
use App\Models\Currency;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Roles\Commerce;
use App\Models\Roles\Seller;
use App\Models\Roles\Subsidiary;
use App\Models\Shared\UniqueCode;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Fichas de autogestión "Mi Comercio" / "Mi Agencia" / "Mi Perfil de
 * Vendedor" (RoleProfileView + *ProfileService + RoleProfileInsightsService):
 *
 *  - Cada rol ve SU parte del reparto en cascada (vendedor X, agencia Y − X,
 *    comercializadora Z − max(X, Y)); los lotes REFUNDED no cuentan.
 *  - Cadena jerárquica, red, deuda pendiente con la plataforma y alertas.
 *  - Request de edición: teléfono formateado de 16 chars (antes max:14
 *    rechazaba guardar la ficha sin tocarlo) y edición por admin vía
 *    target_user_id (antes authorize() devolvía 403).
 *
 * Escenario: Comercializadora C (5%) → Agencia S (4%) → Vendedor V (3%).
 * V vende 120 (comisiones 3,60 / 1,20 / 1,20) + un lote REFUNDED de 50.
 */
class RoleProfilePagesTest extends TestCase
{
    use DatabaseTransactions;

    private static int $idCounter = 0;
    private Commerce $commerce;
    private User $commerceUser;

    private Country $country;
    private Currency $currency;
    private Seller $seller;
    private User $sellerUser;
    private Subsidiary $subsidiary;
    private User $subsidiaryUser;

    protected function setUp() : void
    {
        parent::setUp();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $this->country = Country::firstOrCreate(['iso_code_2' => 'VE'], ['name' => 'Venezuela', 'iso_code_3' => 'VEN', 'status' => 'ACTIVE']);
        $this->currency = Currency::firstOrCreate(
            ['iso_code' => 'VES'],
            ['symbol' => 'Bs', 'name' => 'Bolívar', 'type' => 'FIAT', 'decimal_places' => 2, 'status' => 'ACTIVE'],
        );

        $this->seedRolesAndPermissions();

        $this->commerceUser = $this->makeUserWithRole('commerce');
        $this->commerce = Commerce::create([
            'name' => 'Comercio Perfil', 'country_id' => $this->country->id, 'phone' => '+58 0412-1000001',
            'user_id' => $this->commerceUser->id, 'commission' => 5.00, 'status' => 'ACTIVE',
            'unique_code_id' => UniqueCode::create([
                'rke_code' => 'RKE-' . $this->nextId(), 'uuid_code' => (string) Str::uuid(),
                'user_id' => $this->commerceUser->id, 'status' => 'ACTIVE',
            ])->id,
        ]);

        $this->subsidiaryUser = $this->makeUserWithRole('subsidiary');
        $this->subsidiary = Subsidiary::create([
            'name' => 'Agencia Perfil', 'commerce_id' => $this->commerce->id, 'user_id' => $this->subsidiaryUser->id,
            'country_id' => $this->country->id, 'currency_id' => $this->currency->id, 'commission' => 4.00,
            'phone' => '+58 0412-1000002', 'status' => 'ACTIVE',
        ]);

        $this->sellerUser = $this->makeUserWithRole('seller');
        $this->seller = Seller::create([
            'id' => $this->nextId(), 'name' => 'Vendedor Perfil', 'phone' => '+58 0412-1000003', 'commission' => 3.00,
            'user_id' => $this->sellerUser->id, 'country_id' => $this->country->id,
            'manager_user_id' => $this->commerceUser->id, 'subsidiary_id' => $this->subsidiary->id, 'status' => 'ACTIVE',
        ]);

        $this->sell(120.00, 2, 3.60, 1.20, 1.20);
        $this->sell(50.00, 1, 1.50, 0.50, 0.50, 'REFUNDED');
    }

    protected function tearDown() : void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        parent::tearDown();
    }

    public function test_admin_can_edit_another_users_profile_via_target_user_id() : void
    {
        $admin = $this->makeUserWithRole('admin');

        $this->actingAs($admin)
            ->putJson('/seller/profile', [
                'name' => 'Vendedor Editado Por Admin',
                'phone' => '+58 0412-1000003',
                'target_user_id' => (string) $this->sellerUser->id,
            ])
            ->assertOk();

        $this->assertSame('Vendedor Editado Por Admin', $this->seller->fresh()->name);
    }

    public function test_admin_viewing_a_seller_profile_sees_every_rate_and_the_cap() : void
    {
        $admin = $this->makeUserWithRole('admin');

        $this->actingAs($admin)->get('/seller/profile?target_user_id=' . $this->sellerUser->id)
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where('isAdminView', true)
                    ->where('seller.hierarchy.chain.0.rate', 5)
                    ->where('seller.hierarchy.chain.1.rate', 4)
                    ->where('seller.hierarchy.cap', 4),
            );
    }

    public function test_commerce_profile_shows_network_margin_agencies_and_pending_receivables() : void
    {
        DB::table('platform_receivables')->insert([
            'id' => $this->nextId(), 'source' => 'BINGO_SALE', 'debtor_user_id' => $this->commerceUser->id,
            'currency_id' => $this->currency->id, 'amount' => 89.76, 'status' => 'PENDING',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->commerceUser)->get('/commerce/profile')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Roles/Commerce/Profile/Index')
                    ->where('commerce.performance.kpis.commission.value', 1.2)
                    ->where('commerce.performance.lifetime.lots', 1)
                    ->where('commerce.network.subsidiaries.0.name', 'Agencia Perfil')
                    ->where('commerce.network.top_sellers.0.cards', 2)
                    ->where('commerce.hierarchy.children_rate_range', ['min' => 4, 'max' => 4])
                    ->where('commerce.receivables.0.amount', 89.76),
            );
    }

    public function test_non_admin_cannot_edit_another_users_profile() : void
    {
        $this->actingAs($this->commerceUser)
            ->putJson('/commerce/profile', ['name' => 'Intruso', 'target_user_id' => (string) $this->sellerUser->id])
            ->assertForbidden();

        $this->assertSame('Comercio Perfil', $this->commerce->fresh()->name);
    }

    // ─── Edición ─────────────────────────────────────────────────────────────

    public function test_profile_saves_with_the_existing_formatted_phone() : void
    {
        // "+58 0412-1000001" = 16 chars: antes max:14 rechazaba guardar sin tocarlo.
        $this->actingAs($this->commerceUser)
            ->putJson('/commerce/profile', ['name' => 'Comercio Renombrado', 'phone' => '+58 0412-1000001'])
            ->assertOk();

        $this->assertSame('Comercio Renombrado', $this->commerce->fresh()->name);
    }

    // ─── Desempeño: cada rol ve su parte del reparto ─────────────────────────

    public function test_seller_profile_shows_own_commission_and_chain_but_hides_upper_rates() : void
    {
        $this->actingAs($this->sellerUser)->get('/seller/profile')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Roles/Seller/Profile/Index')
                    ->where('seller.role', 'seller')
                    ->where('seller.performance.kpis.sales.value', 120)
                    ->where('seller.performance.kpis.commission.value', 3.6)
                    ->where('seller.performance.kpis.cards.value', 2)
                    ->where('seller.performance.currency.iso', 'VES')
                    ->has('seller.performance.trend', 14)
                    ->has('seller.hierarchy.chain', 3)
                    ->where('seller.hierarchy.chain.2.is_self', true)
                    ->where('seller.hierarchy.chain.2.rate', 3)
                // Privacidad: cada nivel ve su comisión y la de los de abajo,
                // nunca la de arriba (ni su tope, que ES la de la agencia).
                    ->where('seller.hierarchy.chain.0.rate', null)
                    ->where('seller.hierarchy.chain.1.rate', null)
                    ->where('seller.hierarchy.cap', null)
                    ->where('seller.alerts', []),
            );
    }

    public function test_seller_with_inactive_agency_gets_a_cannot_sell_alert() : void
    {
        $this->subsidiary->update(['status' => 'INACTIVE']);

        $this->actingAs($this->sellerUser)->get('/seller/profile')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where('seller.alerts.0.level', 'danger')
                    ->where('seller.alerts.0.title', 'No puedes vender en este momento'),
            );
    }

    public function test_subsidiary_profile_shows_its_margin_and_its_sellers() : void
    {
        $this->actingAs($this->subsidiaryUser)->get('/subsidiary/profile')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Roles/Subsidiary/Profile/Index')
                    ->where('subsidiary.performance.kpis.commission.value', 1.2)
                    ->where('subsidiary.hierarchy.pass_through', false)
                    ->has('subsidiary.hierarchy.chain', 2)
                    ->where('subsidiary.hierarchy.chain.0.rate', null) // su comercializadora: oculta
                    ->where('subsidiary.hierarchy.chain.1.rate', 4)
                    ->has('subsidiary.network.sellers', 1)
                    ->where('subsidiary.network.sellers.0.month_cards', 2),
            );
    }

    private function makeUserWithRole(string $role) : User
    {
        $user = User::factory()->create(['country_id' => $this->country->id, 'email_verified_at' => now()]);
        $user->assignRole($role);

        return $user;
    }

    private function nextId() : string
    {
        return (string) (440000000000000000 + (++self::$idCounter));
    }

    // ─── Fixtures ────────────────────────────────────────────────────────────

    private function seedRolesAndPermissions() : void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $grants = [
            'commerce' => ['commerces.manage-own'],
            'subsidiary' => ['subsidiaries.manage-own'],
            'seller' => ['sellers.manage-own'],
            'admin' => ['commerces.view', 'subsidiaries.view', 'sellers.view'],
        ];

        foreach ($grants as $roleName => $permissions) {
            foreach ($permissions as $name) {
                Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            }
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if (! $role) {
                $role = (new Role)->forceFill(['name' => $roleName, 'guard_name' => 'web', 'prefix' => $roleName]);
                $role->save();
            }
            $role->givePermissionTo($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function sell(float $amount, int $cards, float $sellerCommission, float $subsidiaryCommission, float $commerceCommission, string $status = 'ACTIVE') : void
    {
        DB::table('bingo_card_lots')->insert([
            'id' => $this->nextId(), 'room_id' => $this->nextId(), 'user_id' => $this->commerceUser->id,
            'seller_user_id' => $this->sellerUser->id, 'seller_type' => 'SELLER', 'room_user_id' => $this->nextId(),
            'currency_id' => $this->currency->id, 'card_count' => $cards, 'card_price' => $amount / $cards,
            'exchange_rate' => 1, 'sale_amount' => $amount, 'commission_amount' => $sellerCommission,
            'subsidiary_id' => $this->subsidiary->id, 'commerce_id' => $this->commerce->id,
            'subsidiary_commission_amount' => $subsidiaryCommission, 'commerce_commission_amount' => $commerceCommission,
            'status' => $status, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
