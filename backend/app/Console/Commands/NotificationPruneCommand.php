<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Services\Commands\CommandService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Purga notificaciones in-app viejas — antes de este comando, a diferencia
 * de audits/logs (que sí tienen audit:prune/logs:prune), la tabla
 * notifications crecía sin límite. Solo purga notificaciones ya leídas: una
 * no leída con más de RETENTION_DAYS se conserva hasta que el usuario la
 * vea, en vez de desaparecer un aviso que nunca llegó a notarse.
 */
class NotificationPruneCommand extends Command
{
    protected $signature = 'notifications:prune';

    protected $description = 'Purga notificaciones in-app leídas más antiguas que 90 días';

    private const RETENTION_DAYS = 90;

    /** Mismo tamaño de lote que AuditPruneCommand — evita un DELETE masivo bloqueante. */
    private const CHUNK_SIZE = 1000;

    public function handle(): void
    {
        $status = 'SUCCESS';

        try {
            $cutoff  = now()->subDays(self::RETENTION_DAYS);
            $deleted = 0;

            Notification::where('created_at', '<', $cutoff)
                ->whereNotNull('read_at')
                ->chunkById(self::CHUNK_SIZE, function ($notifications) use (&$deleted) {
                    $ids = $notifications->pluck('id');
                    Notification::whereIn('id', $ids)->delete();
                    $deleted += $ids->count();
                });

            $output = "Se purgaron {$deleted} notificaciones leídas anteriores a {$cutoff->toDateString()}.";
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
