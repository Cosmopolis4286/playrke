<?php

namespace App\Console\Commands;

use App\Models\SystemLog;
use App\Services\Audit\SystemLogRetentionSettings;
use App\Services\Commands\CommandService;
use Illuminate\Console\Command;
use Throwable;

class LogPruneCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:prune';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purgar registros de system_logs más antiguos que el retention configurado (system_log_retention_days)';

    /**
     * Tamaño de lote de borrado — evita un DELETE masivo que bloquee la
     * tabla `system_logs` mientras el sistema sigue escribiendo logs.
     */
    private const CHUNK_SIZE = 1000;

    public function handle(): void
    {
        $status = 'SUCCESS';

        try {
            $retentionDays = SystemLogRetentionSettings::retentionDays();
            $cutoff = now()->subDays($retentionDays);
            $deleted = 0;

            SystemLog::where('created_at', '<', $cutoff)
                ->chunkById(self::CHUNK_SIZE, function ($logs) use (&$deleted) {
                    $ids = $logs->pluck('id');
                    SystemLog::whereIn('id', $ids)->delete();
                    $deleted += $ids->count();
                });

            $output = "Se purgaron {$deleted} registros de logs anteriores a {$cutoff->toDateString()} (retención: {$retentionDays} días).";
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
