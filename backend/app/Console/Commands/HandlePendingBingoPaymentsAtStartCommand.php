<?php

namespace App\Console\Commands;

use App\Domains\Games\Bingo\Services\Payment\BingoPendingPaymentStartService;
use App\Services\Commands\CommandService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Pagos de cartón pendientes al iniciar el sorteo: aviso previo a la
 * comercializadora y, al iniciar, rechazo con reembolso o se mantienen en
 * revisión según commerces.pending_payment_policy.
 * Ver BingoPendingPaymentStartService.
 */
class HandlePendingBingoPaymentsAtStartCommand extends Command
{
    protected $signature   = 'bingo:handle-pending-payments-at-start';
    protected $description = 'Avisa y resuelve los pagos de cartones que siguen pendientes al iniciar el sorteo';

    public function handle(BingoPendingPaymentStartService $service): void
    {
        $status = 'SUCCESS';

        try {
            $r = $service->run();
            $output = "Completado. Avisados: {$r['alerted']}. Rechazados con reembolso: {$r['rejected']}. "
                . "En revisión: {$r['kept']}. Sin poder rechazar: {$r['failed']}.";
        } catch (Throwable $e) {
            $status = 'ERROR';
            $output = sprintf('Error general: %s en %s:%d', $e->getMessage(), $e->getFile(), $e->getLine());
            $this->error($output);
        }

        try {
            app(CommandService::class)->logCommandExecution($this->signature, $output, $status);
        } catch (Throwable $logEx) {
            $this->error('No se pudo registrar la ejecución del comando: ' . $logEx->getMessage());
        }

        if ($status === 'SUCCESS') {
            $this->info($output);
        }
    }
}
