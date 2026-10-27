<?php

namespace Tests\Feature\Payments;

use App\Domains\Marketplace\Inventory\Models\AssetDefinition;
use App\Domains\Payments\Contracts\BlockchainProviderInterface;
use App\Domains\Payments\DTOs\InboundTransaction;
use App\Domains\Payments\Models\PaymentOrder;
use App\Domains\Payments\Models\PaymentProduct;
use App\Domains\Payments\Providers\MockBlockchainProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WatchPaymentOrdersCommandTest extends TestCase
{
    use RefreshDatabase;

    private const CONTRACT = 'fake-usdt-contract';

    private MockBlockchainProvider $blockchain;

    protected function setUp() : void
    {
        parent::setUp();

        $this->blockchain = new MockBlockchainProvider;
        $this->app->singleton(BlockchainProviderInterface::class, fn () => $this->blockchain);
    }

    public function test_advances_pending_order_to_detected_on_confirmed_payment() : void
    {
        $order = $this->makeOrder();

        $this->blockchain->pushTransaction(new InboundTransaction(
            txHash: 'watch-tx-1',
            fromAddress: 'from',
            toAddress: 'TWATCH1',
            tokenContract: self::CONTRACT,
            amount: 10.0,
            blockNumber: 100,
            timestamp: time(),
            confirmations: 1,
        ));

        $this->artisan('payments:watch-orders')->assertExitCode(0);

        $this->assertNotSame(PaymentOrder::STATUS_PENDING, $order->fresh()->status);
    }

    public function test_command_does_not_fail_when_no_active_orders() : void
    {
        $this->artisan('payments:watch-orders')->assertExitCode(0);
    }

    public function test_expires_overdue_order_without_touching_blockchain() : void
    {
        $order = $this->makeOrder();
        $order->update(['expires_at' => now()->subMinute()]);

        $this->artisan('payments:watch-orders')->assertExitCode(0);

        $this->assertSame(PaymentOrder::STATUS_EXPIRED, $order->fresh()->status);
    }

    private function makeOrder() : PaymentOrder
    {
        $user = User::factory()->create();

        $definition = AssetDefinition::create([
            'code' => 'TEST_WATCH_PACK', 'name' => 'Pack watch',
            'category' => AssetDefinition::CATEGORY_TICKET, 'stackable' => true,
            'tradable' => false, 'transferable' => false, 'consumable' => true,
            'marketplace_enabled' => false, 'status' => AssetDefinition::STATUS_ACTIVE,
        ]);

        $product = PaymentProduct::create([
            'code' => 'TEST_WATCH_PACK', 'name' => 'Pack watch',
            'asset_definition_id' => $definition->id, 'asset_quantity' => 1,
            'price_usdt' => 10.0, 'status' => PaymentProduct::STATUS_ACTIVE,
        ]);

        return PaymentOrder::create([
            'user_id' => $user->id, 'payment_product_id' => $product->id,
            'network' => 'TRON', 'token' => 'USDT', 'token_contract' => self::CONTRACT,
            'derivation_index' => 1, 'deposit_address' => 'TWATCH1',
            'expected_amount' => 10.0, 'status' => PaymentOrder::STATUS_PENDING,
            'expires_at' => now()->addMinutes(30),
        ]);
    }
}
