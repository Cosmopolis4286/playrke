<?php

namespace Tests\Unit\Services;

use App\Domains\Wallet\Exceptions\WalletDomainException;
use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletMovement;
use App\Domains\Wallet\Services\WalletFraudService;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletFraudServiceTest extends TestCase
{
    use RefreshDatabase;

    private WalletFraudService $service;

    protected function setUp() : void
    {
        parent::setUp();

        $this->service = app(WalletFraudService::class);
    }

    public function test_check_double_submit_blocks_same_amount_within_window() : void
    {
        $wallet = $this->makeWallet();

        $this->service->checkDoubleSubmit((string) $wallet->id, 50.0, WalletMovement::TYPE_WITHDRAWAL);

        $this->expectException(WalletDomainException::class);

        $this->service->checkDoubleSubmit((string) $wallet->id, 50.0, WalletMovement::TYPE_WITHDRAWAL);
    }

    public function test_check_duplicate_reference_blocks_reused_reference_within_24h() : void
    {
        $wallet = $this->makeWallet();

        WalletMovement::create([
            'wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'type' => WalletMovement::TYPE_DEPOSIT,
            'status' => WalletMovement::STATUS_PENDING,
            'amount' => 10,
            'balance_before' => 1000,
            'balance_after' => 1000,
            'reference_id' => 'REF-123',
        ]);

        $this->expectException(WalletDomainException::class);

        $this->service->checkDuplicateReference((string) $wallet->id, 'REF-123');
    }

    public function test_invalidate_velocity_is_scoped_by_type() : void
    {
        $wallet = $this->makeWallet();

        $this->service->checkWithdrawalVelocity((string) $wallet->user_id);

        // Invalidar DEPOSIT no debe afectar el conteo cacheado de WITHDRAWAL.
        $this->service->invalidateVelocity((string) $wallet->user_id, WalletMovement::TYPE_DEPOSIT);

        // No lanza: el conteo de retiros de hoy sigue en 0, muy por debajo
        // de MAX_WITHDRAWALS_PER_DAY.
        $this->service->checkWithdrawalVelocity((string) $wallet->user_id);
        $this->addToAssertionCount(1);
    }

    // ── Bug de caché: operationCountToday() no se invalidaba tras crear un movimiento ──

    public function test_velocity_check_blocks_after_invalidating_cache_post_movement() : void
    {
        $wallet = $this->makeWallet();

        // Congela el conteo cacheado en 0 (nadie ha depositado hoy todavía).
        $this->service->checkDepositVelocity((string) $wallet->user_id);

        // Crea 10 movimientos de depósito (MAX_DEPOSITS_PER_DAY) sin pasar
        // por WalletService, para no depender de otros flujos.
        for ($i = 0; $i < 10; $i++) {
            WalletMovement::create([
                'wallet_id' => $wallet->id,
                'user_id' => $wallet->user_id,
                'type' => WalletMovement::TYPE_DEPOSIT,
                'status' => WalletMovement::STATUS_PENDING,
                'amount' => 10,
                'balance_before' => 1000,
                'balance_after' => 1000,
            ]);
        }

        // Sin invalidar, checkDepositVelocity() seguiría usando el conteo
        // cacheado en 0 y nunca bloquearía pese a los 10 depósitos ya creados.
        $this->service->invalidateVelocity((string) $wallet->user_id, WalletMovement::TYPE_DEPOSIT);

        $this->expectException(WalletDomainException::class);

        $this->service->checkDepositVelocity((string) $wallet->user_id);
    }

    private function makeWallet() : Wallet
    {
        $user = User::factory()->create();
        $currency = Currency::firstOrCreate(['iso_code' => 'USD'], ['name' => 'Dollar', 'type' => 'FIAT']);

        return Wallet::create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'balance' => 1000,
            'bonus_balance' => 0,
            'status' => 'ACTIVE',
        ]);
    }
}
