<?php

namespace Tests\Unit\Domains\Marketplace;

use App\Domains\Marketplace\Inventory\Exceptions\AssetNotAvailableException;
use App\Domains\Marketplace\Inventory\Models\AssetDefinition;
use App\Domains\Marketplace\Inventory\Models\PlayerAsset;
use App\Domains\Marketplace\Inventory\Services\AssetConsumptionService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetConsumptionServiceTest extends TestCase
{
    use RefreshDatabase;

    private AssetConsumptionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AssetConsumptionService::class);
    }

    private function makeConsumableDefinition(string $gameGroup = 'TRIVIA'): AssetDefinition
    {
        $definition = AssetDefinition::create([
            'code' => 'TEST_HINT_' . uniqid(), 'name' => 'Pista de prueba',
            'category' => AssetDefinition::CATEGORY_TICKET, 'stackable' => true,
            'tradable' => false, 'transferable' => false, 'consumable' => true,
            'marketplace_enabled' => false, 'game_group' => $gameGroup,
            'status' => AssetDefinition::STATUS_ACTIVE,
        ]);
        AssetDefinition::forgetCatalogCache();

        return $definition;
    }

    public function test_consume_by_code_decrements_available_quantity(): void
    {
        $definition = $this->makeConsumableDefinition();
        $user = User::factory()->create();
        PlayerAsset::create([
            'asset_definition_id' => $definition->id, 'owner_id' => $user->id,
            'quantity' => 3, 'status' => PlayerAsset::STATUS_AVAILABLE, 'acquired_via' => 'ADMIN_GRANT',
        ]);

        $this->service->consumeByCode((string) $user->id, $definition->code);

        $this->assertSame(2, $this->service->availableQuantity((string) $user->id, $definition->code));
    }

    public function test_consume_by_code_throws_without_enough_quantity(): void
    {
        $definition = $this->makeConsumableDefinition();
        $user = User::factory()->create();

        $this->expectException(AssetNotAvailableException::class);
        $this->service->consumeByCode((string) $user->id, $definition->code);
    }

    public function test_consume_by_code_throws_on_wrong_game_group(): void
    {
        $definition = $this->makeConsumableDefinition('TRIVIA');
        $user = User::factory()->create();
        PlayerAsset::create([
            'asset_definition_id' => $definition->id, 'owner_id' => $user->id,
            'quantity' => 1, 'status' => PlayerAsset::STATUS_AVAILABLE, 'acquired_via' => 'ADMIN_GRANT',
        ]);

        $this->expectException(AssetNotAvailableException::class);
        $this->service->consumeByCode((string) $user->id, $definition->code, 1, 'BINGO');
    }

    public function test_consume_by_code_throws_for_non_consumable_definition(): void
    {
        $definition = AssetDefinition::create([
            'code' => 'TEST_NOT_CONSUMABLE_' . uniqid(), 'name' => 'x',
            'category' => AssetDefinition::CATEGORY_COSMETIC, 'stackable' => true,
            'tradable' => true, 'transferable' => true, 'consumable' => false,
            'marketplace_enabled' => true, 'status' => AssetDefinition::STATUS_ACTIVE,
        ]);
        AssetDefinition::forgetCatalogCache();
        $user = User::factory()->create();

        $this->expectException(AssetNotAvailableException::class);
        $this->service->consumeByCode((string) $user->id, $definition->code);
    }

    public function test_available_quantity_returns_zero_for_unknown_code(): void
    {
        $user = User::factory()->create();

        $this->assertSame(0, $this->service->availableQuantity((string) $user->id, 'DOES_NOT_EXIST'));
    }

    public function test_grant_by_code_creates_player_asset(): void
    {
        $definition = $this->makeConsumableDefinition();
        $user = User::factory()->create();

        $asset = $this->service->grantByCode((string) $user->id, $definition->code, 2, 'event:test-123');

        $this->assertSame(2, $asset->quantity);
        $this->assertSame('GAME_PRIZE', $asset->acquired_via);
        $this->assertSame('event:test-123', $asset->acquired_reference);
    }
}
