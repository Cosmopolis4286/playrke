<?php

namespace App\Services\System\Scheduler;

use App\Models\Audit;
use App\Models\Commands\CommandLog;
use App\Models\Commands\ScheduledCommand;
use App\Models\User;
use App\Support\Scheduling\ScheduledCommandGate;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;

/**
 * Gestión de comandos programados desde el panel "Jobs y Scheduler" —
 * expone toggle on/off (persistido, consultado por ScheduledCommandGate
 * desde routes/console.php) y el cruce con el último CommandLog de cada
 * comando para mostrar su historial reciente en la UI.
 */
final class ScheduledCommandService
{
    /**
     * Cron expression por defecto de cada comando — refleja el ->everyMinute(),
     * ->hourly(), etc. fijo que ya vivía en routes/console.php. Se usa como
     * fallback de presentación cuando scheduled_commands.cron_expression es
     * NULL (comando nunca editado desde la UI) y como semilla al hacer
     * "restablecer al valor por defecto".
     *
     * @var array<string, string>
     */
    private const DEFAULT_CRON = [
        'check:expired-bingo-rooms' => '* * * * *',
        'bingo:start-pending-draws' => '* * * * *',
        'bingo:handle-pending-payments-at-start' => '* * * * *',
        'validate:transactions' => '*/5 * * * *',
        'bingo:retry-failed-prize-payouts' => '*/5 * * * *',
        'bingo:retry-failed-refunds' => '*/5 * * * *',
        'generate:bingo-rooms' => '0 * * * *',
        'handle:exchange-rates' => '0 * * * *',
        'exchange-rates:alerts' => '*/15 * * * *',
        'trivia:start-scheduled-rooms' => '* * * * *',
        'trivia:auto-finish-stale-games' => '* * * * *',
        'trivia:retry-failed-prize-payouts' => '*/5 * * * *',
        'audit:prune' => '0 0 * * *',
        'logs:prune' => '0 0 * * *',
        'logs:alert-threshold' => '*/15 * * * *',
        'permissions:expire-specific-grants' => '*/5 * * * *',
        'permissions:notify-expiring-specific-grants' => '0 8 * * *',
        'suspensions:expire-temporary' => '*/5 * * * *',
        'trivia:cancel-stale-rooms' => '*/5 * * * *',
        'kyc:notify-pending' => '0 8 * * *',
        'wallet:notify-stale-movements' => '*/15 * * * *',
        'notifications:prune' => '0 0 * * *',
    ];

    /**
     * Traduce expresiones cron comunes a texto legible; para el resto,
     * muestra la expresión cruda (sigue siendo información útil para un
     * superadmin, aunque menos amigable que un preset conocido).
     */
    public static function describeCron(string $cron) : string
    {
        $presets = [
            '* * * * *' => 'Cada minuto',
            '*/5 * * * *' => 'Cada 5 minutos',
            '*/10 * * * *' => 'Cada 10 minutos',
            '*/15 * * * *' => 'Cada 15 minutos',
            '*/30 * * * *' => 'Cada 30 minutos',
            '0 * * * *' => 'Cada hora',
            '0 0 * * *' => 'Diario a medianoche (UTC)',
            '0 8 * * *' => 'Diario a las 08:00 (UTC)',
            '0 12 * * *' => 'Diario a las 12:00 (UTC)',
        ];

        return $presets[$cron] ?? $cron;
    }

    public function defaultCronFor(string $command) : ?string
    {
        return self::DEFAULT_CRON[$command] ?? null;
    }

    /**
     * Encuentra el registro por id — usado por el controller para resolver
     * min_interval_seconds antes de validar, y luego reutilizado para el
     * update real (evita un segundo findOrFail).
     */
    public function find(string $id) : ScheduledCommand
    {
        return ScheduledCommand::findOrFail($id);
    }

    /**
     * Historial unificado de un comando: ejecuciones (CommandLog, con origen
     * scheduler/manual y el actor cuando aplica) + cambios de estado
     * (Audit — toggle activar/desactivar, cambio/reset de frecuencia),
     * intercalados y ordenados por fecha descendente. Antes solo mostraba
     * ejecuciones; "quién activó/desactivó esto y cuándo" solo vivía en las
     * columnas simples disabled_by/disabled_at del propio ScheduledCommand
     * (sobrescribibles, sin histórico), invisible desde este modal.
     *
     * @return array<int, array<string, mixed>>
     */
    public function history(string $command, int $limit = 50) : array
    {
        $executions = CommandLog::byCommand($command)
            ->orderedByExecution()
            ->limit($limit)
            ->get()
            ->map(fn (CommandLog $log) => [
                'type' => 'execution',
                'occurred_at' => $log->executed_at->toIso8601String(),
                'server_timezone' => config('app.timezone'),
                'status' => $log->status,
                'output' => $log->output,
                'triggered_by' => $log->triggered_by,
                'actor' => $log->triggeredByUser
                    ? trim("{$log->triggeredByUser->first_name} {$log->triggeredByUser->last_name}") ?: $log->triggeredByUser->username
                    : null,
            ]);

        $scheduled = ScheduledCommand::query()->byCommand($command)->first();
        $stateChanges = $scheduled
            ? Audit::query()
                ->ofAuditable(ScheduledCommand::class, $scheduled->id)
                ->with('user:id,first_name,last_name,username')
                ->latest('id')
                ->limit($limit)
                ->get()
                ->map(fn (Audit $audit) => [
                    'type' => $this->describeAuditEvent($audit),
                    'occurred_at' => $audit->created_at->toIso8601String(),
                    'server_timezone' => config('app.timezone'),
                    'actor' => $audit->user
                        ? trim("{$audit->user->first_name} {$audit->user->last_name}") ?: $audit->user->username
                        : null,
                    'detail' => $this->describeAuditDetail($audit),
                ])
            : collect();

        return $executions
            ->concat($stateChanges)
            ->sortByDesc('occurred_at')
            ->values()
            ->take($limit)
            ->all();
    }

    /**
     * @return array<int, array{id: string, command: string, label: ?string, description: ?string, frequency: string, cron_expression: string, is_default_frequency: bool, min_interval_seconds: ?int, is_enabled: bool, disabled_at: ?string, disabled_by: ?string, frequency_updated_at: ?string, frequency_updated_by: ?string, last_execution: ?array, server_timezone: string}>
     */
    public function list() : array
    {
        $lastLogs = CommandLog::query()
            ->selectRaw('command, MAX(executed_at) as max_executed_at')
            ->groupBy('command')
            ->pluck('max_executed_at', 'command');

        return ScheduledCommand::query()
            ->with(['disabledBy:id,first_name,last_name,username', 'frequencyUpdatedBy:id,first_name,last_name,username'])
            ->orderBy('command')
            ->get()
            ->map(function(ScheduledCommand $scheduled) use ($lastLogs) {
                $lastExecutedAt = $lastLogs->get($scheduled->command);
                $lastLog = $lastExecutedAt
                    ? CommandLog::byCommand($scheduled->command)->orderedByExecution()->first()
                    : null;

                $effectiveCron = $scheduled->cron_expression ?? (self::DEFAULT_CRON[$scheduled->command] ?? null);

                return [
                    'id' => (string) $scheduled->id,
                    'command' => $scheduled->command,
                    'label' => $scheduled->label,
                    'description' => $scheduled->description,
                    'frequency' => $effectiveCron ? self::describeCron($effectiveCron) : '—',
                    'cron_expression' => $effectiveCron ?? '',
                    'is_default_frequency' => $scheduled->cron_expression === null,
                    'min_interval_seconds' => $scheduled->min_interval_seconds,
                    'is_enabled' => $scheduled->is_enabled,
                    // ISO 8601 con offset (no un string ya formateado en la
                    // zona del servidor) — el frontend lo convierte a la zona
                    // horaria local del navegador, ver useLocalDateTime.ts.
                    'disabled_at' => $scheduled->disabled_at?->toIso8601String(),
                    'disabled_by' => $scheduled->disabledBy
                        ? trim("{$scheduled->disabledBy->first_name} {$scheduled->disabledBy->last_name}") ?: $scheduled->disabledBy->username
                        : null,
                    'frequency_updated_at' => $scheduled->frequency_updated_at?->toIso8601String(),
                    'frequency_updated_by' => $scheduled->frequencyUpdatedBy
                        ? trim("{$scheduled->frequencyUpdatedBy->first_name} {$scheduled->frequencyUpdatedBy->last_name}") ?: $scheduled->frequencyUpdatedBy->username
                        : null,
                    'last_execution' => $lastLog ? [
                        'status' => $lastLog->status,
                        'executed_at' => $lastLog->executed_at->toIso8601String(),
                        'output' => $lastLog->truncated_output,
                    ] : null,
                    'server_timezone' => config('app.timezone'),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Vuelve a la cadencia fija original de routes/console.php (borra el
     * override) — útil si un cambio manual causó un comportamiento
     * inesperado y el superadmin quiere el valor conocido-bueno sin tener
     * que recordar cuál era.
     */
    public function resetFrequency(string $id, User $actor) : ScheduledCommand
    {
        $scheduled = ScheduledCommand::findOrFail($id);

        $scheduled->update([
            'cron_expression' => null,
            'frequency_updated_by' => $actor->id,
            'frequency_updated_at' => now(),
        ]);

        ScheduledCommandGate::flushCache();

        return $scheduled->fresh();
    }

    /**
     * Ejecuta AHORA un comando ya catalogado en scheduled_commands (nunca
     * texto libre del cliente — $id resuelve a un ScheduledCommand existente,
     * y es su columna `command` la que se pasa a Artisan::call(), no un valor
     * enviado directamente por el request). El comando individual (su propio
     * handle()) ya se encarga de registrar el CommandLog y, si falla, de
     * Log::error() hacia system_logs — ver App\Services\Commands\CommandService.
     *
     * Context::add('command.triggered_by_user_id', ...) es cómo este actor
     * llega hasta CommandService::logCommandExecution() dentro del handle()
     * del comando invocado por Artisan::call() — ninguno de los 15 comandos
     * programados conoce ni necesita conocer que puede ejecutarse
     * manualmente; logCommandExecution() lee este valor como fallback solo
     * si no se le pasa explícito. Context es scoped al proceso/request
     * actual, así que no hay riesgo de que el valor "se filtre" a una
     * ejecución posterior no relacionada.
     *
     * No se registra un Audit adicional aquí: el propio CommandLog que el
     * comando crea en su handle() ya queda con triggered_by='manual' +
     * triggered_by_user_id=$actor->id (vía el Context seteado abajo), que es
     * exactamente "quién disparó esto y cuándo" — duplicarlo como Audit solo
     * generaba una segunda entrada idéntica en el historial unificado
     * (history()) sin aportar nada nuevo.
     *
     * @return array{exit_code: int, output: string}
     */
    public function runNow(string $id, User $actor) : array
    {
        $scheduled = ScheduledCommand::findOrFail($id);

        Context::add('command.triggered_by_user_id', (string) $actor->id);

        try {
            $exitCode = Artisan::call($scheduled->command);
        } finally {
            Context::forget('command.triggered_by_user_id');
        }
        $output = Artisan::output();

        // Ejecución manual disparada por un admin fuera del horario programado
        // — visibilidad explícita en /logs sea cual sea el resultado, no solo
        // cuando falla (a diferencia del log automático del propio comando).
        Log::info("[run-now] {$scheduled->command} ejecutado manualmente por {$actor->id} (exit={$exitCode})");

        return ['exit_code' => $exitCode, 'output' => $output];
    }

    public function toggle(string $id, bool $enabled, User $actor) : ScheduledCommand
    {
        $scheduled = ScheduledCommand::findOrFail($id);

        $scheduled->update([
            'is_enabled' => $enabled,
            'disabled_at' => $enabled ? null : now(),
            'disabled_by' => $enabled ? null : $actor->id,
        ]);

        ScheduledCommandGate::flushCache();

        return $scheduled->fresh();
    }

    public function updateFrequency(ScheduledCommand $scheduled, string $cronExpression, User $actor) : ScheduledCommand
    {
        $scheduled->update([
            'cron_expression' => $cronExpression,
            'frequency_updated_by' => $actor->id,
            'frequency_updated_at' => now(),
        ]);

        ScheduledCommandGate::flushCache();

        return $scheduled->fresh();
    }

    private function describeAuditDetail(Audit $audit) : string
    {
        $new = $audit->new_values ?? [];
        $old = $audit->old_values ?? [];

        if (array_key_exists('is_enabled', $new)) {
            return $new['is_enabled'] ? 'Comando activado' : 'Comando desactivado';
        }

        if (array_key_exists('cron_expression', $new)) {
            return $new['cron_expression'] === null
                ? 'Frecuencia restablecida al valor por defecto'
                : sprintf('Frecuencia cambiada de "%s" a "%s"', $old['cron_expression'] ?? '—', $new['cron_expression']);
        }

        return 'Cambio de configuración';
    }

    /**
     * Clasifica un Audit('updated') de ScheduledCommand según qué campos
     * realmente cambiaron — el trait Auditable no distingue esto por sí
     * solo, siempre registra el evento genérico 'updated'.
     */
    private function describeAuditEvent(Audit $audit) : string
    {
        $changed = array_keys($audit->new_values ?? []);

        if (in_array('is_enabled', $changed, true)) {
            return 'toggle';
        }

        if (in_array('cron_expression', $changed, true)) {
            return 'frequency_change';
        }

        return 'other_change';
    }
}
