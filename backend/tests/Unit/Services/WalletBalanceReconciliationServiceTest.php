<?php

namespace Tests\Unit\Services;

use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletMovement;
use App\Domains\Wallet\Services\WalletBalanceReconciliationService;
use App\Models\Currency;
use App\Models\MovementType;
use App\Models\User;
use Database\Seeders\MovementTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletBalanceReconciliationServiceTest extends TestCase
{
    use RefreshDatabase;

    private WalletBalanceReconciliationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(WalletBalanceReconciliationService::class);

        (new MovementTypeSeeder())->run();
        MovementType::forgetCatalogCache();
    }

    private function makeWallet(float $balance): Wallet
    {
        $user     = User::factory()->create();
        $currency = Currency::firstOrCreate(['iso_code' => 'USD'], ['name' => 'Dollar', 'type' => 'FIAT']);

        return Wallet::create([
            'user_id'       => $user->id,
            'currency_id'   => $currency->id,
            'balance'       => $balance,
            'bonus_balance' => 0,
            'status'        => 'ACTIVE',
        ]);
    }

    private function makeMovement(Wallet $wallet, float $balanceAfter, ?\DateTimeInterface $createdAt = null): WalletMovement
    {
        $movement = WalletMovement::create([
            'wallet_id'      => $wallet->id,
            'user_id'        => $wallet->user_id,
            'type'           => WalletMovement::TYPE_DEPOSIT,
            'status'         => WalletMovement::STATUS_COMPLETED,
            'amount'         => 10,
            'balance_before' => 0,
            'balance_after'  => $balanceAfter,
        ]);

        if ($createdAt) {
            $movement->created_at = $createdAt;
            $movement->save();
        }

        return $movement;
    }

    public function test_wallet_with_no_movements_is_not_reported_as_drifted(): void
    {
        $this->makeWallet(100.0);

        $drifted = $this->service->findDrifted();

        $this->assertCount(0, $drifted);
    }

    public function test_wallet_matching_last_movement_balance_after_is_not_drifted(): void
    {
        $wallet = $this->makeWallet(100.0);
        $this->makeMovement($wallet, balanceAfter: 100.0);

        $drifted = $this->service->findDrifted();

        $this->assertCount(0, $drifted);
    }

    public function test_wallet_with_mismatched_balance_is_reported_as_drifted(): void
    {
        $wallet = $this->makeWallet(150.0);
        $this->makeMovement($wallet, balanceAfter: 100.0);

        $drifted = $this->service->findDrifted();

        $this->assertCount(1, $drifted);
        $this->assertSame((string) $wallet->id, $drifted->first()['wallet_id']);
        $this->assertSame(150.0, $drifted->first()['wallet_balance']);
        $this->assertSame(100.0, $drifted->first()['expected_balance']);
    }

    public function test_only_the_most_recent_movement_is_compared(): void
    {
        $wallet = $this->makeWallet(120.0);
        // Movimiento más antiguo con balance_after distinto — no debe
        // usarse para la comparación, solo el más reciente.
        $this->makeMovement($wallet, balanceAfter: 50.0, createdAt: now()->subDay());
        $this->makeMovement($wallet, balanceAfter: 120.0, createdAt: now());

        $drifted = $this->service->findDrifted();

        $this->assertCount(0, $drifted);
    }

    public function test_reconcile_and_alert_notifies_approvers_and_returns_drifted_count(): void
    {
        $approver = User::factory()->create();
        $approver->givePermissionTo('wallet.movements.manage');

        $wallet = $this->makeWallet(150.0);
        $this->makeMovement($wallet, balanceAfter: 100.0);

        $count = $this->service->reconcileAndAlert();

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $approver->id,
            'type'    => 'SYSTEM',
        ]);
    }

    public function test_reconcile_and_alert_returns_zero_when_nothing_drifted(): void
    {
        $wallet = $this->makeWallet(100.0);
        $this->makeMovement($wallet, balanceAfter: 100.0);

        $count = $this->service->reconcileAndAlert();

        $this->assertSame(0, $count);
    }
}
