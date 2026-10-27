<?php

namespace Tests\Feature\Payments;

use App\Domains\Marketplace\Inventory\Models\AssetDefinition;
use App\Domains\Payments\Models\PaymentOrder;
use App\Domains\Payments\Models\PaymentProduct;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PaymentOrderReconciliationCommandTest extends TestCase
{
    use RefreshDatabase;

    private PaymentProduct $product;

    protected function setUp() : void
    {
        parent::setUp();

        $definition = AssetDefinition::create([
            'code' => 'TEST_RECONCILE_PACK', 'name' => 'Pack reconcile',
            'category' => AssetDefinition::CATEGORY_TICKET, 'stackable' => true,
            'tradable' => false, 'transferable' => false, 'consumable' => true,
            'marketplace_enabled' => false, 'status' => AssetDefinition::STATUS_ACTIVE,
        ]);

        $this->product = PaymentProduct::create([
            'code' => 'TEST_RECONCILE_PACK', 'name' => 'Pack reconcile',
            'asset_definition_id' => $definition->id, 'asset_quantity' => 1,
            'price_usdt' => 5.0, 'status' => PaymentProduct::STATUS_ACTIVE,
        ]);
    }

    public function test_logs_stale_confirming_order() : void
    {
        Log::spy();

        $this->makeOrder(PaymentOrder::STATUS_CONFIRMING, now()->subMinutes(20));

        $this->artisan('payments:reconcile-orders')->assertExitCode(0);

        Log::shouldHaveReceived('warning')->once();
    }

    public function test_logs_stale_detected_order() : void
    {
        Log::spy();

        $this->makeOrder(PaymentOrder::STATUS_DETECTED, now()->subMinutes(30));

        $this->artisan('payments:reconcile-orders')->assertExitCode(0);

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn ($message) => str_contains($message, 'Orden estancada sin avanzar'));
    }

    public function test_silent_for_terminal_or_pending_orders() : void
    {
        Log::spy();

        $this->makeOrder(PaymentOrder::STATUS_CREDITED, now()->subMinutes(30));
        $this->makeOrder(PaymentOrder::STATUS_PENDING, now()->subMinutes(30));

        $this->artisan('payments:reconcile-orders')->assertExitCode(0);

        Log::shouldNotHaveReceived('warning');
    }

    public function test_silent_when_recently_updated() : void
    {
        Log::spy();

        $this->makeOrder(PaymentOrder::STATUS_DETECTED, now()->subMinutes(2));

        $this->artisan('payments:reconcile-orders')->assertExitCode(0);

        Log::shouldNotHaveReceived('warning');
    }

    private function makeOrder(string $status, DateTimeInterface $updatedAt) : PaymentOrder
    {
        $user = User::factory()->create();

        $order = PaymentOrder::create([
            'user_id' => $user->id, 'payment_product_id' => $this->product->id,
            'network' => 'TRON', 'token' => 'USDT', 'token_contract' => 'fake-contract',
            'derivation_index' => ((int) PaymentOrder::max('derivation_index')) + 1,
            'deposit_address' => 'TRECONCILE' . uniqid(),
            'expected_amount' => 5.0, 'status' => $status,
            'expires_at' => now()->addMinutes(30),
        ]);

        // updated_at es auto-gestionado por Eloquent — forzamos el valor
        // real vía query builder para simular una orden vieja sin re-tocar
        // el timestamp con save()/update().
        PaymentOrder::where('id', $order->id)->update(['updated_at' => $updatedAt]);

        return $order->fresh();
    }
}
