<?php

namespace App\Console\Commands\Marketplace;

use App\Domains\Marketplace\Escrow\Models\Escrow;
use App\Services\Commands\CommandService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Alerta (nunca auto-corrige) escrows que deberían haberse resuelto y no lo
 * hicieron: READY_TO_SETTLE/FUNDED/ASSET_LOCKED cuyo expires_at ya venció
 * sin que ningún job los haya expirado — señal de que el cron de expiración
 * (fuera de alcance de esta fase, ver EscrowService::expire()) no corrió, o
 * de un escrow atascado por una excepción no capturada en un paso intermedio.
 *
 * Mismo criterio que NotifyStaleWalletMovementsCommand: idempotente
 * (ejecutar dos veces no duplica nada, solo re-cuenta), sin dedupe de
 * notificación — se re-alerta mientras el escrow siga sin resolver.
 */
class EscrowReconciliationCommand extends Command
{
    protected $signature = 'marketplace:reconcile-escrows';

    protected $description = 'Alerta escrows vencidos sin liberar/reembolsar (posible fallo del cron de expiración)';

    public function handle(): void
    {
        $status = 'SUCCESS';

        try {
            $staleEscrows = Escrow::whereIn('status', [
                Escrow::STATUS_FUNDED,
                Escrow::STATUS_ASSET_LOCKED,
                Escrow::STATUS_READY_TO_SETTLE,
            ])
                ->where('expires_at', '<=', now())
                ->get(['id', 'status', 'expires_at', 'buyer_id', 'seller_id']);

            if ($staleEscrows->isEmpty()) {
                $output = 'Sin escrows vencidos pendientes de resolución.';
            } else {
                foreach ($staleEscrows as $escrow) {
                    Log::warning('[EscrowReconciliation] Escrow vencido sin resolver', [
                        'escrow_id'  => $escrow->id,
                        'status'     => $escrow->status,
                        'expires_at' => $escrow->expires_at?->toIso8601String(),
                    ]);
                }
                $output = "Se detectaron {$staleEscrows->count()} escrow(s) vencido(s) sin resolver — ver logs para detalle.";
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

        if ($status === 'SUCCESS' && (!isset($staleEscrows) || $staleEscrows->isEmpty())) {
            $this->info($output);
        }
    }
}
