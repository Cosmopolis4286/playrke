<?php

namespace App\Services\Commands;

use App\Models\Commands\CommandLog;
use App\Models\User;
use App\Services\NotificationService;
use App\Traits\ResolvesSuperadminIds;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Throwable;

class CommandService
{
    use ResolvesSuperadminIds;

    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Registra la ejecución del comando en la base de datos.
     *
     * El INSERT del log es la única parte que NO puede fallar — es la única
     * llamada que la mayoría de los 15 comandos programados hacen sin
     * try/catch propio (routes/console.php los invoca vía schedule:run, sin
     * capturar nada). Todo lo "extra" (resolver el estado anterior para
     * decidir si notificar, la notificación en sí) corre en un bloque
     * separado y tolerante a fallos — si algo ahí lanza (ej. un problema
     * transitorio de conexión), el comando pierde la notificación pero NUNCA
     * el registro del log, que es lo que ya causó una vez la pérdida
     * silenciosa de un log real (incidente 2026-08-18).
     *
     * $triggeredByUserId: null para ejecuciones del scheduler automático
     * (el caso normal, los 15 comandos programados no pasan este argumento).
     * Cuando viene con valor, es porque ScheduledCommandService::runNow()
     * invocó el comando manualmente desde /system/jobs — el historial
     * (/system/jobs, ver ScheduledCommandHistoryModal) distingue origen y
     * actor a partir de estas dos columnas.
     */
    public function logCommandExecution(string $signature, string $output, string $status, ?string $triggeredByUserId = null): void
    {
        $triggeredByUserId ??= Context::get('command.triggered_by_user_id');

        CommandLog::create([
            'command'              => $signature,
            'output'               => $output,
            'status'               => $status,
            'executed_at'          => now(),
            'triggered_by'         => $triggeredByUserId ? 'manual' : 'scheduler',
            'triggered_by_user_id' => $triggeredByUserId,
        ]);

        if ($status !== 'error') {
            return;
        }

        // Log::error() aquí (no solo en command_logs) para que el error
        // también quede en system_logs vía el canal 'database'
        // (DatabaseLogHandler, ver config/logging.php) — antes solo unos
        // pocos de los 15 comandos llamaban Log::error() manualmente en
        // sub-catches internos, así que la mayoría de fallos de comando eran
        // invisibles en /logs y para logs:alert-threshold (que cuenta sobre
        // system_logs). Centralizado aquí, los 15 quedan consistentes sin
        // tener que tocar cada Command individual.
        Log::error("[{$signature}] {$output}");

        try {
            $this->notifyIfNewFailure($signature, $output);
        } catch (Throwable $e) {
            Log::warning('CommandService: no se pudo evaluar/enviar la notificación de fallo, el log ya quedó registrado.', [
                'command' => $signature,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * Solo notifica en la transición éxito/sin-historial -> error, no en
     * cada corrida mientras sigue fallando — comandos como
     * bingo:start-pending-draws corren cada minuto, y sin este filtro un bug
     * real generaría decenas de notificaciones por hora sin aportar nada
     * nuevo tras la primera.
     */
    private function notifyIfNewFailure(string $signature, string $output): void
    {
        // orderedByExecution() (por executed_at) no alcanza como único
        // criterio: comandos que corren varias veces dentro del mismo
        // segundo generan empates. Se desempata por id (Snowflake, monótono
        // creciente) para garantizar "el log realmente más reciente", y se
        // excluye el que se acaba de insertar (más los que compartan su
        // mismo timestamp) para comparar contra el estado ANTERIOR real.
        $previousStatus = CommandLog::byCommand($signature)
            ->orderedByExecution()
            ->orderBy('id', 'desc')
            ->skip(1)
            ->value('status');

        if ($previousStatus === 'error') {
            return;
        }

        $this->notifyFailure($signature, $output);
    }

    private function notifyFailure(string $signature, string $output): void
    {
        $firstLine = mb_strimwidth(strtok($output, "\n") ?: $output, 0, 200, '…');

        foreach ($this->superadminIds() as $superadminId) {
            $this->notificationService->create(
                userId: $superadminId,
                type: 'SYSTEM',
                title: '⚠️ Comando programado falló',
                message: "{$signature}: {$firstLine}",
                data: [
                    'command' => $signature,
                    'url'     => '/system/jobs',
                ]
            );
        }
    }

}
