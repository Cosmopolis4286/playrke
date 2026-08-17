<?php

namespace App\Console\Commands;

use App\Services\Commands\CommandService;
use App\Services\Roles\Admin\User\SpecificPermissionExpiryService;
use Illuminate\Console\Command;
use Throwable;

class ExpireSpecificPermissionGrantsCommand extends Command
{
    protected $signature = 'permissions:expire-specific-grants';

    protected $description = 'Revoca permisos individuales de usuario cuyo expires_at ya venció, y notifica (in-app a superadmins + email al afectado)';

    public function handle(SpecificPermissionExpiryService $service): void
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
                $e->getLine()
            );
            $this->error($output);
        }

        app(CommandService::class)->logCommandExecution($this->signature, $output, $status);

        if ($status === 'SUCCESS') {
            $this->info($output);
        }
    }
}
