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

        $credited = $this->credit($order, $amount, $reviewer);
        if ($credited->status !== UsdtDepositOrder::STATUS_CREDITED) {
            // Reporte sin monto conocido (pago aún no visto): hay que indicarlo.
            throw UsdtDepositException::amountRequired();
        }

        return $credited;
    }

    /**
     * El jugador reporta el txid de un pago que no se acreditó solo (monto
     * distinto, pagó tarde…). Si el watcher ya vio esa transferencia y no
     * tenía dueño, queda vinculada; en ambos casos pasa a revisión.
     */
    public function claim(UsdtDepositOrder $order, string $txHash) : UsdtDepositOrder
    {
        $txHash = $this->normalizeTxHash($txHash);

        return DB::transaction(function() use ($order, $txHash) {
            $locked = UsdtDepositOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if (! in_array($locked->status, [UsdtDepositOrder::STATUS_PENDING, UsdtDepositOrder::STATUS_EXPIRED], true)) {
                throw UsdtDepositException::notClaimable();
            }
            $transfer = $this->lockClaimableTransfer($txHash, (string) $locked->id);

            $locked->update([
                'status' => UsdtDepositOrder::STATUS_MANUAL_REVIEW,
                'tx_hash' => $txHash,
                'from_address' => $transfer?->from_address,
                'received_amount' => $transfer?->amount,
                'review_reason' => $transfer
                    ? 'Reportado por el jugador; transferencia vista en la dirección sin coincidir.'
                    : 'Reportado por el jugador; transferencia aún no vista por el watcher.',
            ]);
            $transfer?->update(['status' => UsdtInboundTransfer::STATUS_IN_REVIEW, 'usdt_deposit_order_id' => $locked->id]);

            return $locked->fresh();
        });
    }

    /**
     * Reporte de un pago SIN orden previa (pagó sin generar el monto, cerró
     * la pantalla, pagó hace días). Crea una orden directamente en revisión,
     * vinculada a la transferencia si el watcher ya la vio sin dueño.
     */
    public function claimWithoutOrder(User $user, string $txHash) : UsdtDepositOrder
    {
        $this->assertEnabled();
        $txHash = $this->normalizeTxHash($txHash);

        $openOrphanClaims = UsdtDepositOrder::where('user_id', (string) $user->id)
            ->where('status', UsdtDepositOrder::STATUS_MANUAL_REVIEW)
            ->whereNull('amount_suffix')
            ->count();
        if ($openOrphanClaims >= (int) config('payments.usdt_deposits.max_open_orphan_claims', 3)) {
            throw UsdtDepositException::tooManyOpenClaims();
        }

        $wallet = $this->usdtWallet($user);

        return DB::transaction(function() use ($user, $wallet, $txHash) {
            $transfer = $this->lockClaimableTransfer($txHash, null);

            $order = UsdtDepositOrder::create([
                'user_id' => (string) $user->id,
                'wallet_id' => (string) $wallet->id,
                // Sin sufijo: no reserva ni compite por montos únicos.
                'requested_amount' => $transfer ? floor($transfer->amount * 100 + 1e-6) / 100 : 0,
                'amount_units' => $transfer?->amount_units ?? 0,
                'amount_suffix' => null,
                'deposit_address' => (string) config('payments.usdt_deposits.receiving_address'),
                'status' => UsdtDepositOrder::STATUS_MANUAL_REVIEW,
                'tx_hash' => $txHash,
                'from_address' => $transfer?->from_address,
                'received_amount' => $transfer?->amount,
                'expires_at' => now(),
                'review_reason' => $transfer
                    ? 'Pago reportado sin orden; transferencia vista en la dirección.'
                    : 'Pago reportado sin orden; transferencia aún no vista por el watcher.',
            ]);
            $transfer?->update(['status' => UsdtInboundTransfer::STATUS_IN_REVIEW, 'usdt_deposit_order_id' => $order->id]);

            return $order;
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
            $suffix = $this->pickUniqueSuffix();

            return UsdtDepositOrder::create([
                'user_id' => (string) $user->id,
                'wallet_id' => (string) $wallet->id,
                'requested_amount' => $amount,
                'amount_units' => $baseUnits + $suffix,
                'amount_suffix' => $suffix,
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

            $creditAmount = round($amount ?? $locked->creditableAmount(), 2);
            if ($creditAmount < 0.01) {
                return $locked;
            }
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

            UsdtInboundTransfer::where('tx_hash', $locked->tx_hash)
                ->update(['status' => UsdtInboundTransfer::STATUS_MATCHED, 'usdt_deposit_order_id' => $locked->id]);

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
     * El txid no debe estar en otra orden; si el watcher ya vio la
     * transferencia, debe seguir sin dueño. Devuelve la transferencia
     * bloqueada, o null si todavía no se vio.
     */
    private function lockClaimableTransfer(string $txHash, ?string $exceptOrderId) : ?UsdtInboundTransfer
    {
        $usedByOtherOrder = UsdtDepositOrder::where('tx_hash', $txHash)
            ->when($exceptOrderId, fn ($q) => $q->where('id', '!=', $exceptOrderId))
            ->exists();
        if ($usedByOtherOrder) {
            throw UsdtDepositException::txHashAlreadyUsed();
        }

        $transfer = UsdtInboundTransfer::where('tx_hash', $txHash)->lockForUpdate()->first();
        if ($transfer && $transfer->status !== UsdtInboundTransfer::STATUS_UNMATCHED) {
            throw UsdtDepositException::txHashAlreadyUsed();
        }

        return $transfer;
    }

    private function normalizeTxHash(string $txHash) : string
    {
        $txHash = strtolower(trim($txHash));
        if (! preg_match('/^[0-9a-f]{64}$/', $txHash)) {
            throw UsdtDepositException::invalidTxHash();
        }

        return $txHash;
    }

    /**
     * Sufijo aleatorio (1..9999 micro-USDT) único entre TODAS las órdenes que
     * todavía pueden recibir un pago (activas o expiradas hace poco), sin
     * importar el monto base: así el pago se reconoce por sus decimales aunque
     * el exchange le haya descontado una comisión redonda.
     */
    private function pickUniqueSuffix() : int
    {
        $taken = UsdtDepositOrder::reservingSuffixes()->pluck('amount_suffix')->flip();

        for ($i = 0; $i < self::SUFFIX_ATTEMPTS; $i++) {
            $candidate = random_int(1, self::MAX_SUFFIX_UNITS);
            if (! $taken->has($candidate)) {
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
