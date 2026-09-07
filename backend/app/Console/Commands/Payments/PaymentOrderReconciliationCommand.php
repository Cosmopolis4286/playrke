<?php

namespace App\Console\Commands\Payments;

use App\Domains\Payments\Models\PaymentOrder;
use App\Services\Commands\CommandService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Alerta (nunca auto-corrige) PaymentOrder en DETECTED/CONFIRMING que llevan
 * demasiado tiempo sin avanzar — señal de que TronGrid está caído, rate
 * limiteando, o de un bug en BlockchainWatcherService. Mismo patrón que
 * CryptoDepositReconciliationCommand/EscrowReconciliationCommand.
 *
 * Sin esto, una orden puede quedar colgada en DETECTED/CONFIRMING sin que
 * nadie se entere: el usuario ya envió el pago y ve el estado "confirmando"
 * indefinidamente, mientras payments:watch-orders sigue reintentando cada
 * minuto sin éxito silencioso.
 */
class PaymentOrderReconciliationCommand extends Command
{
    protected $signature = 'payments:reconcile-orders';

    protected $description = 'Alerta órdenes de pago USDT-TRC20 detectadas/confirmando sin avanzar hace demasiado tiempo';

    private const STALE_MINUTES = 15;

    public function handle(): void
    {
        $status = 'SUCCESS';

        try {
            $stale = PaymentOrder::whereIn('status', [PaymentOrder::STATUS_DETECTED, PaymentOrder::STATUS_CONFIRMING])
                ->where('updated_at', '<=', now()->subMinutes(self::STALE_MINUTES))
                ->get(['id', 'status', 'user_id', 'deposit_address', 'expected_amount', 'received_amount', 'updated_at']);

            if ($stale->isEmpty()) {
                $output = 'Sin órdenes de pago detectadas/confirmando estancadas.';
            } else {
                foreach ($stale as $order) {
                    Log::warning('[PaymentOrderReconciliation] Orden estancada sin avanzar', [
                        'payment_order_id' => $order->id,
                        'status'           => $order->status,
                        'user_id'          => $order->user_id,
                        'deposit_address'  => $order->deposit_address,
                        'expected_amount'  => $order->expected_amount,
                        'received_amount'  => $order->received_amount,
                        'updated_at'       => $order->updated_at?->toIso8601String(),
                    ]);
                }
                $output = "Se detectaron {$stale->count()} orden(es) de pago estancadas — ver logs para detalle.";
                $this->warn($output);
            }
        } catch (Throwable $e) {
            $status = 'ERROR';
            $output = sprintf(
                'Error al ejecutar la tarea: %s en archivo %s línea %d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );
            $this->error($output);
        }

        try {
            app(CommandService::class)->logCommandExecution($this->signature, $output, $status);
        } catch (Throwable $logEx) {
            $this->error('No se pudo registrar la ejecución del comando: ' . $logEx->getMessage());
        }

        if ($status === 'SUCCESS' && (!isset($stale) || $stale->isEmpty())) {
            $this->info($output);
        }
    }
}
