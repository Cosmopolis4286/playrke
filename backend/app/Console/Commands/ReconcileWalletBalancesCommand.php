<?php

namespace App\Console\Commands;

use App\Services\Commands\CommandService;
use App\Domains\Wallet\Services\WalletBalanceReconciliationService;
use Illuminate\Console\Command;
use Throwable;

class ReconcileWalletBalancesCommand extends Command
{
    protected $signature = 'wallet:reconcile-balances';

    protected $description = 'Alerta wallets cuyo balance no coincide con el balance_after de su último movimiento (drift entre balance y el ledger)';

    public function handle(WalletBalanceReconciliationService $service): void
    {
        $status = 'SUCCESS';

        try {
            $count = $service->reconcileAndAlert();
            $output = $count === 0
                ? 'Sin drift detectado entre wallets.balance y el ledger.'
                : "Se detectaron {$count} wallet(s) con drift de balance — ver logs/notificación para detalle.";

            if ($count > 0) {
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

        if ($status === 'SUCCESS' && (!isset($count) || $count === 0)) {
            $this->info($output);
        }
    }
}
