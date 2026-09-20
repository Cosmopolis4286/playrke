<?php

namespace App\Console\Commands;

use App\Domains\Wallet\Services\WalletMovementsReportService;
use App\Services\Commands\CommandService;
use Illuminate\Console\Command;
use Throwable;

class SendWalletMovementsSummaryCommand extends Command
{
    protected $signature = 'wallet:send-movements-summary';

    protected $description = 'Envía el resumen semanal de movimientos de billetera por email a los admins con permiso de gestión';

    public function handle(WalletMovementsReportService $service): void
    {
        $status = 'SUCCESS';

        try {
            $count = $service->sendWeeklySummary();
            $output = "Resumen semanal enviado a {$count} destinatario(s).";
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

        if ($status === 'SUCCESS') {
            $this->info($output);
        }
    }
}
