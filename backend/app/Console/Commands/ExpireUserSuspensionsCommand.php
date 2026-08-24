<?php

namespace App\Console\Commands;

use App\Services\AntiCheat\UserSuspensionExpiryService;
use App\Services\Commands\CommandService;
use Illuminate\Console\Command;
use Throwable;

class ExpireUserSuspensionsCommand extends Command
{
    protected $signature = 'suspensions:expire-temporary';

    protected $description = 'Reactiva usuarios cuya suspensión temporal (UserSuspension.expires_at) ya venció, sin esperar acción manual de un admin';

    public function handle(UserSuspensionExpiryService $service): void
    {
        $status = 'SUCCESS';

        try {
            $count = $service->expireDueSuspensions();
            $output = "Se reactivaron {$count} usuarios con suspensión temporal vencida.";
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
