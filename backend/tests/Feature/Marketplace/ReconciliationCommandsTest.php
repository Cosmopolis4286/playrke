<?php

namespace Tests\Feature\Marketplace;

use App\Domains\Marketplace\Escrow\Models\Escrow;
use App\Domains\Marketplace\Inventory\Models\AssetDefinition;
use App\Domains\Marketplace\Inventory\Models\PlayerAsset;
use App\Domains\Marketplace\Models\Listing;
use App\Domains\Marketplace\Models\Order;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ReconciliationCommandsTest extends TestCase
{
    use RefreshDatabase;

    private Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();
        $this->currency = Currency::firstOrCreate(['iso_code' => 'USD'], ['name' => 'Dollar', 'type' => 'FIAT']);
    }

    private function makeAsset(User $owner): PlayerAsset
    {
        $definition = AssetDefinition::create([
            'code' => 'TEST_RECON_' . uniqid(), 'name' => 'x',
            'category' => AssetDefinition::CATEGORY_EQUIPMENT, 'stackable' => false,
            'tradable' => true, 'transferable' => true, 'consumable' => false,
            'marketplace_enabled' => true, 'status' => AssetDefinition::STATUS_ACTIVE,
        ]);

        return PlayerAsset::create([
            'asset_definition_id' => $definition->id, 'owner_id' => $owner->id,
            'quantity' => 1, 'status' => PlayerAsset::STATUS_AVAILABLE, 'acquired_via' => 'ADMIN_GRANT',
        ]);
    }

    public function test_escrow_reconciliation_logs_stale_escrows(): void
    {
        Log::spy();

        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $asset = $this->makeAsset($seller);
        $listing = Listing::create([
            'seller_id' => $seller->id, 'player_asset_id' => $asset->id,
            'quantity' => 1, 'price' => 50, 'currency_id' => $this->currency->id, 'status' => Listing::STATUS_RESERVED,
        ]);
        $order = Order::create([
            'listing_id' => $listing->id, 'buyer_id' => $buyer->id,
            'quantity' => 1, 'price' => 50, 'fee_amount' => 0, 'status' => Order::STATUS_ESCROW,
        ]);
        Escrow::create([
            'order_id' => $order->id, 'buyer_id' => $buyer->id, 'seller_id' => $seller->id,
            'player_asset_id' => $asset->id, 'quantity' => 1, 'amount' => 50, 'fee_amount' => 0,
            'seller_amount' => 50, 'currency_id' => $this->currency->id,
            'status' => Escrow::STATUS_READY_TO_SETTLE, 'expires_at' => now()->subMinutes(5),
        ]);

        $this->artisan('marketplace:reconcile-escrows')->assertExitCode(0);

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn ($message) => str_contains($message, 'Escrow vencido sin resolver'));
    }

    public function test_escrow_reconciliation_silent_when_nothing_stale(): void
    {
        Log::spy();

        $this->artisan('marketplace:reconcile-escrows')->assertExitCode(0);

        Log::shouldNotHaveReceived('warning');
    }

    public function test_marketplace_reconciliation_detects_sold_listing_without_completed_order(): void
    {
        Log::spy();

        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $asset = $this->makeAsset($seller);
        $listing = Listing::create([
            'seller_id' => $seller->id, 'player_asset_id' => $asset->id,
            'quantity' => 1, 'price' => 50, 'currency_id' => $this->currency->id, 'status' => Listing::STATUS_SOLD,
        ]);
        Order::create([
            'listing_id' => $listing->id, 'buyer_id' => $buyer->id,
            'quantity' => 1, 'price' => 50, 'fee_amount' => 0, 'status' => Order::STATUS_CANCELLED,
        ]);

        $this->artisan('marketplace:reconcile')->assertExitCode(0);

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn ($message) => str_contains($message, 'Listings SOLD sin Order COMPLETED'));
    }

    public function test_marketplace_reconciliation_silent_when_consistent(): void
    {
        Log::spy();

        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $asset = $this->makeAsset($seller);
        $listing = Listing::create([
            'seller_id' => $seller->id, 'player_asset_id' => $asset->id,
            'quantity' => 1, 'price' => 50, 'currency_id' => $this->currency->id, 'status' => Listing::STATUS_SOLD,
        ]);
        Order::create([
            'listing_id' => $listing->id, 'buyer_id' => $buyer->id,
            'quantity' => 1, 'price' => 50, 'fee_amount' => 0, 'status' => Order::STATUS_COMPLETED,
        ]);

        $this->artisan('marketplace:reconcile')->assertExitCode(0);

        Log::shouldNotHaveReceived('warning');
    }
}
