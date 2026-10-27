<?php

namespace Tests\Feature\Marketplace;

use App\Domains\Marketplace\Escrow\Exceptions\InvalidEscrowStateException;
use App\Domains\Marketplace\Escrow\Models\Escrow;
use App\Domains\Marketplace\Escrow\Services\EscrowService;
use App\Domains\Marketplace\Inventory\Models\AssetDefinition;
use App\Domains\Marketplace\Inventory\Models\PlayerAsset;
use App\Domains\Marketplace\Inventory\Services\InventoryService;
use App\Domains\Marketplace\Models\Listing;
use App\Domains\Marketplace\Models\Order;
use App\Domains\Marketplace\Services\ListingService;
use App\Domains\Marketplace\Services\OrderService;
use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletMovement;
use App\Models\Country;
use App\Models\Currency;
use App\Models\MovementType;
use App\Models\User;
use App\Services\Platform\PlatformWalletService;
use Database\Seeders\MovementTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EscrowServiceTest extends TestCase
{
    use RefreshDatabase;
    private Currency $currency;
    private EscrowService $escrowService;

    private InventoryService $inventory;
    private ListingService $listingService;
    private OrderService $orderService;
    private PlatformWalletService $platformWallet;

    protected function setUp() : void
    {
        parent::setUp();

        // RefreshDatabase no corre seeders adicionales al $seeder de TestCase
        // (PermissionSeeder) — movement_types es requerido por la FK en
        // wallet_movements.type para los tipos MARKETPLACE_* nuevos.
        (new MovementTypeSeeder)->run();
        MovementType::forgetCatalogCache();

        // PlatformWalletService::resolvePlatformUser() (código ya existente,
        // no de esta fase) crea el usuario sistema playrke_platform con
        // country_id resuelto por iso_code_2='VE' — sin este país, el
        // primer creditMarketplaceFee() del test falla con country_id NULL.
        // Mismo patrón que AuthTest/RegistrationTest.
        Country::factory()->create(['iso_code_2' => 'VE']);

        $this->inventory = app(InventoryService::class);
        $this->listingService = app(ListingService::class);
        $this->orderService = app(OrderService::class);
        $this->escrowService = app(EscrowService::class);
        $this->platformWallet = app(PlatformWalletService::class);
        $this->currency = Currency::firstOrCreate(['iso_code' => 'USD'], ['name' => 'Dollar', 'type' => 'FIAT']);

        config(['marketplace.default_fee_percent' => 5.0]);
    }

    public function test_cancel_after_funding_throws() : void
    {
        ['escrow' => $escrow] = $this->readyToSettleEscrow(100.0);

        $this->expectException(InvalidEscrowStateException::class);
        $this->escrowService->cancel($escrow);
    }

    public function test_cancel_before_funding_does_not_touch_wallet() : void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        Wallet::create(['user_id' => $buyer->id, 'currency_id' => $this->currency->id, 'balance' => 500, 'status' => 'ACTIVE']);

        $definition = $this->tradableDefinition();
        $asset = $this->inventory->grant($definition, (string) $seller->id, 1, 'ADMIN_GRANT');
        $listing = $this->listingService->create($asset, (string) $seller->id, 1, 100.0, $this->currency->id);
        $order = $this->orderService->buy($listing->fresh(), (string) $buyer->id, 1);
        $escrow = $this->escrowService->create($order->fresh());

        $cancelled = $this->escrowService->cancel($escrow);

        $this->assertSame(Escrow::STATUS_CANCELLED, $cancelled->status);
        $this->assertSame(500.0, (float) Wallet::where('user_id', $buyer->id)->first()->balance);
        $this->assertSame(Listing::STATUS_ACTIVE, $listing->fresh()->status);
    }

    public function test_dispute_then_resolve_buyer_wins_refunds() : void
    {
        ['escrow' => $escrow, 'buyer' => $buyer] = $this->readyToSettleEscrow(100.0);
        $admin = User::factory()->create();

        $disputed = $this->escrowService->dispute($escrow, 'Item no coincide');
        $this->assertSame(Escrow::STATUS_DISPUTED, $disputed->status);

        $resolved = $this->escrowService->resolve($disputed, Escrow::RESOLUTION_BUYER_WINS, $admin);

        $this->assertSame(Escrow::STATUS_REFUNDED, $resolved->status);
        $this->assertSame(Escrow::RESOLUTION_BUYER_WINS, $resolved->resolution);
        $this->assertSame((string) $admin->id, $resolved->resolved_by);
        $this->assertSame(500.0, (float) Wallet::where('user_id', $buyer->id)->first()->balance);
    }

    public function test_dispute_then_resolve_seller_wins_releases() : void
    {
        ['escrow' => $escrow, 'seller' => $seller] = $this->readyToSettleEscrow(100.0);
        $admin = User::factory()->create();

        $disputed = $this->escrowService->dispute($escrow, 'Comprador insiste sin motivo válido');
        $resolved = $this->escrowService->resolve($disputed, Escrow::RESOLUTION_SELLER_WINS, $admin);

        $this->assertSame(Escrow::STATUS_RELEASED, $resolved->status);
        $this->assertSame(95.0, (float) Wallet::where('user_id', $seller->id)->first()->balance);
    }

    public function test_double_release_is_idempotent_seller_credited_once() : void
    {
        ['escrow' => $escrow, 'seller' => $seller] = $this->readyToSettleEscrow(100.0);

        $first = $this->escrowService->release($escrow);
        $second = $this->escrowService->release($first->fresh());

        $this->assertSame(Escrow::STATUS_RELEASED, $second->status);
        $this->assertSame(95.0, (float) Wallet::where('user_id', $seller->id)->first()->balance);

        $movementCount = WalletMovement::where('idempotency_key', "marketplace_escrow:{$escrow->id}:seller_credit")->count();
        $this->assertSame(1, $movementCount);
    }

    public function test_full_happy_path_moves_funds_and_asset_correctly() : void
    {
        ['escrow' => $escrow, 'seller' => $seller, 'buyer' => $buyer, 'asset' => $asset, 'listing' => $listing] = $this->readyToSettleEscrow(100.0);

        $released = $this->escrowService->release($escrow);

        $this->assertSame(Escrow::STATUS_RELEASED, $released->status);
        $this->assertSame(400.0, (float) Wallet::where('user_id', $buyer->id)->first()->balance);
        $this->assertSame(95.0, (float) Wallet::where('user_id', $seller->id)->first()->balance);

        $platformWallet = $this->platformWallet->resolveWallet($this->currency->id);
        $this->assertSame(5.0, (float) $platformWallet->balance);

        $freshAsset = PlayerAsset::find($asset->id);
        $this->assertSame((string) $buyer->id, $freshAsset->owner_id);
        $this->assertSame(PlayerAsset::STATUS_AVAILABLE, $freshAsset->status);

        $this->assertSame(Order::STATUS_COMPLETED, $listing->orders()->first()->fresh()->status);
        $this->assertSame(Listing::STATUS_SOLD, $listing->fresh()->status);
    }

    public function test_refund_after_lock_restores_balance_and_unlocks_asset() : void
    {
        ['escrow' => $escrow, 'buyer' => $buyer, 'asset' => $asset, 'listing' => $listing] = $this->readyToSettleEscrow(100.0);

        $refunded = $this->escrowService->refund($escrow);

        $this->assertSame(Escrow::STATUS_REFUNDED, $refunded->status);
        $this->assertSame(500.0, (float) Wallet::where('user_id', $buyer->id)->first()->balance);
        $this->assertSame(PlayerAsset::STATUS_AVAILABLE, PlayerAsset::find($asset->id)->status);
        $this->assertSame(Listing::STATUS_ACTIVE, $listing->fresh()->status);
    }

    public function test_release_on_non_ready_escrow_is_noop() : void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        Wallet::create(['user_id' => $buyer->id, 'currency_id' => $this->currency->id, 'balance' => 500, 'status' => 'ACTIVE']);

        $definition = $this->tradableDefinition();
        $asset = $this->inventory->grant($definition, (string) $seller->id, 1, 'ADMIN_GRANT');
        $listing = $this->listingService->create($asset, (string) $seller->id, 1, 100.0, $this->currency->id);
        $order = $this->orderService->buy($listing->fresh(), (string) $buyer->id, 1);
        $escrow = $this->escrowService->create($order->fresh());

        // Todavía en CREATED, no READY_TO_SETTLE — release() debe ignorar sin lanzar.
        $result = $this->escrowService->release($escrow);

        $this->assertSame(Escrow::STATUS_CREATED, $result->status);
    }

    /** @return array{escrow: Escrow, seller: User, buyer: User, asset: PlayerAsset, listing: Listing} */
    private function readyToSettleEscrow(float $price = 100.0) : array
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();

        Wallet::create(['user_id' => $seller->id, 'currency_id' => $this->currency->id, 'balance' => 0, 'status' => 'ACTIVE']);
        Wallet::create(['user_id' => $buyer->id, 'currency_id' => $this->currency->id, 'balance' => 500, 'status' => 'ACTIVE']);

        $definition = $this->tradableDefinition();
        $asset = $this->inventory->grant($definition, (string) $seller->id, 1, 'ADMIN_GRANT');
        $listing = $this->listingService->create($asset, (string) $seller->id, 1, $price, $this->currency->id);
        $order = $this->orderService->buy($listing->fresh(), (string) $buyer->id, 1);

        $escrow = $this->escrowService->create($order->fresh());
        $escrow = $this->escrowService->fund($escrow);
        $escrow = $this->escrowService->lockAsset($escrow);

        return compact('escrow', 'seller', 'buyer', 'asset', 'listing');
    }

    private function tradableDefinition() : AssetDefinition
    {
        return AssetDefinition::create([
            'code' => 'TEST_TRADABLE_ESCROW', 'name' => 'Test Tradable',
            'category' => AssetDefinition::CATEGORY_EQUIPMENT, 'stackable' => false,
            'tradable' => true, 'transferable' => true, 'consumable' => false,
            'marketplace_enabled' => true, 'status' => AssetDefinition::STATUS_ACTIVE,
        ]);
    }
}
