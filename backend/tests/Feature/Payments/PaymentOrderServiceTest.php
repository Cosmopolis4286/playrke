<?php

namespace Tests\Feature\Payments;

use App\Domains\Marketplace\Inventory\Models\AssetDefinition;
use App\Domains\Payments\Contracts\BlockchainProviderInterface;
use App\Domains\Payments\Exceptions\PaymentProductNotAvailableException;
use App\Domains\Payments\Models\PaymentOrder;
use App\Domains\Payments\Models\PaymentProduct;
use App\Domains\Payments\Providers\MockBlockchainProvider;
use App\Domains\Payments\Services\PaymentOrderService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentOrderServiceTest extends TestCase
{
    use RefreshDatabase;
    private PaymentProduct $product;

    private PaymentOrderService $service;

    protected function setUp() : void
    {
        parent::setUp();

        $this->app->singleton(BlockchainProviderInterface::class, MockBlockchainProvider::class);

        $definition = AssetDefinition::create([
            'code' => 'TEST_PAYMENT_PACK', 'name' => 'Pack de prueba',
            'category' => AssetDefinition::CATEGORY_TICKET, 'stackable' => true,
            'tradable' => false, 'transferable' => false, 'consumable' => true,
            'marketplace_enabled' => false, 'status' => AssetDefinition::STATUS_ACTIVE,
        ]);

        $this->product = PaymentProduct::create([
            'code' => 'TEST_PACK', 'name' => 'Pack de prueba',
            'asset_definition_id' => $definition->id, 'asset_quantity' => 5,
            'price_usdt' => 1.5, 'status' => PaymentProduct::STATUS_ACTIVE,
        ]);

        $this->service = app(PaymentOrderService::class);
    }

    public function test_create_derives_a_deposit_address_and_sets_expected_amount() : void
    {
        $user = User::factory()->create();

        $order = $this->service->create((string) $user->id, 'TEST_PACK');

        $this->assertSame(PaymentOrder::STATUS_PENDING, $order->status);
        $this->assertSame(1.5, $order->expected_amount);
        $this->assertSame('TRON', $order->network);
        $this->assertNotEmpty($order->deposit_address);
        $this->assertGreaterThan(0, $order->derivation_index);
        $this->assertTrue($order->expires_at->isFuture());
    }

    public function test_create_throws_for_inactive_product() : void
    {
        $this->product->update(['status' => PaymentProduct::STATUS_INACTIVE]);
        $user = User::factory()->create();

        $this->expectException(PaymentProductNotAvailableException::class);
        $this->service->create((string) $user->id, 'TEST_PACK');
    }

    public function test_create_throws_for_unknown_product_code() : void
    {
        $user = User::factory()->create();

        $this->expectException(PaymentProductNotAvailableException::class);
        $this->service->create((string) $user->id, 'DOES_NOT_EXIST');
    }

    public function test_find_for_user_never_returns_another_users_order() : void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $order = $this->service->create((string) $owner->id, 'TEST_PACK');

        $this->assertNull($this->service->findForUser($order->id, (string) $other->id));
        $this->assertNotNull($this->service->findForUser($order->id, (string) $owner->id));
    }

    public function test_two_orders_never_share_a_deposit_address() : void
    {
        $user = User::factory()->create();

        $first = $this->service->create((string) $user->id, 'TEST_PACK');
        $second = $this->service->create((string) $user->id, 'TEST_PACK');

        $this->assertNotSame($first->deposit_address, $second->deposit_address);
        $this->assertNotSame($first->derivation_index, $second->derivation_index);
    }
}
