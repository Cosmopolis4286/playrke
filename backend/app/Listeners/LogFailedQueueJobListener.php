<?php

namespace App\Listeners;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;

/**
 * Puente entre jobs de cola fallidos (failed_jobs, gestionados desde
 * /system/jobs) y system_logs (/logs) — antes eran sistemas completamente
 * separados: un job de cola que agotaba sus reintentos automáticos quedaba
 * SOLO en failed_jobs, invisible en /logs y para logs:alert-threshold (que
 * cuenta sobre system_logs), a diferencia de los scheduled commands, que ya
 * llegan a /logs vía Log::error() en App\Services\Commands\CommandService.
 *
 * Auto-descubierto por Laravel (handle() tipado con el evento, sin registro
 * manual) — mismo mecanismo que App\Listeners\ChangeLogPermissionsListener.
 */
class LogFailedQueueJobListener
{
    public function handle(JobFailed $event) : void
    {
        Log::error(sprintf(
            '[queue:%s] %s: %s',
            $event->connectionName,
            $event->job->resolveName(),
            $event->exception->getMessage(),
        ));
    }
}
