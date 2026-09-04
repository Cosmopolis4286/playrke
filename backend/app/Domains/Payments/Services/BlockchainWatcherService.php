<?php

declare(strict_types=1);

namespace App\Domains\Payments\Services;

use App\Domains\Marketplace\Crypto\Models\CryptoTransaction;
use App\Domains\Payments\Contracts\BlockchainProviderInterface;
use App\Domains\Payments\Events\PaymentOrderUpdated;
use App\Domains\Payments\Models\PaymentAttempt;
use App\Domains\Payments\Models\PaymentOrder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Evalúa cada PaymentOrder activa contra el proveedor de blockchain y avanza
 * su estado según lo que realmente observa on-chain. Ver
 * PAYMENTS_USDT_TRC20_DESIGN.md secciones 3 y 6 para el diagrama de flujo y
 * la tabla de riesgos que cada guard de este servicio cubre.
 *
 * Nunca confía en nada que no haya verificado él mismo contra
 * BlockchainProviderInterface — no hay webhook de terceros en este diseño
 * (ver sección 5 del documento). Cada evaluación queda registrada en
 * PaymentAttempt, éxito o rechazo, para trazabilidad.
 *
 * Ejecutado periódicamente por WatchPaymentOrdersCommand (Job + Scheduler).
 */
final class BlockchainWatcherService
{
    public function __construct(
        private readonly BlockchainProviderInterface $blockchain,
        private readonly PurchaseService $purchaseService,
    ) {
    }

    /**
     * Evalúa todas las órdenes activas (no terminales) y expira las vencidas
     * antes de consultar la blockchain — nunca acredita una orden que ya
     * pasó su expires_at, sin importar qué reporte el proveedor.
     */
    public function watchAll(): void
    {
        $this->expireOverdueOrders();

        PaymentOrder::active()->chunkById(100, function ($orders) {
            foreach ($orders as $order) {
                try {
                    $this->evaluate($order);
                } catch (\Throwable $e) {
                    Log::channel('slack')->error('payments.watcher.evaluate_failed', [
                        'payment_order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });
    }

    public function evaluate(PaymentOrder $order): PaymentOrder
    {
        if ($order->isTerminal()) {
            return $order;
        }

        $incoming = $this->blockchain->getIncomingTransactions($order->deposit_address);

        $match = collect($incoming)->first(
            fn ($tx) => $tx->tokenContract === $order->token_contract
        );

        if (!$match) {
            return $order;
        }

        // tx_hash ya usado por OTRA orden -> nunca se reasigna (segunda
        // capa de defensa contra doble-acreditación, además del unique en
        // crypto_transactions.tx_hash).
        $alreadyLinked = PaymentOrder::where('crypto_transaction_id', function ($query) use ($match) {
            $query->select('id')->from('crypto_transactions')->where('tx_hash', $match->txHash);
        })->where('id', '!=', $order->id)->exists();

        if ($alreadyLinked) {
            $this->logAttempt($order, $match->txHash, PaymentAttempt::ACTION_DETECT, PaymentAttempt::RESULT_REJECTED, 'tx_hash ya asociado a otra orden');
            return $order;
        }

        if ($order->status === PaymentOrder::STATUS_PENDING) {
            $order = $this->markDetected($order, $match);
        }

        $confirmations = $this->blockchain->getConfirmations($match->txHash) ?? 0;
        $required = (int) config('payments.tron.confirmations_required', 19);

        if ($confirmations < $required) {
            $this->transitionTo($order, PaymentOrder::STATUS_CONFIRMING);
            $this->logAttempt($order, $match->txHash, PaymentAttempt::ACTION_CONFIRM, PaymentAttempt::RESULT_REJECTED, "confirmaciones {$confirmations}/{$required}");
            return $order->fresh();
        }

        return $this->confirmAndDeliver($order, $match);
    }

    private function markDetected(PaymentOrder $order, $match): PaymentOrder
    {
        $affected = PaymentOrder::where('id', $order->id)
            ->where('status', PaymentOrder::STATUS_PENDING)
            ->update([
                'status' => PaymentOrder::STATUS_DETECTED,
                'detected_at' => now(),
                'received_amount' => $match->amount,
            ]);

        if ($affected > 0) {
            $this->logAttempt($order, $match->txHash, PaymentAttempt::ACTION_DETECT, PaymentAttempt::RESULT_SUCCESS, null);
            $this->broadcastUpdate($order->fresh());
        }

        return $order->fresh();
    }

    private function confirmAndDeliver(PaymentOrder $order, $match): PaymentOrder
    {
        if (bccomp((string) $match->amount, (string) $order->expected_amount, 8) < 0) {
            $this->transitionTo($order, PaymentOrder::STATUS_UNDERPAID);
            $this->logAttempt($order, $match->txHash, PaymentAttempt::ACTION_CONFIRM, PaymentAttempt::RESULT_REJECTED, 'monto insuficiente');
            return $order->fresh();
        }

        if (bccomp((string) $match->amount, (string) $order->expected_amount, 8) > 0) {
            $this->transitionTo($order, PaymentOrder::STATUS_OVERPAID);
            $this->logAttempt($order, $match->txHash, PaymentAttempt::ACTION_CONFIRM, PaymentAttempt::RESULT_REJECTED, 'monto excede lo esperado — requiere revisión manual');
            Log::channel('slack')->warning('payments.overpaid', ['payment_order_id' => $order->id, 'tx_hash' => $match->txHash]);
            return $order->fresh();
        }

        try {
            $transaction = $this->persistCryptoTransaction($order, $match);
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), 'tx_hash')) {
                // Ya persistida por otra evaluación concurrente — continuar
                // con la fila existente, nunca fallar la orden por esto.
                $transaction = CryptoTransaction::where('tx_hash', $match->txHash)->firstOrFail();
            } else {
                throw $e;
            }
        }

        $affected = PaymentOrder::where('id', $order->id)
            ->whereIn('status', [PaymentOrder::STATUS_DETECTED, PaymentOrder::STATUS_CONFIRMING])
            ->update([
                'status' => PaymentOrder::STATUS_CONFIRMED,
                'confirmed_at' => now(),
                'crypto_transaction_id' => $transaction->id,
            ]);

        if ($affected === 0) {
            return $order->fresh();
        }

        $this->logAttempt($order, $match->txHash, PaymentAttempt::ACTION_CONFIRM, PaymentAttempt::RESULT_SUCCESS, null);
        $this->broadcastUpdate($order->fresh());

        $order = $order->fresh();
        $this->purchaseService->deliver($order);
        $this->logAttempt($order, $match->txHash, PaymentAttempt::ACTION_CREDIT, PaymentAttempt::RESULT_SUCCESS, null);
        $this->broadcastUpdate($order->fresh());

        return $order->fresh();
    }

    private function persistCryptoTransaction(PaymentOrder $order, $match): CryptoTransaction
    {
        return DB::transaction(fn () => CryptoTransaction::create([
            'tx_hash' => $match->txHash,
            'network' => $order->network,
            'token' => $order->token,
            'direction' => CryptoTransaction::DIRECTION_DEPOSIT,
            'from_address' => $match->fromAddress,
            'to_address' => $match->toAddress,
            'amount' => $match->amount,
            'status' => CryptoTransaction::STATUS_CONFIRMED,
            'confirmations' => $match->confirmations,
            'user_id' => $order->user_id,
            'confirmed_at' => now(),
        ]));
    }

    private function transitionTo(PaymentOrder $order, string $status): void
    {
        if ($order->status === $status) {
            return;
        }

        $affected = PaymentOrder::where('id', $order->id)
            ->whereNotIn('status', PaymentOrder::TERMINAL_STATUSES)
            ->update(['status' => $status]);

        if ($affected > 0) {
            $this->broadcastUpdate($order->fresh());
        }
    }

    private function expireOverdueOrders(): void
    {
        PaymentOrder::active()
            ->where('expires_at', '<', now())
            ->chunkById(200, function ($orders) {
                foreach ($orders as $order) {
                    PaymentOrder::where('id', $order->id)
                        ->whereNotIn('status', PaymentOrder::TERMINAL_STATUSES)
                        ->update(['status' => PaymentOrder::STATUS_EXPIRED]);

                    $this->broadcastUpdate($order->fresh());
                }
            });
    }

    private function logAttempt(PaymentOrder $order, ?string $txHash, string $action, string $result, ?string $reason): void
    {
        PaymentAttempt::create([
            'payment_order_id' => $order->id,
            'tx_hash' => $txHash,
            'attempted_action' => $action,
            'result' => $result,
            'reason' => $reason,
        ]);
    }

    private function broadcastUpdate(PaymentOrder $order): void
    {
        try {
            event(new PaymentOrderUpdated($order));
        } catch (\Throwable $e) {
            Log::warning('payments.broadcast_failed', ['payment_order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }
}
