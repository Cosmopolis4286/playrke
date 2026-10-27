<?php

namespace App\Console\Commands;

use App\Services\Commands\CommandService;
use App\Services\Roles\Admin\User\SpecificPermissionExpiryService;
use Illuminate\Console\Command;
use Throwable;

class NotifyExpiringSpecificPermissionGrantsCommand extends Command
{
    protected $description = 'Notifica a superadmins los permisos individuales que expirarán pronto, sin revocarlos todavía';
    protected $signature = 'permissions:notify-expiring-specific-grants {--days=3 : Días de anticipación del aviso}';

    public function handle(SpecificPermissionExpiryService $service) : void
    {
        $status = 'SUCCESS';
        $days = (int) $this->option('days');

        try {
            $count = $service->notifyExpiringSoon($days);
            $output = "Se notificaron {$count} permisos individuales por expirar en los próximos {$days} días.";
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

        // Envuelto en try/catch: si logCommandExecution() fallara por
        // cualquier motivo, el error visible al operador debe ser el
        // original de la tarea, no uno de logging que lo enmascare.
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
