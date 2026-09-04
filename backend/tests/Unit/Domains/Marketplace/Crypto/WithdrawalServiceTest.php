<?php

namespace Tests\Unit\Domains\Marketplace\Crypto;

use App\Domains\Marketplace\Crypto\Exceptions\CryptoCustodyNotConfiguredException;
use App\Domains\Marketplace\Crypto\Exceptions\InvalidWithdrawalStateException;
use App\Domains\Marketplace\Crypto\Models\CryptoWithdrawal;
use App\Domains\Marketplace\Crypto\Services\WithdrawalService;
use App\Models\Currency;
use App\Models\MovementType;
use App\Models\User;
use App\Domains\Wallet\Exceptions\WalletDomainException;
use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletHold;
use App\Domains\Wallet\Models\WalletLimit;
use Database\Seeders\MovementTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WithdrawalServiceTest extends TestCase
{
    use RefreshDatabase;

    private WithdrawalService $service;
    private User $user;
    private User $admin;
    private Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        (new MovementTypeSeeder())->run();
        MovementType::forgetCatalogCache();

        $this->service = app(WithdrawalService::class);
        $usdt = Currency::firstOrCreate(['iso_code' => 'USDT'], ['name' => 'Tether', 'type' => 'CRYPTO']);

        // WalletLimitService::assertWithinLimits() bloquea por precaución si
        // no hay ninguna fila — ver docblock de ese método.
        WalletLimit::firstOrCreate(
            ['currency_id' => $usdt->id, 'kyc_level' => 0],
            [
                'min_deposit' => 1, 'max_deposit' => 100000,
                'min_per_transaction' => 1, 'max_per_transaction' => 100000,
                'max_daily' => 100000, 'max_weekly' => 100000, 'max_monthly' => 100000,
                'is_active' => true,
            ],
        );
        WalletLimit::forgetCatalogCache();

        $this->user = User::factory()->create();
        $this->admin = User::factory()->create();
        $this->wallet = Wallet::create([
            'user_id' => $this->user->id, 'currency_id' => $usdt->id, 'balance' => 100, 'status' => 'ACTIVE',
        ]);
    }

    public function test_request_reserves_amount_without_touching_balance(): void
    {
        $withdrawal = $this->service->request($this->user, $this->wallet, 50.0, 'TDEST');

        $this->assertSame(CryptoWithdrawal::STATUS_RESERVED, $withdrawal->status);
        $this->assertNotNull($withdrawal->wallet_hold_id);
        $this->assertSame(100.0, (float) $this->wallet->fresh()->balance);
        $this->assertSame(50.0, $this->wallet->fresh()->getAvailableBalance());
    }

    public function test_request_throws_when_amount_exceeds_available_balance(): void
    {
        $this->expectException(WalletDomainException::class);
        $this->service->request($this->user, $this->wallet, 500.0, 'TDEST');
    }

    public function test_full_review_flow_to_approved(): void
    {
        $withdrawal = $this->service->request($this->user, $this->wallet, 50.0, 'TDEST');
        $withdrawal = $this->service->startReview($withdrawal);
        $this->assertSame(CryptoWithdrawal::STATUS_REVIEWING, $withdrawal->status);

        $withdrawal = $this->service->approve($withdrawal, $this->admin);
        $this->assertSame(CryptoWithdrawal::STATUS_APPROVED, $withdrawal->status);
        $this->assertSame((string) $this->admin->id, $withdrawal->reviewed_by);
    }

    public function test_double_approve_throws(): void
    {
        $withdrawal = $this->service->request($this->user, $this->wallet, 50.0, 'TDEST');
        $withdrawal = $this->service->startReview($withdrawal);
        $withdrawal = $this->service->approve($withdrawal, $this->admin);

        $this->expectException(InvalidWithdrawalStateException::class);
        $this->service->approve($withdrawal, $this->admin);
    }

    public function test_reject_releases_hold_without_touching_balance(): void
    {
        $withdrawal = $this->service->request($this->user, $this->wallet, 50.0, 'TDEST');
        $withdrawal = $this->service->startReview($withdrawal);

        $withdrawal = $this->service->reject($withdrawal, $this->admin, 'KYC insuficiente');

        $this->assertSame(CryptoWithdrawal::STATUS_REJECTED, $withdrawal->status);
        $this->assertSame('KYC insuficiente', $withdrawal->rejection_reason);
        $this->assertSame(100.0, $this->wallet->fresh()->getAvailableBalance());
        $this->assertSame(WalletHold::STATUS_RELEASED, WalletHold::find($withdrawal->wallet_hold_id)->status);
    }

    public function test_user_can_cancel_before_approval(): void
    {
        $withdrawal = $this->service->request($this->user, $this->wallet, 50.0, 'TDEST');

        $withdrawal = $this->service->cancel($withdrawal);

        $this->assertSame(CryptoWithdrawal::STATUS_CANCELLED, $withdrawal->status);
        $this->assertSame(100.0, $this->wallet->fresh()->getAvailableBalance());
    }

    public function test_cannot_cancel_after_approval(): void
    {
        $withdrawal = $this->service->request($this->user, $this->wallet, 50.0, 'TDEST');
        $withdrawal = $this->service->startReview($withdrawal);
        $withdrawal = $this->service->approve($withdrawal, $this->admin);

        $this->expectException(InvalidWithdrawalStateException::class);
        $this->service->cancel($withdrawal);
    }

    /**
     * Regresión: broadcast() debe liberar el hold automáticamente cuando el
     * CryptoGateway falla (stub sin custodia real) — encontrado durante la
     * validación manual de esta fase. Sin este fix, el saldo del usuario
     * quedaba bloqueado indefinidamente tras un fallo de custodia, ya que
     * FAILED es un estado terminal y fail() no acepta ese estado de entrada.
     */
    public function test_broadcast_releases_hold_automatically_when_gateway_fails(): void
    {
        $withdrawal = $this->service->request($this->user, $this->wallet, 50.0, 'TDEST');
        $withdrawal = $this->service->startReview($withdrawal);
        $withdrawal = $this->service->approve($withdrawal, $this->admin);

        try {
            $this->service->broadcast($withdrawal);
            $this->fail('Se esperaba CryptoCustodyNotConfiguredException.');
        } catch (CryptoCustodyNotConfiguredException) {
            // esperado — TronUsdtProvider es un stub en esta fase
        }

        $withdrawal->refresh();
        $this->assertSame(CryptoWithdrawal::STATUS_FAILED, $withdrawal->status);
        $this->assertSame(WalletHold::STATUS_RELEASED, WalletHold::find($withdrawal->wallet_hold_id)->status);
        $this->assertSame(100.0, $this->wallet->fresh()->getAvailableBalance());
    }

    public function test_broadcast_throws_when_not_approved(): void
    {
        $withdrawal = $this->service->request($this->user, $this->wallet, 50.0, 'TDEST');

        $this->expectException(InvalidWithdrawalStateException::class);
        $this->service->broadcast($withdrawal);
    }
}
