<?php

namespace App\Console\Commands;

use App\Services\Commands\CommandService;
use App\Services\Roles\Admin\User\SpecificPermissionExpiryService;
use Illuminate\Console\Command;
use Throwable;

class ExpireSpecificPermissionGrantsCommand extends Command
{
    protected $description = 'Revoca permisos individuales de usuario cuyo expires_at ya venció, y notifica (in-app a superadmins + email al afectado)';
    protected $signature = 'permissions:expire-specific-grants';

    public function handle(SpecificPermissionExpiryService $service) : void
    {
        $status = 'SUCCESS';

        try {
            $count = $service->expireDueGrants();
            $output = "Se expiraron {$count} permisos individuales vencidos.";
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
