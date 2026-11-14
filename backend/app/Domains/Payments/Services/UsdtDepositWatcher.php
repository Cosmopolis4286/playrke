<?php

declare(strict_types=1);

namespace App\Domains\Payments\Services;

use App\Domains\Payments\Contracts\BlockchainProviderInterface;
use App\Domains\Payments\DTOs\InboundTransaction;
use App\Domains\Payments\Models\UsdtDepositOrder;
use App\Domains\Payments\Models\UsdtInboundTransfer;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Vigila la dirección receptora de depósitos USDT (sin intermediarios no
 * hay webhook: se consulta TronGrid cada minuto, payments:watch-usdt-deposits).
 *
 * 1. Expira las órdenes PENDING vencidas.
 * 2. Registra cada transferencia nueva una sola vez y la empareja por monto
 *    único: con una orden PENDING → CONFIRMING; con una EXPIRED reciente →
 *    MANUAL_REVIEW (pago tardío); sin dueño → UNMATCHED para tesorería.
 * 3. Acredita las CONFIRMING que ya tienen las confirmaciones requeridas.
 */
final class UsdtDepositWatcher
{
    public const CURSOR_KEY = 'usdt-deposits:cursor-ms';

    /** Un pago no puede ser de una orden creada después (tolerancia de reloj). */
    private const CLOCK_SKEW_SECONDS = 120;

    /** Se relee un margen hacia atrás: tx_hash único descarta lo ya visto. */
    private const CURSOR_OVERLAP_MS = 10 * 60 * 1000;

    public function __construct(
        private readonly BlockchainProviderInterface $blockchain,
        private readonly UsdtDepositService $deposits,
    ) {
    }

    /** @return array{expired: int, seen: int, matched: int, credited: int} */
    public function run() : array
    {
        $stats = ['expired' => 0, 'seen' => 0, 'matched' => 0, 'credited' => 0];
        if (! UsdtDepositService::isEnabled()) {
            return $stats;
        }

        $stats['expired'] = $this->expireOverdue();

        $address = (string) config('payments.usdt_deposits.receiving_address');
        $sinceMs = max(0, (int) Cache::get(self::CURSOR_KEY, now()->subHour()->getTimestampMs()) - self::CURSOR_OVERLAP_MS);
        $maxSeenMs = 0;

        foreach ($this->blockchain->getIncomingTransactions($address, $sinceMs) as $tx) {
            $maxSeenMs = max($maxSeenMs, $tx->timestamp * 1000);

            try {
                $result = $this->ingest($tx);
                $stats['seen'] += $result !== null ? 1 : 0;
                $stats['matched'] += $result === UsdtInboundTransfer::STATUS_MATCHED ? 1 : 0;
            } catch (Throwable $e) {
                Log::error('[UsdtDepositWatcher] Error procesando transferencia', ['tx_hash' => $tx->txHash, 'error' => $e->getMessage()]);
            }
        }

        if ($maxSeenMs > 0) {
            Cache::forever(self::CURSOR_KEY, max($maxSeenMs, (int) Cache::get(self::CURSOR_KEY, 0)));
        }

        $stats['credited'] = $this->creditConfirmed();

        return $stats;
    }

    private function creditConfirmed() : int
    {
        $required = (int) config('payments.tron.confirmations_required', 19);
        $credited = 0;

        UsdtDepositOrder::where('status', UsdtDepositOrder::STATUS_CONFIRMING)->each(function(UsdtDepositOrder $order) use ($required, &$credited) {
            try {
                $confirmations = $this->blockchain->getConfirmations((string) $order->tx_hash) ?? 0;
                if ($confirmations < $required) {
                    return;
                }
                if ($this->deposits->credit($order)->status === UsdtDepositOrder::STATUS_CREDITED) {
                    $credited++;
                }
            } catch (Throwable $e) {
                Log::error('[UsdtDepositWatcher] Error acreditando', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            }
        });

        return $credited;
    }

    private function expireOverdue() : int
    {
        return UsdtDepositOrder::where('status', UsdtDepositOrder::STATUS_PENDING)
            ->where('expires_at', '<', now())
            ->update(['status' => UsdtDepositOrder::STATUS_EXPIRED]);
    }

    /**
     * Orden dueña de un monto único: la PENDING (pago a tiempo) o, si ya
     * venció, la EXPIRED reciente con ese monto. Nunca una creada después
     * del pago.
     */
    private function findOrderFor(int $units, Carbon $blockTime) : ?UsdtDepositOrder
    {
        $cooldown = now()->subHours((int) config('payments.usdt_deposits.amount_reuse_cooldown_hours', 48));

        return UsdtDepositOrder::where('amount_units', $units)
            ->whereNull('tx_hash')
            ->where('created_at', '<=', $blockTime->copy()->addSeconds(self::CLOCK_SKEW_SECONDS))
            ->where(fn ($q) => $q->where('status', UsdtDepositOrder::STATUS_PENDING)
                ->orWhere(fn ($q) => $q->where('status', UsdtDepositOrder::STATUS_EXPIRED)->where('expires_at', '>', $cooldown)))
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [UsdtDepositOrder::STATUS_PENDING])
            ->lockForUpdate()
            ->first();
    }

    /** @return string|null estado de la transferencia, o null si ya se había visto */
    private function ingest(InboundTransaction $tx) : ?string
    {
        if (strcasecmp($tx->tokenContract, $this->blockchain->tokenContract()) !== 0) {
            return null;
        }
        if (UsdtInboundTransfer::where('tx_hash', $tx->txHash)->exists()) {
            return null;
        }

        $units = (int) round($tx->amount * UsdtDepositOrder::UNITS_PER_USDT);
        $blockTime = Carbon::createFromTimestamp($tx->timestamp);

        try {
            return DB::transaction(function() use ($tx, $units, $blockTime) {
                $transfer = UsdtInboundTransfer::create([
                    'tx_hash' => $tx->txHash,
                    'from_address' => $tx->fromAddress,
                    'to_address' => $tx->toAddress,
                    'amount' => $tx->amount,
                    'amount_units' => $units,
                    'block_time' => $blockTime,
                    'status' => UsdtInboundTransfer::STATUS_UNMATCHED,
                ]);

                // El jugador ya la reportó con su txid (orden en revisión):
                // se vincula y se completan los datos para tesorería.
                $claimed = UsdtDepositOrder::where('tx_hash', $tx->txHash)->lockForUpdate()->first();
                if ($claimed) {
                    $claimed->update(['from_address' => $tx->fromAddress, 'received_amount' => $tx->amount, 'detected_at' => now()]);
                    $transfer->update(['status' => UsdtInboundTransfer::STATUS_MATCHED, 'usdt_deposit_order_id' => $claimed->id]);

                    return UsdtInboundTransfer::STATUS_MATCHED;
                }

                $order = $this->findOrderFor($units, $blockTime);
                if (! $order) {
                    Log::warning('[UsdtDepositWatcher] Transferencia sin orden que coincida', ['tx_hash' => $tx->txHash, 'amount' => $tx->amount]);

                    return UsdtInboundTransfer::STATUS_UNMATCHED;
                }

                $isOnTime = $order->status === UsdtDepositOrder::STATUS_PENDING && $blockTime->lte($order->expires_at);
                $order->update([
                    'status' => $isOnTime ? UsdtDepositOrder::STATUS_CONFIRMING : UsdtDepositOrder::STATUS_MANUAL_REVIEW,
                    'tx_hash' => $tx->txHash,
                    'from_address' => $tx->fromAddress,
                    'received_amount' => $tx->amount,
                    'detected_at' => now(),
                    'review_reason' => $isOnTime ? null : 'Pago recibido después de vencer la orden.',
                ]);
                $transfer->update([
                    'status' => $isOnTime ? UsdtInboundTransfer::STATUS_MATCHED : UsdtInboundTransfer::STATUS_LATE,
                    'usdt_deposit_order_id' => $order->id,
                ]);

                return $isOnTime ? UsdtInboundTransfer::STATUS_MATCHED : UsdtInboundTransfer::STATUS_LATE;
            });
        } catch (QueryException $e) {
            // Otra corrida la registró al mismo tiempo (tx_hash único).
            if (str_contains($e->getMessage(), 'tx_hash')) {
                return null;
            }

            throw $e;
        }
    }
}
