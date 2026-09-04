<?php

namespace Tests\Feature\Payments;

use App\Domains\Marketplace\Inventory\Models\AssetDefinition;
use App\Domains\Marketplace\Inventory\Models\PlayerAsset;
use App\Domains\Payments\Contracts\BlockchainProviderInterface;
use App\Domains\Payments\Exceptions\InvalidPaymentOrderStateException;
use App\Domains\Payments\Models\PaymentOrder;
use App\Domains\Payments\Models\PaymentProduct;
use App\Domains\Payments\Models\Purchase;
use App\Domains\Payments\Providers\MockBlockchainProvider;
use App\Domains\Payments\Services\PurchaseService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseServiceTest extends TestCase
{
    use RefreshDatabase;

    private PurchaseService $service;
    private PaymentProduct $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->singleton(BlockchainProviderInterface::class, MockBlockchainProvider::class);

        $definition = AssetDefinition::create([
            'code' => 'TEST_PURCHASE_PACK', 'name' => 'Pack de prueba',
            'category' => AssetDefinition::CATEGORY_TICKET, 'stackable' => true,
            'tradable' => false, 'transferable' => false, 'consumable' => true,
            'marketplace_enabled' => false, 'status' => AssetDefinition::STATUS_ACTIVE,
        ]);
        AssetDefinition::forgetCatalogCache();

        $this->product = PaymentProduct::create([
            'code' => 'TEST_PURCHASE_PACK', 'name' => 'Pack de prueba',
            'asset_definition_id' => $definition->id, 'asset_quantity' => 3,
            'price_usdt' => 2.0, 'status' => PaymentProduct::STATUS_ACTIVE,
        ]);

        $this->service = app(PurchaseService::class);
    }

    private function confirmedOrder(User $user): PaymentOrder
    {
        return PaymentOrder::create([
            'user_id' => $user->id, 'payment_product_id' => $this->product->id,
            'network' => 'TRON', 'token' => 'USDT', 'token_contract' => 'fake-contract',
            'derivation_index' => 1, 'deposit_address' => 'TFAKE',
            'expected_amount' => 2.0, 'received_amount' => 2.0,
            'status' => PaymentOrder::STATUS_CONFIRMED, 'expires_at' => now()->addMinutes(30),
        ]);
    }

    public function test_deliver_grants_the_asset_and_marks_order_credited(): void
    {
        $user = User::factory()->create();
        $order = $this->confirmedOrder($user);

        $purchase = $this->service->deliver($order);

        $this->assertSame(PaymentOrder::STATUS_CREDITED, $order->fresh()->status);
        $this->assertSame((string) $user->id, $purchase->user_id);

        $asset = PlayerAsset::find($purchase->player_asset_id);
        $this->assertSame(3, $asset->quantity);
        $this->assertSame('USDT_PURCHASE', $asset->acquired_via);
    }

    public function test_deliver_is_idempotent_never_grants_twice(): void
    {
        $user = User::factory()->create();
        $order = $this->confirmedOrder($user);

        $first = $this->service->deliver($order);
        $second = $this->service->deliver($order->fresh());

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Purchase::where('payment_order_id', $order->id)->count());
        $this->assertSame(3, PlayerAsset::find($first->player_asset_id)->quantity);
    }

    public function test_deliver_throws_for_order_not_confirmed(): void
    {
        $user = User::factory()->create();
        $order = $this->confirmedOrder($user);
        $order->update(['status' => PaymentOrder::STATUS_PENDING]);

        $this->expectException(InvalidPaymentOrderStateException::class);
        $this->service->deliver($order->fresh());
    }
}
