<?php

namespace Tests\Unit\Domains\Marketplace;

use App\Domains\Marketplace\Inventory\Exceptions\AssetNotAvailableException;
use App\Domains\Marketplace\Inventory\Exceptions\InsufficientAssetQuantityException;
use App\Domains\Marketplace\Inventory\Models\AssetDefinition;
use App\Domains\Marketplace\Inventory\Models\PlayerAsset;
use App\Domains\Marketplace\Inventory\Services\InventoryService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InventoryService::class);
    }

    private function makeStackableDefinition(): AssetDefinition
    {
        return AssetDefinition::create([
            'code' => 'TEST_STACKABLE', 'name' => 'Test Stackable',
            'category' => AssetDefinition::CATEGORY_BOOSTER, 'stackable' => true,
            'tradable' => true, 'transferable' => true, 'consumable' => true,
            'marketplace_enabled' => true, 'status' => AssetDefinition::STATUS_ACTIVE,
        ]);
    }

    private function makeNonStackableDefinition(): AssetDefinition
    {
        return AssetDefinition::create([
            'code' => 'TEST_UNIQUE', 'name' => 'Test Unique',
            'category' => AssetDefinition::CATEGORY_EQUIPMENT, 'stackable' => false,
            'tradable' => true, 'transferable' => true, 'consumable' => false,
            'marketplace_enabled' => true, 'status' => AssetDefinition::STATUS_ACTIVE,
        ]);
    }

    public function test_grant_creates_new_row_then_merges_subsequent_grants_for_stackable(): void
    {
        $definition = $this->makeStackableDefinition();
        $user = User::factory()->create();

        $first = $this->service->grant($definition, (string) $user->id, 3, 'ADMIN_GRANT');
        $second = $this->service->grant($definition, (string) $user->id, 2, 'ADMIN_GRANT');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(5, $second->fresh()->quantity);
        $this->assertSame(1, PlayerAsset::where('owner_id', $user->id)->count());
    }

    public function test_grant_non_stackable_creates_separate_rows(): void
    {
        $definition = $this->makeNonStackableDefinition();
        $user = User::factory()->create();

        $this->service->grant($definition, (string) $user->id, 1, 'ADMIN_GRANT');
        $this->service->grant($definition, (string) $user->id, 1, 'ADMIN_GRANT');

        $this->assertSame(2, PlayerAsset::where('owner_id', $user->id)->count());
    }

    public function test_lock_partial_quantity_splits_into_two_rows(): void
    {
        $definition = $this->makeStackableDefinition();
        $user = User::factory()->create();
        $asset = $this->service->grant($definition, (string) $user->id, 5, 'ADMIN_GRANT');

        $locked = $this->service->lock($asset, '1000000000000000001', 2);

        $this->assertSame(PlayerAsset::STATUS_LOCKED, $locked->status);
        $this->assertSame(2, $locked->quantity);
        $this->assertSame(3, $asset->fresh()->quantity);
        $this->assertSame(PlayerAsset::STATUS_AVAILABLE, $asset->fresh()->status);
    }

    public function test_lock_full_quantity_transitions_same_row(): void
    {
        $definition = $this->makeNonStackableDefinition();
        $user = User::factory()->create();
        $asset = $this->service->grant($definition, (string) $user->id, 1, 'ADMIN_GRANT');

        $locked = $this->service->lock($asset, '1000000000000000001', 1);

        $this->assertSame($asset->id, $locked->id);
        $this->assertSame(PlayerAsset::STATUS_LOCKED, $locked->fresh()->status);
    }

    public function test_lock_more_than_available_throws(): void
    {
        $definition = $this->makeStackableDefinition();
        $user = User::factory()->create();
        $asset = $this->service->grant($definition, (string) $user->id, 2, 'ADMIN_GRANT');

        $this->expectException(InsufficientAssetQuantityException::class);
        $this->service->lock($asset, '1000000000000000001', 5);
    }

    public function test_lock_already_locked_asset_throws(): void
    {
        $definition = $this->makeNonStackableDefinition();
        $user = User::factory()->create();
        $asset = $this->service->grant($definition, (string) $user->id, 1, 'ADMIN_GRANT');
        $this->service->lock($asset, '1000000000000000001', 1);

        $this->expectException(AssetNotAvailableException::class);
        $this->service->lock($asset->fresh(), '1000000000000000002', 1);
    }

    public function test_unlock_reverts_to_available_and_merges_with_sibling(): void
    {
        $definition = $this->makeStackableDefinition();
        $user = User::factory()->create();
        $asset = $this->service->grant($definition, (string) $user->id, 5, 'ADMIN_GRANT');
        $locked = $this->service->lock($asset, '1000000000000000001', 2);

        $this->service->unlock($locked);

        $this->assertSame(1, PlayerAsset::where('owner_id', $user->id)->count());
        $this->assertSame(5, PlayerAsset::where('owner_id', $user->id)->first()->quantity);
    }

    public function test_transfer_changes_owner_and_merges_into_buyer_existing_stack(): void
    {
        $definition = $this->makeStackableDefinition();
        $seller = User::factory()->create();
        $buyer = User::factory()->create();

        $sellerAsset = $this->service->grant($definition, (string) $seller->id, 5, 'ADMIN_GRANT');
        $buyerAsset = $this->service->grant($definition, (string) $buyer->id, 3, 'ADMIN_GRANT');

        $locked = $this->service->lock($sellerAsset->fresh(), '1000000000000000001', 2);
        $transferred = $this->service->transfer($locked, (string) $buyer->id, '1000000000000000001');

        $this->assertSame($buyerAsset->id, $transferred->id);
        $this->assertSame(5, $transferred->fresh()->quantity);
        $this->assertSame(3, $sellerAsset->fresh()->quantity);
    }

    public function test_transfer_without_existing_buyer_stack_moves_ownership(): void
    {
        $definition = $this->makeNonStackableDefinition();
        $seller = User::factory()->create();
        $buyer = User::factory()->create();

        $asset = $this->service->grant($definition, (string) $seller->id, 1, 'ADMIN_GRANT');
        $locked = $this->service->lock($asset, '1000000000000000001', 1);
        $transferred = $this->service->transfer($locked, (string) $buyer->id, '1000000000000000001');

        $this->assertSame((string) $buyer->id, $transferred->owner_id);
        $this->assertSame(PlayerAsset::STATUS_AVAILABLE, $transferred->status);
    }

    public function test_consume_full_quantity_marks_consumed(): void
    {
        $definition = $this->makeStackableDefinition();
        $user = User::factory()->create();
        $asset = $this->service->grant($definition, (string) $user->id, 1, 'ADMIN_GRANT');

        $this->service->consume($asset, 1);

        $this->assertSame(PlayerAsset::STATUS_CONSUMED, $asset->fresh()->status);
    }

    public function test_consume_partial_quantity_decrements_and_stays_available(): void
    {
        $definition = $this->makeStackableDefinition();
        $user = User::factory()->create();
        $asset = $this->service->grant($definition, (string) $user->id, 3, 'ADMIN_GRANT');

        $this->service->consume($asset, 1);

        $fresh = $asset->fresh();
        $this->assertSame(2, $fresh->quantity);
        $this->assertSame(PlayerAsset::STATUS_AVAILABLE, $fresh->status);
    }

    public function test_consume_non_consumable_definition_throws(): void
    {
        $definition = AssetDefinition::create([
            'code' => 'TEST_NOT_CONSUMABLE', 'name' => 'x',
            'category' => AssetDefinition::CATEGORY_COSMETIC, 'stackable' => true,
            'tradable' => true, 'transferable' => true, 'consumable' => false,
            'marketplace_enabled' => true, 'status' => AssetDefinition::STATUS_ACTIVE,
        ]);
        $user = User::factory()->create();
        $asset = $this->service->grant($definition, (string) $user->id, 1, 'ADMIN_GRANT');

        $this->expectException(\DomainException::class);
        $this->service->consume($asset, 1);
    }
}
