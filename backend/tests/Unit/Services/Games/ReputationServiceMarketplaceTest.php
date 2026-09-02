<?php

namespace Tests\Unit\Services\Games;

use App\Domains\Marketplace\Escrow\Models\Escrow;
use App\Domains\Marketplace\Inventory\Models\AssetDefinition;
use App\Domains\Marketplace\Inventory\Models\PlayerAsset;
use App\Domains\Marketplace\Models\Listing;
use App\Domains\Marketplace\Models\Order;
use App\Models\Currency;
use App\Models\User;
use App\Services\Games\ReputationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReputationServiceMarketplaceTest extends TestCase
{
    use RefreshDatabase;

    private ReputationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ReputationService::class);
    }

    private function makeEscrow(User $buyer, User $seller, ?string $resolution): Escrow
    {
        $currency = Currency::firstOrCreate(['iso_code' => 'USD'], ['name' => 'Dollar', 'type' => 'FIAT']);
        $definition = AssetDefinition::create([
            'code' => 'TEST_REP_' . uniqid(), 'name' => 'x',
            'category' => AssetDefinition::CATEGORY_EQUIPMENT, 'stackable' => false,
            'tradable' => true, 'transferable' => true, 'consumable' => false,
            'marketplace_enabled' => true, 'status' => AssetDefinition::STATUS_ACTIVE,
        ]);
        $asset = PlayerAsset::create([
            'asset_definition_id' => $definition->id, 'owner_id' => $seller->id,
            'quantity' => 1, 'status' => PlayerAsset::STATUS_AVAILABLE, 'acquired_via' => 'ADMIN_GRANT',
        ]);
        $listing = Listing::create([
            'seller_id' => $seller->id, 'player_asset_id' => $asset->id,
            'quantity' => 1, 'price' => 50, 'currency_id' => $currency->id, 'status' => Listing::STATUS_SOLD,
        ]);
        $order = Order::create([
            'listing_id' => $listing->id, 'buyer_id' => $buyer->id,
            'quantity' => 1, 'price' => 50, 'fee_amount' => 0, 'status' => Order::STATUS_COMPLETED,
        ]);

        return Escrow::create([
            'order_id' => $order->id, 'buyer_id' => $buyer->id, 'seller_id' => $seller->id,
            'player_asset_id' => $asset->id, 'quantity' => 1, 'amount' => 50, 'fee_amount' => 0,
            'seller_amount' => 50, 'currency_id' => $currency->id, 'status' => Escrow::STATUS_REFUNDED,
            'expires_at' => now(), 'resolution' => $resolution,
        ]);
    }

    public function test_lost_dispute_as_buyer_penalizes_score(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $this->makeEscrow($buyer, $seller, Escrow::RESOLUTION_SELLER_WINS);

        $reputation = $this->service->recalculate($buyer);

        $this->assertSame(95, $reputation->score);
    }

    public function test_lost_dispute_as_seller_penalizes_score(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $this->makeEscrow($buyer, $seller, Escrow::RESOLUTION_BUYER_WINS);

        $reputation = $this->service->recalculate($seller);

        $this->assertSame(95, $reputation->score);
    }

    public function test_won_dispute_does_not_penalize(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        // BUYER_WINS no penaliza al buyer, solo al seller.
        $this->makeEscrow($buyer, $seller, Escrow::RESOLUTION_BUYER_WINS);

        $reputation = $this->service->recalculate($buyer);

        $this->assertSame(100, $reputation->score);
    }

    public function test_cancelled_resolution_does_not_penalize_anyone(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $this->makeEscrow($buyer, $seller, Escrow::RESOLUTION_CANCELLED);

        $this->assertSame(100, $this->service->recalculate($buyer)->score);
        $this->assertSame(100, $this->service->recalculate($seller)->score);
    }
}
