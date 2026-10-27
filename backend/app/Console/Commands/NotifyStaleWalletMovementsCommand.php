<?php

namespace App\Console\Commands;

use App\Domains\Wallet\Services\StaleWalletMovementAlertService;
use App\Services\Commands\CommandService;
use Illuminate\Console\Command;
use Throwable;

class NotifyStaleWalletMovementsCommand extends Command
{
    protected $description = 'Notifica a tesorería los depósitos/retiros pendientes de aprobación con antigüedad excesiva';
    protected $signature = 'wallet:notify-stale-movements';

    public function handle(StaleWalletMovementAlertService $service) : void
    {
        $status = 'SUCCESS';

        try {
            $staleCount = $service->notifyStalePending();
            $criticalCount = $service->notifyCriticalPending();
            $output = "Se notificaron {$staleCount} movimiento(s) atrasado(s) y {$criticalCount} crítico(s).";
        } catch (Throwable $e) {
            $status = 'ERROR';
            $output = sprintf(
                'Error al ejecutar la tarea: %s en archivo %s línea %d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
            );
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
