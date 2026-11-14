<?php

namespace Tests\Feature\Payments;

use App\Domains\Payments\Contracts\BlockchainProviderInterface;
use App\Domains\Payments\Models\UsdtDepositOrder;
use App\Domains\Payments\Models\UsdtInboundTransfer;
use App\Domains\Payments\Services\UsdtDepositWatcher;
use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletLimit;
use App\Domains\Wallet\Models\WalletMovement;
use App\Http\Middleware\RequireTosAcceptance;
use App\Models\Currency;
use App\Models\MovementType;
use App\Models\User;
use Database\Seeders\MovementTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Depósito USDT-TRC20 "una dirección + monto único" de punta a punta, con
 * TronGridProvider real y la API de TronGrid simulada (Http::fake).
 */
class UsdtDepositFlowTest extends TestCase
{
    use RefreshDatabase;

    private const ADDRESS = 'TPlatformDepositAddress0000000000x';

    private const CONTRACT = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t';

    /** @var array<int, array<string, mixed>> transferencias que "devuelve" TronGrid */
    private array $chainTransfers = [];

    private int $currentBlock = 1000;

    /** @var array<string, int> bloque de cada tx */
    private array $txBlocks = [];

    private Currency $usdt;

    protected function setUp() : void
    {
        parent::setUp();
        (new MovementTypeSeeder)->run();
        MovementType::forgetCatalogCache();
        Cache::flush();
        foreach (['wallet.view', 'wallet.deposit', 'wallet.movements.manage'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        $this->withoutMiddleware(RequireTosAcceptance::class);

        config([
            'payments.usdt_deposits.enabled' => true,
            'payments.usdt_deposits.receiving_address' => self::ADDRESS,
            'payments.tron.provider' => 'tron_grid',
            'payments.tron.api_url' => 'https://tron.test',
            'payments.tron.usdt_contract' => self::CONTRACT,
            'payments.tron.confirmations_required' => 19,
        ]);
        $this->app->forgetInstance(BlockchainProviderInterface::class);

        $this->usdt = Currency::firstOrCreate(['iso_code' => 'USDT'], ['name' => 'Tether', 'type' => 'CRYPTO', 'decimal_places' => 2]);
        WalletLimit::create([
            'currency_id' => $this->usdt->id, 'kyc_level' => 0,
            'min_deposit' => 1, 'max_deposit' => 500, 'min_per_transaction' => 1, 'max_per_transaction' => 500,
            'max_daily' => 1000, 'max_weekly' => 3000, 'max_monthly' => 10000, 'is_active' => true,
        ]);
        WalletLimit::forgetCatalogCache();

        Http::fake([
            'tron.test/v1/accounts/*' => fn () => Http::response(['data' => $this->chainTransfers, 'success' => true]),
            'tron.test/wallet/gettransactioninfobyid' => fn ($request) => Http::response(
                isset($this->txBlocks[$request['value']]) ? ['blockNumber' => $this->txBlocks[$request['value']]] : [],
            ),
            'tron.test/wallet/getnowblock' => fn () => Http::response(['block_header' => ['raw_data' => ['number' => $this->currentBlock]]]),
        ]);
    }

    public function test_concurrent_orders_get_different_amounts_and_each_payment_finds_its_owner() : void
    {
        $alice = $this->player();
        $bob = $this->player();
        $a = $this->createOrder($alice, 25);
        $b = $this->createOrder($bob, 25);
        $this->assertNotSame($a['pay_amount'], $b['pay_amount']);

        $this->pay($b['pay_amount'], block: 900);
        $this->currentBlock = 1000;
        app(UsdtDepositWatcher::class)->run();

        $this->assertSame(25.0, (float) $this->wallet($bob)->balance);
        $this->assertSame(UsdtDepositOrder::STATUS_PENDING, UsdtDepositOrder::find($a['id'])->status);
    }

    public function test_exact_payment_is_detected_confirmed_and_credited_once() : void
    {
        $player = $this->player();
        $order = $this->createOrder($player, 10);

        $this->assertSame(UsdtDepositOrder::STATUS_PENDING, $order['status']);
        $this->assertSame(self::ADDRESS, $order['deposit_address']);
        $this->assertGreaterThan(10.0, (float) $order['pay_amount']);
        $this->assertLessThan(10.01, (float) $order['pay_amount']);

        // Pago con pocas confirmaciones: detectado, todavía sin acreditar.
        $tx = $this->pay($order['pay_amount'], block: 995);
        app(UsdtDepositWatcher::class)->run();
        $this->assertSame(UsdtDepositOrder::STATUS_CONFIRMING, UsdtDepositOrder::find($order['id'])->status);
        $this->assertSame(0.0, (float) $this->wallet($player)->balance);

        // Alcanza las 19 confirmaciones: se acredita el monto pedido.
        $this->currentBlock = 1020;
        app(UsdtDepositWatcher::class)->run();
        $credited = UsdtDepositOrder::find($order['id']);
        $this->assertSame(UsdtDepositOrder::STATUS_CREDITED, $credited->status);
        $this->assertSame($tx, $credited->tx_hash);
        $this->assertSame(10.0, (float) $this->wallet($player)->balance);

        // Correr de nuevo no acredita dos veces.
        app(UsdtDepositWatcher::class)->run();
        $this->assertSame(10.0, (float) $this->wallet($player)->balance);
        $this->assertSame(1, WalletMovement::where('type', WalletMovement::TYPE_CRYPTO_DEPOSIT)->count());

        $this->actingAs($player)->getJson(route('wallet.usdt-deposits.show', $order['id']))
            ->assertOk()->assertJsonPath('data.status', UsdtDepositOrder::STATUS_CREDITED);
    }

    public function test_late_payment_goes_to_review_and_treasury_credits_it() : void
    {
        $player = $this->player();
        $order = $this->createOrder($player, 40);

        $this->travel(31)->minutes();
        $this->pay($order['pay_amount'], block: 900);
        app(UsdtDepositWatcher::class)->run();

        $late = UsdtDepositOrder::find($order['id']);
        $this->assertSame(UsdtDepositOrder::STATUS_MANUAL_REVIEW, $late->status);
        $this->assertSame(UsdtInboundTransfer::STATUS_LATE, UsdtInboundTransfer::sole()->status);
        $this->assertSame(0.0, (float) $this->wallet($player)->balance);

        // El jugador no puede revisarse a sí mismo; tesorería sí.
        $player->givePermissionTo('wallet.movements.manage');
        $this->actingAs($player)->postJson(route('usdt-deposits.approve', $order['id']))->assertForbidden();

        $this->actingAs($this->treasurer())->postJson(route('usdt-deposits.approve', $order['id']))
            ->assertOk()->assertJsonPath('data.status', UsdtDepositOrder::STATUS_CREDITED);
        $this->assertSame(40.0, (float) $this->wallet($player)->balance);
    }

    public function test_limits_disabled_feature_and_invalid_amounts_are_rejected() : void
    {
        $player = $this->player();

        $this->actingAs($player)->postJson(route('wallet.usdt-deposits.store'), ['amount' => 600])
            ->assertUnprocessable(); // max_deposit 500
        $this->actingAs($player)->postJson(route('wallet.usdt-deposits.store'), ['amount' => 10.555])
            ->assertUnprocessable(); // más de 2 decimales

        config(['payments.usdt_deposits.enabled' => false]);
        $this->actingAs($player)->postJson(route('wallet.usdt-deposits.store'), ['amount' => 10])->assertNotFound();
        $this->assertSame(['expired' => 0, 'seen' => 0, 'matched' => 0, 'credited' => 0], app(UsdtDepositWatcher::class)->run());
    }

    public function test_orders_are_private_to_their_owner() : void
    {
        $order = $this->createOrder($this->player(), 10);

        $this->actingAs($this->player())->getJson(route('wallet.usdt-deposits.show', $order['id']))->assertNotFound();
        $this->actingAs($this->player())->get(route('wallet.usdt-deposits.qr', $order['id']))->assertNotFound();
    }

    public function test_player_and_treasury_pages_render() : void
    {
        $player = $this->player();
        $order = $this->createOrder($player, 10);

        $this->actingAs($player)->get(route('wallet.usdt-deposit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Wallet/UsdtDeposit')
                ->where('enabled', true)
                ->where('order.id', $order['id']));

        $this->pay('3.000000', block: 900);
        app(UsdtDepositWatcher::class)->run();

        $this->actingAs($this->treasurer())->get(route('usdt-deposits.review'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Wallet/UsdtDepositReview/Index')
                ->has('orders', 0)
                ->has('unmatched', 1)
                ->where('receivingAddress', self::ADDRESS));

        // Sin permiso de tesorería: el middleware lo saca de la página.
        $this->actingAs($player)->get(route('usdt-deposits.review'))->assertRedirect();
    }

    public function test_txid_reported_before_the_watcher_sees_it_is_linked_later() : void
    {
        $player = $this->player();
        $order = $this->createOrder($player, 10);
        $tx = str_repeat('ab', 32);

        $this->actingAs($player)->postJson(route('wallet.usdt-deposits.claim', $order['id']), ['tx_hash' => $tx])->assertOk();

        $this->pay('9.500000', block: 900, txHash: $tx);
        app(UsdtDepositWatcher::class)->run();

        $this->assertSame(UsdtInboundTransfer::STATUS_MATCHED, UsdtInboundTransfer::sole()->status);
        $this->assertSame(9.5, UsdtDepositOrder::find($order['id'])->received_amount);

        // Tesorería acredita lo que realmente llegó.
        $this->actingAs($this->treasurer())->postJson(route('usdt-deposits.approve', $order['id']), ['amount' => 9.5])->assertOk();
        $this->assertSame(9.5, (float) $this->wallet($player)->balance);
    }

    public function test_wrong_amount_is_unmatched_until_the_player_reports_the_txid() : void
    {
        $player = $this->player();
        $order = $this->createOrder($player, 10);

        // Pagó 10 "redondo" (sin el sufijo único): no se puede saber de quién es.
        $tx = $this->pay('10.000000', block: 900);
        app(UsdtDepositWatcher::class)->run();
        $this->assertSame(UsdtInboundTransfer::STATUS_UNMATCHED, UsdtInboundTransfer::sole()->status);
        $this->assertSame(UsdtDepositOrder::STATUS_PENDING, UsdtDepositOrder::find($order['id'])->status);

        $this->actingAs($player)->postJson(route('wallet.usdt-deposits.claim', $order['id']), ['tx_hash' => 'no-es-un-hash'])
            ->assertUnprocessable();
        $this->actingAs($player)->postJson(route('wallet.usdt-deposits.claim', $order['id']), ['tx_hash' => strtoupper($tx)])
            ->assertOk()->assertJsonPath('data.status', UsdtDepositOrder::STATUS_MANUAL_REVIEW);

        $claimed = UsdtDepositOrder::find($order['id']);
        $this->assertSame(10.0, $claimed->received_amount);
        $this->assertSame(UsdtInboundTransfer::STATUS_MATCHED, UsdtInboundTransfer::sole()->status);

        // Otro jugador no puede reclamar el mismo txid.
        $other = $this->player();
        $otherOrder = $this->createOrder($other, 10);
        $this->actingAs($other)->postJson(route('wallet.usdt-deposits.claim', $otherOrder['id']), ['tx_hash' => $tx])
            ->assertStatus(409);

        // Tesorería lo rechaza: la transferencia vuelve a quedar sin dueño.
        $this->actingAs($this->treasurer())->postJson(route('usdt-deposits.reject', $order['id']), ['reason' => 'El txid no corresponde a tu cuenta'])
            ->assertOk();
        $this->assertSame(UsdtDepositOrder::STATUS_REJECTED, UsdtDepositOrder::find($order['id'])->status);
        $this->assertSame(UsdtInboundTransfer::STATUS_UNMATCHED, UsdtInboundTransfer::sole()->status);
        $this->assertSame(0.0, (float) $this->wallet($player)->balance);
    }

    /** @return array<string, mixed> */
    private function createOrder(User $player, float $amount) : array
    {
        return $this->actingAs($player)
            ->postJson(route('wallet.usdt-deposits.store'), ['amount' => $amount])
            ->assertCreated()
            ->json('data');
    }

    private function pay(string $amount, int $block, ?string $txHash = null) : string
    {
        $txHash ??= bin2hex(random_bytes(32));
        $this->chainTransfers[] = [
            'transaction_id' => $txHash,
            'from' => 'TPayerAddress000000000000000000001',
            'to' => self::ADDRESS,
            'value' => (string) (int) round(((float) $amount) * 1_000_000),
            'block_timestamp' => now()->getTimestampMs(),
            'token_info' => ['address' => self::CONTRACT, 'decimals' => 6],
        ];
        $this->txBlocks[$txHash] = $block;

        return $txHash;
    }

    private function player() : User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->givePermissionTo(['wallet.view', 'wallet.deposit']);

        return $user;
    }

    private function treasurer() : User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->givePermissionTo('wallet.movements.manage');

        return $user;
    }

    private function wallet(User $user) : Wallet
    {
        return Wallet::where('user_id', $user->id)->where('currency_id', $this->usdt->id)->firstOrFail();
    }
}
