<?php

namespace Tests\Unit\Domains\Marketplace;

use App\Domains\Marketplace\AntiFraud\MarketplaceFraudService;
use App\Domains\Marketplace\Inventory\Models\AssetDefinition;
use App\Domains\Marketplace\Inventory\Services\InventoryService;
use App\Domains\Marketplace\Models\Listing;
use App\Domains\Marketplace\Models\Order;
use App\Domains\Marketplace\Services\ListingService;
use App\Domains\Marketplace\Services\OrderService;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceFraudServiceTest extends TestCase
{
    use RefreshDatabase;

    private MarketplaceFraudService $fraudService;
    private InventoryService $inventory;
    private ListingService $listingService;
    private OrderService $orderService;
    private Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fraudService = app(MarketplaceFraudService::class);
        $this->inventory = app(InventoryService::class);
        $this->listingService = app(ListingService::class);
        $this->orderService = app(OrderService::class);
        $this->currency = Currency::firstOrCreate(['iso_code' => 'USD'], ['name' => 'Dollar', 'type' => 'FIAT']);
    }

    private function tradableDefinition(): AssetDefinition
    {
        return AssetDefinition::create([
            'code' => 'TEST_FRAUD_TRADABLE', 'name' => 'Test Tradable',
            'category' => AssetDefinition::CATEGORY_EQUIPMENT, 'stackable' => false,
            'tradable' => true, 'transferable' => true, 'consumable' => false,
            'marketplace_enabled' => true, 'status' => AssetDefinition::STATUS_ACTIVE,
        ]);
    }

    public function test_is_self_trade_detects_same_user(): void
    {
        $this->assertTrue($this->fraudService->isSelfTrade('1', '1'));
        $this->assertFalse($this->fraudService->isSelfTrade('1', '2'));
    }

    public function test_check_double_submit_throws_on_repeated_call(): void
    {
        $this->fraudService->checkDoubleSubmit('user-1', 'listing-1');

        $this->expectException(\DomainException::class);
        $this->fraudService->checkDoubleSubmit('user-1', 'listing-1');
    }

    public function test_check_listing_velocity_throws_after_limit(): void
    {
        $definition = $this->tradableDefinition();
        $seller = User::factory()->create();

        // MAX_LISTINGS_PER_DAY es privado y alto (50) — en vez de crear 50
        // listings reales, se inserta el conteo directamente vía DB para
        // simular el límite alcanzado sin pagar el costo de 50 inserts reales.
        $rows = [];
        for ($i = 0; $i < 50; $i++) {
            $asset = $this->inventory->grant($definition, (string) $seller->id, 1, 'ADMIN_GRANT');
            $rows[] = [
                'id' => Listing::generateSnowflakeId(),
                'seller_id' => $seller->id,
                'player_asset_id' => $asset->id,
                'quantity' => 1,
                'price' => 10,
                'currency_id' => $this->currency->id,
                'status' => Listing::STATUS_CANCELLED,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        \Illuminate\Support\Facades\DB::table('marketplace_listings')->insert($rows);

        $this->expectException(\DomainException::class);
        $this->fraudService->checkListingVelocity((string) $seller->id);
    }

    public function test_detect_circular_trade_finds_buyback_within_window(): void
    {
        $definition = $this->tradableDefinition();
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // A vende a B
        $assetA = $this->inventory->grant($definition, (string) $userA->id, 1, 'ADMIN_GRANT');
        $listing1 = $this->listingService->create($assetA, (string) $userA->id, 1, 50.0, $this->currency->id);
        $order1 = $this->orderService->buy($listing1->fresh(), (string) $userB->id, 1);
        Order::where('id', $order1->id)->update(['status' => Order::STATUS_COMPLETED]);

        // B revende el mismo tipo de activo de vuelta a A
        $assetB = $this->inventory->grant($definition, (string) $userB->id, 1, 'ADMIN_GRANT');
        $listing2 = $this->listingService->create($assetB, (string) $userB->id, 1, 50.0, $this->currency->id);
        $order2 = $this->orderService->buy($listing2->fresh(), (string) $userA->id, 1);

        $this->assertTrue($this->fraudService->detectCircularTrade($order2->fresh(['listing.playerAsset'])));
    }

    public function test_detect_circular_trade_returns_false_without_prior_reverse_sale(): void
    {
        $definition = $this->tradableDefinition();
        $seller = User::factory()->create();
        $buyer = User::factory()->create();

        $asset = $this->inventory->grant($definition, (string) $seller->id, 1, 'ADMIN_GRANT');
        $listing = $this->listingService->create($asset, (string) $seller->id, 1, 50.0, $this->currency->id);
        $order = $this->orderService->buy($listing->fresh(), (string) $buyer->id, 1);

        $this->assertFalse($this->fraudService->detectCircularTrade($order->fresh(['listing.playerAsset'])));
    }

    public function test_is_anomalous_price_false_without_enough_history(): void
    {
        $definition = $this->tradableDefinition();

        $this->assertFalse($this->fraudService->isAnomalousPrice($definition->id, 999999.0));
    }
}
