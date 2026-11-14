<?php

declare(strict_types=1);

namespace App\Domains\Payments\Services;

use App\Domains\Payments\Exceptions\UsdtDepositException;
use App\Domains\Payments\Models\UsdtDepositOrder;
use App\Domains\Payments\Models\UsdtInboundTransfer;
use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Services\WalletLimitService;
use App\Domains\Wallet\Services\WalletService;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Depósito USDT-TRC20 con "una dirección + monto único" (ver
 * config payments.usdt_deposits). El jugador pide N USDT y recibe un monto
 * único N + 0.00000X..0.00999X que identifica su pago en la dirección común;
 * UsdtDepositWatcher lo detecta y acredita. Lo que no se puede emparejar
 * solo pasa por revisión de tesorería (approve/reject).
 *
 * Se acredita siempre el monto pedido (2 decimales, igual que el saldo de
 * la billetera); el sufijo único (< 0.01 USDT) queda como redondeo.
 */
final class UsdtDepositService
{
    private const MAX_SUFFIX_UNITS = 9_999;

    private const SUFFIX_ATTEMPTS = 30;

    public static function isEnabled() : bool
    {
        return (bool) config('payments.usdt_deposits.enabled')
            && ! empty(config('payments.usdt_deposits.receiving_address'));
    }

    public function __construct(
        private readonly WalletService $walletService,
        private readonly WalletLimitService $limitService,
    ) {
    }

    /** Tesorería aprueba una orden en revisión, con el monto verificado en el explorador. */
    public function approve(UsdtDepositOrder $order, User $reviewer, ?float $amount = null) : UsdtDepositOrder
    {
        $this->assertReviewable($order, $reviewer);

        return $this->credit($order, $amount, $reviewer);
    }

    /**
     * El jugador reporta el txid de un pago que no se acreditó solo (monto
     * distinto, pagó tarde…). Si el watcher ya vio esa transferencia y no
     * tenía dueño, queda vinculada; en ambos casos pasa a revisión.
     */
    public function claim(UsdtDepositOrder $order, string $txHash) : UsdtDepositOrder
    {
        $txHash = strtolower(trim($txHash));
        if (! preg_match('/^[0-9a-f]{64}$/', $txHash)) {
            throw UsdtDepositException::invalidTxHash();
        }

        return DB::transaction(function() use ($order, $txHash) {
            $locked = UsdtDepositOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if (! in_array($locked->status, [UsdtDepositOrder::STATUS_PENDING, UsdtDepositOrder::STATUS_EXPIRED], true)) {
                throw UsdtDepositException::notClaimable();
            }
            if (UsdtDepositOrder::where('tx_hash', $txHash)->where('id', '!=', $locked->id)->exists()) {
                throw UsdtDepositException::txHashAlreadyUsed();
            }

            $transfer = UsdtInboundTransfer::where('tx_hash', $txHash)->lockForUpdate()->first();
            if ($transfer && $transfer->status !== UsdtInboundTransfer::STATUS_UNMATCHED) {
                throw UsdtDepositException::txHashAlreadyUsed();
            }

            $locked->update([
                'status' => UsdtDepositOrder::STATUS_MANUAL_REVIEW,
                'tx_hash' => $txHash,
                'from_address' => $transfer?->from_address,
                'received_amount' => $transfer?->amount,
                'review_reason' => $transfer
                    ? 'Reportado por el jugador; transferencia vista en la dirección sin coincidir.'
                    : 'Reportado por el jugador; transferencia aún no vista por el watcher.',
            ]);
            $transfer?->update(['status' => UsdtInboundTransfer::STATUS_MATCHED, 'usdt_deposit_order_id' => $locked->id]);

            return $locked->fresh();
        });
    }

    public function create(User $user, float $amount) : UsdtDepositOrder
    {
        $this->assertEnabled();

        $requestedUnits = (int) round($amount * 100);
        if ($amount <= 0 || abs($amount * 100 - $requestedUnits) > 1e-6) {
            throw UsdtDepositException::invalidAmount();
        }

        $wallet = $this->usdtWallet($user);
        $this->limitService->assertDepositWithinLimits($wallet, $amount, (int) ($user->kyc_level ?? 0));

        // El lock serializa la elección del sufijo: dos órdenes simultáneas
        // nunca pueden recibir el mismo monto único.
        return Cache::lock('usdt-deposits:amount-allocation', 10)->block(5, function() use ($user, $wallet, $amount, $requestedUnits) {
            $baseUnits = $requestedUnits * (UsdtDepositOrder::UNITS_PER_USDT / 100);
            $amountUnits = $this->pickUniqueAmountUnits($baseUnits);

            return UsdtDepositOrder::create([
                'user_id' => (string) $user->id,
                'wallet_id' => (string) $wallet->id,
                'requested_amount' => $amount,
                'amount_units' => $amountUnits,
                'deposit_address' => (string) config('payments.usdt_deposits.receiving_address'),
                'status' => UsdtDepositOrder::STATUS_PENDING,
                'expires_at' => now()->addMinutes((int) config('payments.usdt_deposits.window_minutes', 30)),
            ]);
        });
    }

    /**
     * Acredita una orden detectada y confirmada on-chain (llamado por el
     * watcher) o aprobada por tesorería. Idempotente por tx_hash: el
     * movimiento usa la llave "usdt_deposit:{tx_hash}".
     */
    public function credit(UsdtDepositOrder $order, ?float $amount = null, ?User $reviewer = null) : UsdtDepositOrder
    {
        return DB::transaction(function() use ($order, $amount, $reviewer) {
            $locked = UsdtDepositOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $expected = $reviewer ? UsdtDepositOrder::STATUS_MANUAL_REVIEW : UsdtDepositOrder::STATUS_CONFIRMING;
            if ($locked->status !== $expected || ! $locked->tx_hash) {
                return $locked;
            }

            $creditAmount = round($amount ?? $locked->requested_amount, 2);
            $wallet = Wallet::whereKey($locked->wallet_id)->firstOrFail();
            $movement = $this->walletService->recordCryptoDeposit(
                $wallet,
                $creditAmount,
                'Depósito USDT-TRC20 · tx ' . substr($locked->tx_hash, 0, 12) . '…',
                'usdt_deposit:' . $locked->tx_hash,
            );

            $locked->update(array_filter([
                'status' => UsdtDepositOrder::STATUS_CREDITED,
                'credited_amount' => $creditAmount,
                'credited_at' => now(),
                'wallet_movement_id' => (string) $movement->id,
                'reviewed_by' => $reviewer ? (string) $reviewer->id : null,
                'reviewed_at' => $reviewer ? now() : null,
            ], fn ($value) => $value !== null));

            Log::info('[UsdtDeposit] Acreditado', ['order_id' => $locked->id, 'amount' => $creditAmount, 'manual' => $reviewer !== null]);

            return $locked->fresh();
        });
    }

    public function findForUser(string $orderId, string $userId) : ?UsdtDepositOrder
    {
        return UsdtDepositOrder::where('id', $orderId)->where('user_id', $userId)->first();
    }

    public function reject(UsdtDepositOrder $order, User $reviewer, string $reason) : UsdtDepositOrder
    {
        $this->assertReviewable($order, $reviewer);

        $affected = UsdtDepositOrder::whereKey($order->id)
            ->where('status', UsdtDepositOrder::STATUS_MANUAL_REVIEW)
            ->update([
                'status' => UsdtDepositOrder::STATUS_REJECTED,
                'review_reason' => $reason,
                'reviewed_by' => (string) $reviewer->id,
                'reviewed_at' => now(),
            ]);
        if ($affected === 0) {
            throw UsdtDepositException::notInReview();
        }

        // La transferencia (si se vio) vuelve a quedar sin dueño: otro jugador
        // puede ser el verdadero dueño y reportarla.
        UsdtInboundTransfer::where('usdt_deposit_order_id', $order->id)
            ->update(['status' => UsdtInboundTransfer::STATUS_UNMATCHED, 'usdt_deposit_order_id' => null]);

        return $order->fresh();
    }

    private function assertEnabled() : void
    {
        if (! self::isEnabled()) {
            throw UsdtDepositException::disabled();
        }
    }

    private function assertReviewable(UsdtDepositOrder $order, User $reviewer) : void
    {
        if ((string) $order->user_id === (string) $reviewer->id) {
            throw UsdtDepositException::selfReview();
        }
        if ($order->status !== UsdtDepositOrder::STATUS_MANUAL_REVIEW) {
            throw UsdtDepositException::notInReview();
        }
    }

    /**
     * Sufijo aleatorio (1..9999 micro-USDT) que no choque con otra orden que
     * todavía pueda recibir un pago: activas, o expiradas hace poco (un pago
     * tardío debe seguir identificando a su dueño).
     */
    private function pickUniqueAmountUnits(int $baseUnits) : int
    {
        $cooldown = now()->subHours((int) config('payments.usdt_deposits.amount_reuse_cooldown_hours', 48));

        for ($i = 0; $i < self::SUFFIX_ATTEMPTS; $i++) {
            $candidate = $baseUnits + random_int(1, self::MAX_SUFFIX_UNITS);

            $taken = UsdtDepositOrder::where('amount_units', $candidate)
                ->where(fn ($q) => $q->whereIn('status', UsdtDepositOrder::ACTIVE_STATUSES)
                    ->orWhere(fn ($q) => $q->where('status', UsdtDepositOrder::STATUS_EXPIRED)->where('expires_at', '>', $cooldown)))
                ->exists();

            if (! $taken) {
                return $candidate;
            }
        }

        throw UsdtDepositException::noUniqueAmountAvailable();
    }

    private function usdtWallet(User $user) : Wallet
    {
        $currency = Currency::where('iso_code', config('payments.usdt_deposits.currency_iso', 'USDT'))->first();
        if (! $currency) {
            throw UsdtDepositException::disabled();
        }

        return Wallet::firstOrCreate(
            ['user_id' => (string) $user->id, 'currency_id' => (string) $currency->id],
            ['balance' => 0.00, 'bonus_balance' => 0.00, 'status' => 'ACTIVE'],
        );
    }
}
