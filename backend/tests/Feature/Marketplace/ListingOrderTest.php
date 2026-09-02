<?php

namespace Tests\Feature\Marketplace;

use App\Domains\Marketplace\Exceptions\InvalidListingException;
use App\Domains\Marketplace\Exceptions\ListingNoLongerAvailableException;
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

class ListingOrderTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $inventory;
    private ListingService $listingService;
    private OrderService $orderService;
    private Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventory = app(InventoryService::class);
        $this->listingService = app(ListingService::class);
        $this->orderService = app(OrderService::class);
        $this->currency = Currency::firstOrCreate(['iso_code' => 'USD'], ['name' => 'Dollar', 'type' => 'FIAT']);
    }

    private function tradableDefinition(): AssetDefinition
    {
        return AssetDefinition::create([
            'code' => 'TEST_TRADABLE', 'name' => 'Test Tradable',
            'category' => AssetDefinition::CATEGORY_EQUIPMENT, 'stackable' => false,
            'tradable' => true, 'transferable' => true, 'consumable' => false,
            'marketplace_enabled' => true, 'status' => AssetDefinition::STATUS_ACTIVE,
        ]);
    }

    public function test_create_listing_requires_ownership(): void
    {
        $definition = $this->tradableDefinition();
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $asset = $this->inventory->grant($definition, (string) $owner->id, 1, 'ADMIN_GRANT');

        $this->expectException(InvalidListingException::class);
        $this->listingService->create($asset, (string) $stranger->id, 1, 10.0, $this->currency->id);
    }

    public function test_create_listing_rejects_non_marketplace_enabled_definition(): void
    {
        $definition = AssetDefinition::create([
            'code' => 'TEST_NOT_ENABLED', 'name' => 'x',
            'category' => AssetDefinition::CATEGORY_TICKET, 'stackable' => true,
            'tradable' => true, 'transferable' => false, 'consumable' => true,
            'marketplace_enabled' => false, 'status' => AssetDefinition::STATUS_ACTIVE,
        ]);
        $owner = User::factory()->create();
        $asset = $this->inventory->grant($definition, (string) $owner->id, 1, 'ADMIN_GRANT');

        $this->expectException(InvalidListingException::class);
        $this->listingService->create($asset, (string) $owner->id, 1, 10.0, $this->currency->id);
    }

    public function test_buy_reserves_listing_and_creates_pending_order_with_fee(): void
    {
        config(['marketplace.default_fee_percent' => 5.0]);
        $definition = $this->tradableDefinition();
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $asset = $this->inventory->grant($definition, (string) $seller->id, 1, 'ADMIN_GRANT');
        $listing = $this->listingService->create($asset, (string) $seller->id, 1, 100.0, $this->currency->id);

        $order = $this->orderService->buy($listing->fresh(), (string) $buyer->id, 1);

        $this->assertSame(Order::STATUS_PENDING, $order->status);
        $this->assertSame(5.0, (float) $order->fee_amount);
        $this->assertSame(Listing::STATUS_RESERVED, $listing->fresh()->status);
    }

    public function test_buy_own_listing_is_rejected(): void
    {
        $definition = $this->tradableDefinition();
        $seller = User::factory()->create();
        $asset = $this->inventory->grant($definition, (string) $seller->id, 1, 'ADMIN_GRANT');
        $listing = $this->listingService->create($asset, (string) $seller->id, 1, 100.0, $this->currency->id);

        $this->expectException(InvalidListingException::class);
        $this->orderService->buy($listing->fresh(), (string) $seller->id, 1);
    }

    /**
     * Prueba de concurrencia determinística: el primer buy() ya commiteó
     * (RESERVED), así que el segundo debe fallar exactamente como fallaría
     * un segundo comprador real llegando una fracción de segundo tarde —
     * el UPDATE ... WHERE status='ACTIVE' afecta 0 filas. La prueba con dos
     * procesos PHP concurrentes reales (1 succeeds, 1 fails bajo carga
     * simultánea genuina) se validó manualmente contra MySQL — ver
     * MARKETPLACE_ARCHITECTURE_AUDIT.md sección 8; este test cubre la
     * regresión del mecanismo de guardia en CI de forma determinística.
     */
    public function test_second_buy_attempt_on_already_reserved_listing_fails(): void
    {
        $definition = $this->tradableDefinition();
        $seller = User::factory()->create();
        $buyerA = User::factory()->create();
        $buyerB = User::factory()->create();
        $asset = $this->inventory->grant($definition, (string) $seller->id, 1, 'ADMIN_GRANT');
        $listing = $this->listingService->create($asset, (string) $seller->id, 1, 100.0, $this->currency->id);

        $this->orderService->buy($listing->fresh(), (string) $buyerA->id, 1);

        $this->expectException(ListingNoLongerAvailableException::class);
        $this->orderService->buy($listing->fresh(), (string) $buyerB->id, 1);
    }

    public function test_cancel_order_reverts_listing_to_active(): void
    {
        $definition = $this->tradableDefinition();
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $asset = $this->inventory->grant($definition, (string) $seller->id, 1, 'ADMIN_GRANT');
        $listing = $this->listingService->create($asset, (string) $seller->id, 1, 100.0, $this->currency->id);
        $order = $this->orderService->buy($listing->fresh(), (string) $buyer->id, 1);

        $this->orderService->cancel($order);

        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
        $this->assertSame(Listing::STATUS_ACTIVE, $listing->fresh()->status);
    }
}
