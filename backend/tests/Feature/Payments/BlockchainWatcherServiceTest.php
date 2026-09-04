<?php

namespace Tests\Feature\Payments;

use App\Domains\Marketplace\Crypto\Models\CryptoTransaction;
use App\Domains\Marketplace\Inventory\Models\AssetDefinition;
use App\Domains\Payments\Contracts\BlockchainProviderInterface;
use App\Domains\Payments\DTOs\InboundTransaction;
use App\Domains\Payments\Models\PaymentOrder;
use App\Domains\Payments\Models\PaymentProduct;
use App\Domains\Payments\Models\Purchase;
use App\Domains\Payments\Providers\MockBlockchainProvider;
use App\Domains\Payments\Services\BlockchainWatcherService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlockchainWatcherServiceTest extends TestCase
{
    use RefreshDatabase;

    private BlockchainWatcherService $watcher;
    private MockBlockchainProvider $blockchain;
    private PaymentProduct $product;
    private const CONTRACT = 'fake-usdt-contract';

    protected function setUp(): void
    {
        parent::setUp();

        config(['payments.tron.confirmations_required' => 19]);

        $this->blockchain = new MockBlockchainProvider();
        $this->app->instance(BlockchainProviderInterface::class, $this->blockchain);

        $definition = AssetDefinition::create([
            'code' => 'TEST_WATCHER_PACK', 'name' => 'Pack de prueba',
            'category' => AssetDefinition::CATEGORY_TICKET, 'stackable' => true,
            'tradable' => false, 'transferable' => false, 'consumable' => true,
            'marketplace_enabled' => false, 'status' => AssetDefinition::STATUS_ACTIVE,
        ]);
        AssetDefinition::forgetCatalogCache();

        $this->product = PaymentProduct::create([
            'code' => 'TEST_WATCHER_PACK', 'name' => 'Pack de prueba',
            'asset_definition_id' => $definition->id, 'asset_quantity' => 1,
            'price_usdt' => 10.0, 'status' => PaymentProduct::STATUS_ACTIVE,
        ]);

        $this->watcher = app(BlockchainWatcherService::class);
    }

    private function pendingOrder(User $user, float $expected = 10.0, string $address = 'TDEPOSIT1'): PaymentOrder
    {
        return PaymentOrder::create([
            'user_id' => $user->id, 'payment_product_id' => $this->product->id,
            'network' => 'TRON', 'token' => 'USDT', 'token_contract' => self::CONTRACT,
            'derivation_index' => ((int) PaymentOrder::max('derivation_index')) + 1, 'deposit_address' => $address,
            'expected_amount' => $expected, 'status' => PaymentOrder::STATUS_PENDING,
            'expires_at' => now()->addMinutes(30),
        ]);
    }

    private function incoming(string $txHash, string $toAddress, float $amount, int $confirmations): InboundTransaction
    {
        return new InboundTransaction(
            txHash: $txHash, fromAddress: 'TSENDER', toAddress: $toAddress,
            tokenContract: self::CONTRACT, amount: $amount, blockNumber: 1000,
            timestamp: time(), confirmations: $confirmations,
        );
    }

    public function test_full_happy_path_detects_confirms_and_delivers(): void
    {
        $user = User::factory()->create();
        $order = $this->pendingOrder($user);

        $tx = $this->incoming('tx-happy', $order->deposit_address, 10.0, 20);
        $this->blockchain->pushTransaction($tx);

        $result = $this->watcher->evaluate($order);

        $this->assertSame(PaymentOrder::STATUS_CREDITED, $result->status);
        $this->assertSame(1, Purchase::where('payment_order_id', $order->id)->count());
        $this->assertSame(1, CryptoTransaction::where('tx_hash', 'tx-happy')->count());
    }

    public function test_insufficient_confirmations_leaves_order_confirming(): void
    {
        $user = User::factory()->create();
        $order = $this->pendingOrder($user);

        $this->blockchain->pushTransaction($this->incoming('tx-low-conf', $order->deposit_address, 10.0, 3));

        $result = $this->watcher->evaluate($order);

        $this->assertSame(PaymentOrder::STATUS_CONFIRMING, $result->status);
        $this->assertSame(0, Purchase::where('payment_order_id', $order->id)->count());
    }

    public function test_underpaid_amount_never_gets_credited(): void
    {
        $user = User::factory()->create();
        $order = $this->pendingOrder($user, 10.0);

        $this->blockchain->pushTransaction($this->incoming('tx-under', $order->deposit_address, 5.0, 20));

        $result = $this->watcher->evaluate($order);

        $this->assertSame(PaymentOrder::STATUS_UNDERPAID, $result->status);
        $this->assertSame(0, Purchase::where('payment_order_id', $order->id)->count());
    }

    public function test_overpaid_amount_never_gets_credited_automatically(): void
    {
        $user = User::factory()->create();
        $order = $this->pendingOrder($user, 10.0);

        $this->blockchain->pushTransaction($this->incoming('tx-over', $order->deposit_address, 15.0, 20));

        $result = $this->watcher->evaluate($order);

        $this->assertSame(PaymentOrder::STATUS_OVERPAID, $result->status);
        $this->assertSame(0, Purchase::where('payment_order_id', $order->id)->count());
    }

    public function test_replaying_evaluate_after_credited_does_not_double_deliver(): void
    {
        $user = User::factory()->create();
        $order = $this->pendingOrder($user);
        $this->blockchain->pushTransaction($this->incoming('tx-replay', $order->deposit_address, 10.0, 20));

        $first = $this->watcher->evaluate($order);
        $second = $this->watcher->evaluate($first->fresh());

        $this->assertSame(PaymentOrder::STATUS_CREDITED, $second->status);
        $this->assertSame(1, Purchase::where('payment_order_id', $order->id)->count());
    }

    public function test_tx_hash_already_linked_to_another_order_is_rejected(): void
    {
        $user = User::factory()->create();
        $orderA = $this->pendingOrder($user, 10.0, 'TDEPOSIT_A');
        $orderB = $this->pendingOrder($user, 10.0, 'TDEPOSIT_B');

        // Mismo tx_hash "visto" en ambas direcciones (escenario de dirección
        // reutilizada por error/bug de un proveedor) — nunca debe acreditar
        // la segunda orden con una transacción ya asociada a la primera.
        $this->blockchain->pushTransaction($this->incoming('tx-shared', $orderA->deposit_address, 10.0, 20));
        $this->watcher->evaluate($orderA);

        $this->blockchain->pushTransaction($this->incoming('tx-shared', $orderB->deposit_address, 10.0, 20));
        $resultB = $this->watcher->evaluate($orderB->fresh());

        $this->assertSame(PaymentOrder::STATUS_PENDING, $resultB->status);
        $this->assertSame(0, Purchase::where('payment_order_id', $orderB->id)->count());
    }

    public function test_expired_order_is_never_evaluated_even_with_valid_payment(): void
    {
        $user = User::factory()->create();
        $order = $this->pendingOrder($user);
        $order->update(['expires_at' => now()->subMinute()]);

        $this->watcher->watchAll();

        $this->assertSame(PaymentOrder::STATUS_EXPIRED, $order->fresh()->status);
        $this->assertSame(0, Purchase::where('payment_order_id', $order->id)->count());
    }

    public function test_terminal_order_is_never_reevaluated(): void
    {
        $user = User::factory()->create();
        $order = $this->pendingOrder($user);
        $order->update(['status' => PaymentOrder::STATUS_CANCELLED]);

        $this->blockchain->pushTransaction($this->incoming('tx-cancelled', $order->deposit_address, 10.0, 20));
        $result = $this->watcher->evaluate($order);

        $this->assertSame(PaymentOrder::STATUS_CANCELLED, $result->status);
        $this->assertSame(0, Purchase::where('payment_order_id', $order->id)->count());
    }
}
