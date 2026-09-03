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
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EscrowAdminControllerTest extends TestCase
{
    use RefreshDatabase;

    private Currency $currency;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->currency = Currency::firstOrCreate(['iso_code' => 'USD'], ['name' => 'Dollar', 'type' => 'FIAT']);

        Permission::firstOrCreate(['name' => 'marketplace.manage', 'guard_name' => 'web']);
        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo('marketplace.manage');
        $this->admin->assignRole('admin');
    }

    private function makeDisputedEscrow(): Escrow
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();

        $definition = AssetDefinition::create([
            'code' => 'TEST_ADMIN_' . uniqid(), 'name' => 'x',
            'category' => AssetDefinition::CATEGORY_EQUIPMENT, 'stackable' => false,
            'tradable' => true, 'transferable' => true, 'consumable' => false,
            'marketplace_enabled' => true, 'status' => AssetDefinition::STATUS_ACTIVE,
        ]);
        $asset = PlayerAsset::create([
            'asset_definition_id' => $definition->id, 'owner_id' => $buyer->id,
            'quantity' => 1, 'status' => PlayerAsset::STATUS_LOCKED, 'acquired_via' => 'ADMIN_GRANT',
        ]);
        $listing = Listing::create([
            'seller_id' => $seller->id, 'player_asset_id' => $asset->id,
            'quantity' => 1, 'price' => 50, 'currency_id' => $this->currency->id, 'status' => Listing::STATUS_RESERVED,
        ]);
        $order = Order::create([
            'listing_id' => $listing->id, 'buyer_id' => $buyer->id,
            'quantity' => 1, 'price' => 50, 'fee_amount' => 2.5, 'status' => Order::STATUS_ESCROW,
        ]);

        $escrow = Escrow::create([
            'order_id' => $order->id, 'buyer_id' => $buyer->id, 'seller_id' => $seller->id,
            'player_asset_id' => $asset->id, 'quantity' => 1, 'amount' => 50, 'fee_amount' => 2.5,
            'seller_amount' => 47.5, 'currency_id' => $this->currency->id,
            'status' => Escrow::STATUS_DISPUTED, 'dispute_reason' => 'test', 'expires_at' => now()->addMinutes(30),
        ]);

        $asset->update(['locked_by_escrow_id' => $escrow->id]);

        return $escrow;
    }

    public function test_admin_can_list_escrows(): void
    {
        $this->makeDisputedEscrow();

        $response = $this->actingAs($this->admin)->getJson('/marketplace-escrows/fetch-list');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_filters_by_status(): void
    {
        $this->makeDisputedEscrow();

        $response = $this->actingAs($this->admin)->getJson('/marketplace-escrows/fetch-list?status=RELEASED');

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }

    public function test_non_admin_cannot_access(): void
    {
        $player = User::factory()->create();
        $player->assignRole('player');

        $response = $this->actingAs($player)->getJson('/marketplace-escrows/fetch-list');

        $response->assertForbidden();
    }

    public function test_admin_can_resolve_dispute_buyer_wins(): void
    {
        $escrow = $this->makeDisputedEscrow();

        $response = $this->actingAs($this->admin)->postJson("/marketplace-escrows/{$escrow->id}/resolve", [
            'resolution' => 'BUYER_WINS',
        ]);

        $response->assertOk();
        $this->assertSame(Escrow::STATUS_REFUNDED, $escrow->fresh()->status);
        $this->assertSame(Escrow::RESOLUTION_BUYER_WINS, $escrow->fresh()->resolution);
    }

    public function test_cannot_resolve_non_disputed_escrow(): void
    {
        $escrow = $this->makeDisputedEscrow();
        Escrow::where('id', $escrow->id)->update(['status' => Escrow::STATUS_RELEASED]);

        $response = $this->actingAs($this->admin)->postJson("/marketplace-escrows/{$escrow->id}/resolve", [
            'resolution' => 'BUYER_WINS',
        ]);

        $response->assertForbidden();
    }
}
