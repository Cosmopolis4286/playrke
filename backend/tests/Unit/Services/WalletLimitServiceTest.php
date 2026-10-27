<?php

namespace Tests\Unit\Services;

use App\Domains\Wallet\Exceptions\WalletDomainException;
use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletLimit;
use App\Domains\Wallet\Models\WalletMovement;
use App\Domains\Wallet\Services\WalletLimitService;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletLimitServiceTest extends TestCase
{
    use RefreshDatabase;

    private WalletLimitService $service;

    protected function setUp() : void
    {
        parent::setUp();

        $this->service = app(WalletLimitService::class);
    }

    // ── assertWithinLimits() — límites por transacción ──────────────────────

    public function test_assert_within_limits_throws_when_amount_exceeds_max_per_transaction() : void
    {
        $wallet = $this->makeWallet();
        $this->makeLimit(null, ['max_per_transaction' => 50]);

        $this->expectException(WalletDomainException::class);

        $this->service->assertWithinLimits($wallet, 60.0, 0);
    }

    public function test_assert_within_limits_throws_when_no_limit_configured() : void
    {
        $wallet = $this->makeWallet();

        $this->expectException(WalletDomainException::class);

        $this->service->assertWithinLimits($wallet, 10.0, 0);
    }

    public function test_resolve_falls_back_to_global_limit_when_no_currency_specific_row() : void
    {
        $currency = Currency::firstOrCreate(['iso_code' => 'USD'], ['name' => 'Dollar', 'type' => 'FIAT']);
        $this->makeLimit(null, ['max_daily' => 200]);

        $resolved = WalletLimit::resolve((string) $currency->id, 0);

        $this->assertNotNull($resolved);
        $this->assertSame(200.0, (float) $resolved->max_daily);
    }

    // ── WalletLimit::resolve() — currency_id como Snowflake (string vs int de PDO) ──

    public function test_resolve_finds_limit_by_currency_id_even_when_pdo_returns_int() : void
    {
        $currency = Currency::firstOrCreate(['iso_code' => 'USD'], ['name' => 'Dollar', 'type' => 'FIAT']);
        $this->makeLimit((string) $currency->id, ['max_daily' => 50]);

        // Snowflake ids llegan siempre como string desde el lado de la app —
        // resolve() debe encontrar la fila sin importar cómo PDO tipó la
        // columna al leerla de vuelta (bigint unsigned -> puede volver int).
        $resolved = WalletLimit::resolve((string) $currency->id, 0);

        $this->assertNotNull($resolved);
        $this->assertSame(50.0, (float) $resolved->max_daily);
    }

    public function test_resolve_prefers_currency_specific_over_global_fallback() : void
    {
        $currency = Currency::firstOrCreate(['iso_code' => 'USD'], ['name' => 'Dollar', 'type' => 'FIAT']);
        $this->makeLimit(null, ['max_daily' => 200]);
        $this->makeLimit((string) $currency->id, ['max_daily' => 75]);

        $resolved = WalletLimit::resolve((string) $currency->id, 0);

        $this->assertSame(75.0, (float) $resolved->max_daily);
    }

    // ── Bug de caché: withdrawalSums() no se invalidaba tras un retiro ──────

    public function test_second_withdrawal_same_day_is_blocked_once_daily_limit_reached() : void
    {
        $wallet = $this->makeWallet();
        $this->makeLimit(null, ['max_daily' => 100, 'max_weekly' => 1000, 'max_monthly' => 1000, 'max_per_transaction' => 100]);

        // Primer retiro: dentro del límite diario (100).
        $this->service->assertWithinLimits($wallet, 60.0, 0);
        WalletMovement::create([
            'wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'type' => WalletMovement::TYPE_WITHDRAWAL,
            'status' => WalletMovement::STATUS_PENDING,
            'amount' => 60.0,
            'balance_before' => 1000,
            'balance_after' => 940,
        ]);

        // Sin invalidar la caché de sumas, este segundo assertWithinLimits()
        // seguiría viendo daily=0 (el valor cacheado ANTES del primer
        // retiro) y dejaría pasar 60 más, superando el límite de 100.
        $this->service->invalidateWithdrawalSums((string) $wallet->id);

        $this->expectException(WalletDomainException::class);
        $this->service->assertWithinLimits($wallet, 60.0, 0);
    }

    public function test_withdrawal_sums_cache_is_invalidated_by_key() : void
    {
        $wallet = $this->makeWallet();
        $this->makeLimit(null, ['max_daily' => 100]);

        $usageBefore = $this->service->usage($wallet, 0);
        $this->assertSame(0.0, $usageBefore['daily']['used']);

        WalletMovement::create([
            'wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'type' => WalletMovement::TYPE_WITHDRAWAL,
            'status' => WalletMovement::STATUS_COMPLETED,
            'amount' => 40.0,
            'balance_before' => 1000,
            'balance_after' => 960,
        ]);

        // Sin invalidar, usage() seguiría devolviendo el valor cacheado (0)
        // pese a que ya existe un retiro completado de 40.
        $usageStale = $this->service->usage($wallet, 0);
        $this->assertSame(0.0, $usageStale['daily']['used']);

        $this->service->invalidateWithdrawalSums((string) $wallet->id);

        $usageFresh = $this->service->usage($wallet, 0);
        $this->assertSame(40.0, $usageFresh['daily']['used']);
    }

    private function makeLimit(?string $currencyId, array $overrides = []) : WalletLimit
    {
        $limit = WalletLimit::create(array_merge([
            'currency_id' => $currencyId,
            'kyc_level' => 0,
            'min_deposit' => 1,
            'max_deposit' => 1000,
            'min_per_transaction' => 1,
            'max_per_transaction' => 500,
            'max_daily' => 100,
            'max_weekly' => 300,
            'max_monthly' => 1000,
            'is_active' => true,
        ], $overrides));

        WalletLimit::forgetCatalogCache();

        return $limit;
    }

    private function makeWallet(float $balance = 1000.0) : Wallet
    {
        $user = User::factory()->create();
        $currency = Currency::firstOrCreate(['iso_code' => 'USD'], ['name' => 'Dollar', 'type' => 'FIAT']);

        return Wallet::create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'balance' => $balance,
            'bonus_balance' => 0,
            'status' => 'ACTIVE',
        ]);
    }
}
