<?php

namespace Tests\Unit\Services;

use App\Domains\Wallet\Models\WalletMovement;
use App\Models\Country;
use App\Models\Currency;
use App\Models\MovementType;
use App\Services\Platform\PlatformWalletService;
use Database\Seeders\MovementTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class PlatformWalletServiceTest extends TestCase
{
    use RefreshDatabase;

    private PlatformWalletService $service;

    protected function setUp() : void
    {
        parent::setUp();

        // resolvePlatformUser()/resolveWallet() cachean 'platform_user_id' y
        // 'platform_wallet_{currencyId}' con clave GLOBAL (sin scope de
        // test). RefreshDatabase trunca las tablas entre tests pero no
        // limpia la caché de aplicación — sin este forget(), un test
        // anterior en la misma corrida de PHPUnit deja cacheado el id de un
        // usuario/wallet que ya no existe tras el truncate, y resolveWallet()
        // devuelve null aunque este test cree todo lo necesario.
        Cache::flush();

        $this->service = app(PlatformWalletService::class);

        // wallet_movements.type tiene FK real hacia movement_types.code —
        // sin el catálogo poblado, cualquier record() con TYPE_RKE_COMMISSION/
        // TYPE_DEPOSIT/etc. falla por violación de esa constraint.
        (new MovementTypeSeeder)->run();
        MovementType::forgetCatalogCache();

        // PlatformWalletService::resolvePlatformUser() busca explícitamente
        // iso_code_2='VE' para el usuario sistema playrke_platform — sin
        // este país, country_id llega null y viola la constraint NOT NULL
        // de users.country_id en cualquier método que resuelva esa wallet.
        Country::firstOrCreate(
            ['iso_code_2' => 'VE'],
            ['name' => 'Venezuela', 'iso_code_3' => 'VEN', 'status' => 'ACTIVE'],
        );
    }

    // ── creditMarketplaceFee() — idempotencia ────────────────────────────────

    public function test_credit_marketplace_fee_is_idempotent_by_key() : void
    {
        $currency = $this->usd();
        $key = 'marketplace_escrow:test-escrow-1:fee';

        $first = $this->service->creditMarketplaceFee((string) $currency->id, 25.0, $key);
        $second = $this->service->creditMarketplaceFee((string) $currency->id, 25.0, $key);

        $this->assertSame((string) $first->id, (string) $second->id);

        $wallet = $this->service->resolveWallet((string) $currency->id);
        // Solo se acreditó UNA vez pese a las dos llamadas con la misma key.
        $this->assertSame(25.0, (float) $wallet->balance);
    }

    public function test_credit_marketplace_fee_with_zero_amount_returns_null() : void
    {
        $currency = $this->usd();

        $result = $this->service->creditMarketplaceFee((string) $currency->id, 0.0, 'some-key');

        $this->assertNull($result);
    }

    // ── creditRke() ──────────────────────────────────────────────────────────

    public function test_credit_rke_increments_balance_and_records_movement() : void
    {
        $currency = $this->usd();

        $this->service->creditRke((string) $currency->id, 15.0, 'Comisión RKE — Sala #test');

        $wallet = $this->service->resolveWallet((string) $currency->id);
        $this->assertNotNull($wallet);
        $this->assertSame(15.0, (float) $wallet->balance);

        $movement = WalletMovement::where('wallet_id', $wallet->id)->first();
        $this->assertSame(WalletMovement::TYPE_RKE_COMMISSION, $movement->type);
        $this->assertSame(15.0, (float) $movement->amount);
    }

    public function test_credit_rke_with_negative_amount_throws_and_does_not_credit() : void
    {
        $currency = $this->usd();

        // Un monto negativo es un error de cálculo aguas arriba: falla para que
        // la venta que lo originó se revierta entera, en vez de ignorarse.
        try {
            $this->service->creditRke((string) $currency->id, -10.0);
            $this->fail('Debió rechazar el monto negativo');
        } catch (InvalidArgumentException) {
            // esperado
        }

        $wallet = $this->service->resolveWallet((string) $currency->id);
        $this->assertNull($wallet);
    }

    public function test_credit_rke_with_zero_amount_is_a_silent_noop() : void
    {
        $currency = $this->usd();

        $this->service->creditRke((string) $currency->id, 0.0);

        // No debe ni siquiera crear la wallet de plataforma (comisión 0% es
        // un caso legítimo, no debe generar ruido ni movimiento).
        $wallet = $this->service->resolveWallet((string) $currency->id);
        $this->assertNull($wallet);
    }

    // ── deposit() ────────────────────────────────────────────────────────────

    public function test_deposit_increments_balance_and_logs_audit() : void
    {
        $currency = $this->usd();

        $this->service->deposit((string) $currency->id, 500.0, 'Recarga de caja');

        $wallet = $this->service->resolveWallet((string) $currency->id);
        $this->assertSame(500.0, (float) $wallet->balance);

        $this->assertDatabaseHas('audits', [
            'auditable_id' => (string) $wallet->id,
            'event' => 'platform_wallet_deposit',
        ]);
    }

    public function test_deposit_with_zero_or_negative_amount_throws() : void
    {
        $currency = $this->usd();

        $this->expectException(InvalidArgumentException::class);

        $this->service->deposit((string) $currency->id, 0.0);
    }

    // ── withdraw() ───────────────────────────────────────────────────────────

    public function test_withdraw_decrements_balance_when_funds_sufficient() : void
    {
        $currency = $this->usd();
        $this->service->deposit((string) $currency->id, 200.0);

        $this->service->withdraw((string) $currency->id, 80.0, 'Extracción de fondos');

        $wallet = $this->service->resolveWallet((string) $currency->id);
        $this->assertSame(120.0, (float) $wallet->balance);

        $this->assertDatabaseHas('audits', [
            'auditable_id' => (string) $wallet->id,
            'event' => 'platform_wallet_withdraw',
        ]);
    }

    public function test_withdraw_throws_when_funds_insufficient() : void
    {
        $currency = $this->usd();
        $this->service->deposit((string) $currency->id, 50.0);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Fondos insuficientes');

        $this->service->withdraw((string) $currency->id, 100.0);
    }

    public function test_withdraw_throws_when_wallet_does_not_exist() : void
    {
        $currency = $this->usd();

        $this->expectException(RuntimeException::class);

        $this->service->withdraw((string) $currency->id, 10.0);
    }

    private function usd() : Currency
    {
        return Currency::firstOrCreate(['iso_code' => 'USD'], ['name' => 'Dollar', 'type' => 'FIAT']);
    }
}
