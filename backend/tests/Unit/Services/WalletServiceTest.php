<?php

namespace Tests\Unit\Services;

use App\Models\Currency;
use App\Models\MovementType;
use App\Models\User;
use App\Domains\Wallet\Exceptions\WalletDomainException;
use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletMovement;
use App\Domains\Wallet\Repositories\WalletRepository;
use App\Domains\Wallet\Services\WalletService;
use Database\Seeders\MovementTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletServiceTest extends TestCase
{
    use RefreshDatabase;

    private WalletService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(WalletService::class);

        // RefreshDatabase migra desde cero pero no corre seeders — el catálogo
        // movement_types (PRIZE, TRIVIA_ENTRY, etc.) es requerido por la FK en
        // wallet_movements.type.
        (new MovementTypeSeeder())->run();
        MovementType::forgetCatalogCache();
    }

    private function makeWallet(float $balance = 0.0, float $bonusBalance = 0.0): Wallet
    {
        $user     = User::factory()->create();
        $currency = Currency::firstOrCreate(['iso_code' => 'USD'], ['name' => 'Dollar', 'type' => 'FIAT']);

        $this->actingAs($user);

        return Wallet::create([
            'user_id'       => $user->id,
            'currency_id'   => $currency->id,
            'balance'       => $balance,
            'bonus_balance' => $bonusBalance,
            'status'        => 'ACTIVE',
        ]);
    }

    // ── assertPositiveAmount() — defensa de última línea en el núcleo ───────
    // Ninguno de estos métodos validaba amount > 0 antes; un caller con un
    // bug de cálculo podía colar un monto negativo/cero y generar un
    // WalletMovement fantasma sin tocar el balance (rompiendo la cuadratura
    // del ledger sin que nadie lo note).

    public function test_record_prize_with_zero_amount_throws(): void
    {
        $wallet = $this->makeWallet(balance: 10.0);

        $this->expectException(WalletDomainException::class);

        $this->service->recordPrize(wallet: $wallet, amount: 0.0, gameId: 'game-1');
    }

    public function test_record_prize_with_negative_amount_throws(): void
    {
        $wallet = $this->makeWallet(balance: 10.0);

        $this->expectException(WalletDomainException::class);

        $this->service->recordPrize(wallet: $wallet, amount: -25.0, gameId: 'game-1');
    }

    public function test_record_card_purchase_with_zero_amount_throws(): void
    {
        $wallet = $this->makeWallet(balance: 10.0);

        $this->expectException(WalletDomainException::class);

        $this->service->recordCardPurchase((string) $wallet->id, 0.0);
    }

    public function test_record_trivia_entry_with_negative_amount_throws(): void
    {
        $wallet = $this->makeWallet(balance: 10.0);

        $this->expectException(WalletDomainException::class);

        $this->service->recordTriviaEntry(wallet: $wallet, amount: -5.0);
    }

    public function test_record_refund_with_zero_amount_throws(): void
    {
        $wallet = $this->makeWallet(balance: 10.0);

        $this->expectException(WalletDomainException::class);

        $this->service->recordRefund(wallet: $wallet, amount: 0.0, gameId: 'game-1');
    }

    public function test_record_admin_deposit_with_negative_amount_throws(): void
    {
        $wallet = $this->makeWallet(balance: 10.0);

        $this->expectException(WalletDomainException::class);

        $this->service->recordAdminDeposit($wallet->user, -20.0);
    }

    // ── recordPrize() ───────────────────────────────────────────────────────

    public function test_record_prize_withdrawable_true_credits_balance_with_type_prize(): void
    {
        $wallet = $this->makeWallet(balance: 10.0);

        $movement = $this->service->recordPrize(
            wallet: $wallet,
            amount: 25.0,
            gameId: 'game-1',
        );

        $wallet->refresh();

        $this->assertSame(35.0, (float) $wallet->balance);
        $this->assertSame(0.0, (float) $wallet->bonus_balance);
        $this->assertSame(WalletMovement::TYPE_PRIZE, $movement->type);
    }

    public function test_record_prize_withdrawable_false_credits_bonus_balance_with_type_bonus_prize(): void
    {
        $wallet = $this->makeWallet(balance: 10.0);

        $movement = $this->service->recordPrize(
            wallet: $wallet,
            amount: 25.0,
            gameId: 'game-1',
            withdrawable: false,
        );

        $wallet->refresh();

        $this->assertSame(10.0, (float) $wallet->balance);
        $this->assertSame(25.0, (float) $wallet->bonus_balance);
        $this->assertSame(WalletMovement::TYPE_BONUS_PRIZE, $movement->type);
        $this->assertFalse($movement->metadata['withdrawable']);
    }

    // ── recordCardPurchase() / recordTriviaEntry(): consumo de bono primero ─

    public function test_purchase_that_fits_entirely_in_bonus_only_consumes_bonus(): void
    {
        $wallet = $this->makeWallet(balance: 50.0, bonusBalance: 20.0);

        $result = $this->service->recordCardPurchase((string) $wallet->id, 15.0);

        $wallet->refresh();

        $this->assertSame(50.0, (float) $wallet->balance);
        $this->assertSame(5.0, (float) $wallet->bonus_balance);
        $this->assertSame(15.0, $result['bonus_spent']);
        $this->assertSame(0.0, $result['balance_spent']);
    }

    public function test_purchase_exceeding_bonus_consumes_bonus_then_balance(): void
    {
        $wallet = $this->makeWallet(balance: 50.0, bonusBalance: 20.0);

        $result = $this->service->recordCardPurchase((string) $wallet->id, 30.0);

        $wallet->refresh();

        $this->assertSame(40.0, (float) $wallet->balance);
        $this->assertSame(0.0, (float) $wallet->bonus_balance);
        $this->assertSame(20.0, $result['bonus_spent']);
        $this->assertSame(10.0, $result['balance_spent']);

        $movement = WalletMovement::where('wallet_id', $wallet->id)->latest()->first();
        $this->assertSame(20.0, (float) $movement->metadata['bonus_amount']);
        $this->assertSame(10.0, (float) $movement->metadata['balance_amount']);
    }

    public function test_purchase_exceeding_bonus_plus_balance_throws_and_rolls_back(): void
    {
        $wallet = $this->makeWallet(balance: 10.0, bonusBalance: 5.0);

        try {
            $this->service->recordCardPurchase((string) $wallet->id, 100.0);
            $this->fail('Expected WalletDomainException was not thrown.');
        } catch (WalletDomainException $e) {
            // esperado
        }

        $wallet->refresh();

        $this->assertSame(10.0, (float) $wallet->balance);
        $this->assertSame(5.0, (float) $wallet->bonus_balance);
    }

    public function test_trivia_entry_consumes_bonus_before_balance(): void
    {
        $wallet = $this->makeWallet(balance: 50.0, bonusBalance: 8.0);

        $result = $this->service->recordTriviaEntry($wallet, 10.0);

        $result->refresh();

        $this->assertSame(48.0, (float) $result->balance);
        $this->assertSame(0.0, (float) $result->bonus_balance);

        $movement = WalletMovement::where('wallet_id', $wallet->id)
            ->where('type', WalletMovement::TYPE_TRIVIA_ENTRY)
            ->latest()->first();
        $this->assertSame(8.0, (float) $movement->metadata['bonus_amount']);
        $this->assertSame(2.0, (float) $movement->metadata['balance_amount']);
    }

    public function test_trivia_entry_with_same_idempotency_key_does_not_charge_twice(): void
    {
        $wallet = $this->makeWallet(balance: 50.0, bonusBalance: 8.0);
        $key = 'trivia_entry:room-idempotent:user-idempotent';

        $first = $this->service->recordTriviaEntry(
            wallet: $wallet,
            amount: 10.0,
            idempotencyKey: $key,
            roomId: 'room-idempotent',
        );

        $second = $this->service->recordTriviaEntry(
            wallet: $wallet->fresh(),
            amount: 10.0,
            idempotencyKey: $key,
            roomId: 'room-idempotent',
        );

        $this->assertSame((string) $wallet->id, (string) $first->id);
        $this->assertSame((string) $wallet->id, (string) $second->id);
        $this->assertSame(48.0, (float) $wallet->fresh()->balance);
        $this->assertSame(0.0, (float) $wallet->fresh()->bonus_balance);
        $this->assertSame(
            1,
            WalletMovement::where('idempotency_key', $key)
                ->where('type', WalletMovement::TYPE_TRIVIA_ENTRY)
                ->count(),
        );
    }

    public function test_trivia_entry_rejects_reusing_idempotency_key_with_different_amount(): void
    {
        $wallet = $this->makeWallet(balance: 50.0);
        $key = 'trivia_entry:room-conflict:user-conflict';

        $this->service->recordTriviaEntry(
            wallet: $wallet,
            amount: 10.0,
            idempotencyKey: $key,
            roomId: 'room-conflict',
        );

        try {
            $this->service->recordTriviaEntry(
                wallet: $wallet->fresh(),
                amount: 12.0,
                idempotencyKey: $key,
                roomId: 'room-conflict',
            );
            $this->fail('Reusing an idempotency key with a different amount must fail.');
        } catch (\\RuntimeException $exception) {
            $this->assertStringContainsString('datos incompatibles', $exception->getMessage());
        }

        $this->assertSame(40.0, (float) $wallet->fresh()->balance);
        $this->assertSame(
            1,
            WalletMovement::where('idempotency_key', $key)->count(),
        );
    }

    public function test_trivia_entry_rejects_reusing_idempotency_key_for_another_wallet(): void
    {
        $firstWallet = $this->makeWallet(balance: 50.0);
        $secondWallet = $this->makeWallet(balance: 30.0);
        $key = 'trivia_entry:room-owner:user-owner';

        $this->service->recordTriviaEntry(
            wallet: $firstWallet,
            amount: 10.0,
            idempotencyKey: $key,
            roomId: 'room-owner',
        );

        try {
            $this->service->recordTriviaEntry(
                wallet: $secondWallet,
                amount: 10.0,
                idempotencyKey: $key,
                roomId: 'room-owner',
            );
            $this->fail('An idempotency key must not be reusable by another wallet owner.');
        } catch (\\RuntimeException $exception) {
            $this->assertStringContainsString('datos incompatibles', $exception->getMessage());
        }

        $this->assertSame(40.0, (float) $firstWallet->fresh()->balance);
        $this->assertSame(30.0, (float) $secondWallet->fresh()->balance);
        $this->assertSame(
            1,
            WalletMovement::where('idempotency_key', $key)->count(),
        );
    }

    // ── requestWithdraw(): el bono nunca cubre un retiro ────────────────────

    public function test_withdraw_ignores_bonus_balance_and_fails_when_balance_insufficient(): void
    {
        $wallet = $this->makeWallet(balance: 5.0, bonusBalance: 100.0);

        $this->expectException(WalletDomainException::class);

        $this->service->requestWithdraw(
            wallet:        $wallet,
            amount:        20.0,
            directoryId:   'dir-1',
            directoryType: 'pago_movil',
        );
    }

    // ── recordRefund(): reembolso proporcional bono/real ────────────────────

    public function test_refund_splits_proportionally_between_bonus_and_balance(): void
    {
        $wallet = $this->makeWallet(balance: 0.0, bonusBalance: 0.0);

        $movement = $this->service->recordRefund(
            wallet:       $wallet,
            amount:       15.0,
            gameId:       'room-1',
            bonusPortion: 6.0,
        );

        $wallet->refresh();

        $this->assertSame(9.0, (float) $wallet->balance);
        $this->assertSame(6.0, (float) $wallet->bonus_balance);
        $this->assertSame(6.0, (float) $movement->metadata['bonus_amount']);
        $this->assertSame(9.0, (float) $movement->metadata['balance_amount']);
    }

    // ── WalletRepository::consumeBonusFirst() en aislamiento ────────────────

    public function test_consume_bonus_first_exact_bonus_amount(): void
    {
        $wallet = $this->makeWallet(balance: 30.0, bonusBalance: 12.0);
        $repo   = app(WalletRepository::class);

        $split = $repo->consumeBonusFirst((string) $wallet->id, 12.0);

        $wallet->refresh();
        $this->assertSame(12.0, $split['bonus_spent']);
        $this->assertSame(0.0, $split['balance_spent']);
        $this->assertSame(0.0, (float) $wallet->bonus_balance);
        $this->assertSame(30.0, (float) $wallet->balance);
    }

    public function test_consume_bonus_first_throws_when_amount_exceeds_total(): void
    {
        $wallet = $this->makeWallet(balance: 5.0, bonusBalance: 5.0);
        $repo   = app(WalletRepository::class);

        $this->expectException(WalletDomainException::class);

        $repo->consumeBonusFirst((string) $wallet->id, 11.0);
    }

    public function test_consume_bonus_first_with_zero_amount_spends_nothing(): void
    {
        $wallet = $this->makeWallet(balance: 5.0, bonusBalance: 5.0);
        $repo   = app(WalletRepository::class);

        $split = $repo->consumeBonusFirst((string) $wallet->id, 0.0);

        $this->assertSame(0.0, $split['bonus_spent']);
        $this->assertSame(0.0, $split['balance_spent']);
    }

    // ── approveDeposit() / rejectWithdrawal() — doble-aprobación/rechazo ────
    // No reproduce la carrera real entre dos requests HTTP paralelos (eso
    // requiere dos conexiones DB concurrentes), pero sí verifica que la
    // segunda llamada sobre un movimiento ya procesado falla en vez de
    // duplicar el crédito — la propiedad que lockForUpdate() + revalidar el
    // status DENTRO de la transacción garantiza incluso bajo concurrencia.

    public function test_approve_deposit_credits_balance_and_marks_completed(): void
    {
        $wallet = $this->makeWallet(balance: 10.0);

        $movement = WalletMovement::create([
            'wallet_id'      => $wallet->id,
            'user_id'        => $wallet->user_id,
            'type'           => WalletMovement::TYPE_DEPOSIT,
            'status'         => WalletMovement::STATUS_PENDING,
            'amount'         => 50.0,
            'balance_before' => 10.0,
            'balance_after'  => 10.0,
        ]);

        $this->service->approveDeposit($movement);

        $this->assertSame(60.0, (float) $wallet->fresh()->balance);
        $this->assertSame(WalletMovement::STATUS_COMPLETED, $movement->fresh()->status);
    }

    public function test_approve_deposit_twice_throws_and_does_not_double_credit(): void
    {
        $wallet = $this->makeWallet(balance: 10.0);

        $movement = WalletMovement::create([
            'wallet_id'      => $wallet->id,
            'user_id'        => $wallet->user_id,
            'type'           => WalletMovement::TYPE_DEPOSIT,
            'status'         => WalletMovement::STATUS_PENDING,
            'amount'         => 50.0,
            'balance_before' => 10.0,
            'balance_after'  => 10.0,
        ]);

        $this->service->approveDeposit($movement);

        // Segunda aprobación sobre el mismo movimiento (ya COMPLETED): debe
        // rechazarse, no volver a incrementar el balance.
        $this->expectException(WalletDomainException::class);

        try {
            $this->service->approveDeposit($movement->fresh());
        } finally {
            $this->assertSame(60.0, (float) $wallet->fresh()->balance);
        }
    }

    public function test_reject_withdrawal_refunds_balance_and_marks_rejected(): void
    {
        $wallet = $this->makeWallet(balance: 40.0);

        $movement = WalletMovement::create([
            'wallet_id'      => $wallet->id,
            'user_id'        => $wallet->user_id,
            'type'           => WalletMovement::TYPE_WITHDRAWAL,
            'status'         => WalletMovement::STATUS_PENDING,
            'amount'         => 25.0,
            'balance_before' => 65.0,
            'balance_after'  => 40.0,
        ]);

        $this->service->rejectWithdrawal($movement);

        $this->assertSame(65.0, (float) $wallet->fresh()->balance);
        $this->assertSame(WalletMovement::STATUS_REJECTED, $movement->fresh()->status);
    }

    public function test_reject_withdrawal_twice_throws_and_does_not_double_refund(): void
    {
        $wallet = $this->makeWallet(balance: 40.0);

        $movement = WalletMovement::create([
            'wallet_id'      => $wallet->id,
            'user_id'        => $wallet->user_id,
            'type'           => WalletMovement::TYPE_WITHDRAWAL,
            'status'         => WalletMovement::STATUS_PENDING,
            'amount'         => 25.0,
            'balance_before' => 65.0,
            'balance_after'  => 40.0,
        ]);

        $this->service->rejectWithdrawal($movement);

        $this->expectException(WalletDomainException::class);

        try {
            $this->service->rejectWithdrawal($movement->fresh());
        } finally {
            $this->assertSame(65.0, (float) $wallet->fresh()->balance);
        }
    }
}
