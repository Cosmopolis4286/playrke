<?php

namespace App\Console\Commands;

use App\Services\Commands\CommandService;
use App\Services\Kyc\KycPendingAlertService;
use Illuminate\Console\Command;
use Throwable;

class NotifyPendingKycCommand extends Command
{
    protected $signature = 'kyc:notify-pending';

    protected $description = 'Notifica a superadmins los documentos KYC pendientes con antigüedad (48h+) sin revisar';

    public function handle(KycPendingAlertService $service): void
    {
        $status = 'SUCCESS';

        try {
            $count = $service->notifyStalePending();
            $output = "Se notificaron {$count} documento(s) KYC pendientes atrasados.";
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
