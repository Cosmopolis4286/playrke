<?php

namespace Tests\Feature\Payments;

use App\Domains\Marketplace\Inventory\Models\AssetDefinition;
use App\Domains\Payments\Contracts\BlockchainProviderInterface;
use App\Domains\Payments\Models\PaymentOrder;
use App\Domains\Payments\Models\PaymentProduct;
use App\Domains\Payments\Providers\MockBlockchainProvider;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PaymentOrderAdminControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private PaymentProduct $product;

    protected function setUp() : void
    {
        parent::setUp();

        $this->app->singleton(BlockchainProviderInterface::class, MockBlockchainProvider::class);

        Permission::firstOrCreate(['name' => 'payments.manage', 'guard_name' => 'web']);
        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo('payments.manage');
        $this->makeRole('admin');
        $this->admin->assignRole('admin');

        $definition = AssetDefinition::create([
            'code' => 'TEST_ADMIN_PACK', 'name' => 'Pack admin',
            'category' => AssetDefinition::CATEGORY_TICKET, 'stackable' => true,
            'tradable' => false, 'transferable' => false, 'consumable' => true,
            'marketplace_enabled' => false, 'status' => AssetDefinition::STATUS_ACTIVE,
        ]);

        $this->product = PaymentProduct::create([
            'code' => 'TEST_ADMIN_PACK', 'name' => 'Pack admin',
            'asset_definition_id' => $definition->id, 'asset_quantity' => 5,
            'price_usdt' => 2.0, 'status' => PaymentProduct::STATUS_ACTIVE,
        ]);
    }

    public function test_admin_can_list_orders() : void
    {
        $this->makeOrder();

        $response = $this->actingAs($this->admin)->getJson('/payments-orders/fetch-list');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_admin_can_reevaluate_pending_order() : void
    {
        $order = $this->makeOrder(PaymentOrder::STATUS_PENDING);

        $response = $this->actingAs($this->admin)->postJson("/payments-orders/{$order->id}/reevaluate");

        $response->assertOk();
    }

    public function test_cannot_reevaluate_terminal_order() : void
    {
        $order = $this->makeOrder(PaymentOrder::STATUS_CREDITED);

        $response = $this->actingAs($this->admin)->postJson("/payments-orders/{$order->id}/reevaluate");

        $response->assertForbidden();
    }

    public function test_filters_by_status() : void
    {
        $this->makeOrder(PaymentOrder::STATUS_PENDING);

        $response = $this->actingAs($this->admin)->getJson('/payments-orders/fetch-list?status=CREDITED');

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }

    public function test_non_admin_cannot_access() : void
    {
        $player = User::factory()->create();
        $this->makeRole('player');
        $player->assignRole('player');

        $response = $this->actingAs($player)->getJson('/payments-orders/fetch-list');

        $response->assertForbidden();
    }

    private function makeOrder(string $status = PaymentOrder::STATUS_PENDING) : PaymentOrder
    {
        $user = User::factory()->create();

        return PaymentOrder::create([
            'user_id' => $user->id, 'payment_product_id' => $this->product->id,
            'network' => 'TRON', 'token' => 'USDT', 'token_contract' => 'fake-contract',
            'derivation_index' => ((int) PaymentOrder::max('derivation_index')) + 1,
            'deposit_address' => 'TADMIN' . uniqid(),
            'expected_amount' => 2.0, 'status' => $status,
            'expires_at' => now()->addMinutes(30),
        ]);
    }

    /** Mismo patrón que EscrowAdminControllerTest::makeRole(). */
    private function makeRole(string $name) : void
    {
        if (Role::where('name', $name)->where('guard_name', 'web')->exists()) {
            return;
        }

        $role = new Role(['name' => $name, 'guard_name' => 'web', 'prefix' => strtoupper($name), 'type' => 'web']);
        $role->id = Role::generateSnowflakeId();
        $role->save();
    }
}
