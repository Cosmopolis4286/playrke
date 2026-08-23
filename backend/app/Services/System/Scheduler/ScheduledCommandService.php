<?php

namespace App\Services\System\Scheduler;

use App\Models\Commands\CommandLog;
use App\Models\Commands\ScheduledCommand;
use App\Models\User;
use App\Support\Scheduling\ScheduledCommandGate;

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
        'check:expired-bingo-rooms'                    => '* * * * *',
        'bingo:start-pending-draws'                     => '* * * * *',
        'validate:transactions'                         => '*/5 * * * *',
        'bingo:retry-failed-prize-payouts'              => '*/5 * * * *',
        'bingo:retry-failed-refunds'                    => '*/5 * * * *',
        'generate:bingo-rooms'                          => '0 * * * *',
        'handle:exchange-rates'                         => '0 * * * *',
        'trivia:start-scheduled-rooms'                  => '* * * * *',
        'trivia:auto-finish-stale-games'                => '* * * * *',
        'trivia:retry-failed-prize-payouts'             => '*/5 * * * *',
        'audit:prune'                                   => '0 0 * * *',
        'logs:prune'                                    => '0 0 * * *',
        'logs:alert-threshold'                          => '*/15 * * * *',
        'permissions:expire-specific-grants'            => '*/5 * * * *',
        'permissions:notify-expiring-specific-grants'   => '0 8 * * *',
    ];

    /**
     * @return array<int, array{id: string, command: string, label: ?string, description: ?string, frequency: string, cron_expression: string, is_default_frequency: bool, min_interval_seconds: ?int, is_enabled: bool, disabled_at: ?string, disabled_by: ?string, frequency_updated_at: ?string, frequency_updated_by: ?string, last_execution: ?array}>
     */
    public function list(): array
    {
        $lastLogs = CommandLog::query()
            ->selectRaw('command, MAX(executed_at) as max_executed_at')
            ->groupBy('command')
            ->pluck('max_executed_at', 'command');

        return ScheduledCommand::query()
            ->with(['disabledBy:id,first_name,last_name,username', 'frequencyUpdatedBy:id,first_name,last_name,username'])
            ->orderBy('command')
            ->get()
            ->map(function (ScheduledCommand $scheduled) use ($lastLogs) {
                $lastExecutedAt = $lastLogs->get($scheduled->command);
                $lastLog = $lastExecutedAt
                    ? CommandLog::byCommand($scheduled->command)->orderedByExecution()->first()
                    : null;

                $effectiveCron = $scheduled->cron_expression ?? (self::DEFAULT_CRON[$scheduled->command] ?? null);

                return [
                    'id'                    => (string) $scheduled->id,
                    'command'               => $scheduled->command,
                    'label'                 => $scheduled->label,
                    'description'           => $scheduled->description,
                    'frequency'             => $effectiveCron ? self::describeCron($effectiveCron) : '—',
                    'cron_expression'       => $effectiveCron ?? '',
                    'is_default_frequency'  => $scheduled->cron_expression === null,
                    'min_interval_seconds'  => $scheduled->min_interval_seconds,
                    'is_enabled'            => $scheduled->is_enabled,
                    'disabled_at'           => $scheduled->disabled_at?->format('d/m/Y H:i:s'),
                    'disabled_by'           => $scheduled->disabledBy
                        ? trim("{$scheduled->disabledBy->first_name} {$scheduled->disabledBy->last_name}") ?: $scheduled->disabledBy->username
                        : null,
                    'frequency_updated_at'  => $scheduled->frequency_updated_at?->format('d/m/Y H:i:s'),
                    'frequency_updated_by'  => $scheduled->frequencyUpdatedBy
                        ? trim("{$scheduled->frequencyUpdatedBy->first_name} {$scheduled->frequencyUpdatedBy->last_name}") ?: $scheduled->frequencyUpdatedBy->username
                        : null,
                    'last_execution' => $lastLog ? [
                        'status'      => $lastLog->status,
                        'executed_at' => $lastLog->executed_at->format('d/m/Y H:i:s'),
                        'output'      => $lastLog->truncated_output,
                    ] : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Traduce expresiones cron comunes a texto legible; para el resto,
     * muestra la expresión cruda (sigue siendo información útil para un
     * superadmin, aunque menos amigable que un preset conocido).
     */
    public static function describeCron(string $cron): string
    {
        $presets = [
            '* * * * *'     => 'Cada minuto',
            '*/5 * * * *'   => 'Cada 5 minutos',
            '*/10 * * * *'  => 'Cada 10 minutos',
            '*/15 * * * *'  => 'Cada 15 minutos',
            '*/30 * * * *'  => 'Cada 30 minutos',
            '0 * * * *'     => 'Cada hora',
            '0 0 * * *'     => 'Diario a medianoche (UTC)',
            '0 8 * * *'     => 'Diario a las 08:00 (UTC)',
            '0 12 * * *'    => 'Diario a las 12:00 (UTC)',
        ];

        return $presets[$cron] ?? $cron;
    }

    /**
     * @return array<int, array{status: string, executed_at: string, output: ?string}>
     */
    public function history(string $command, int $limit = 50): array
    {
        return CommandLog::byCommand($command)
            ->orderedByExecution()
            ->limit($limit)
            ->get()
            ->map(fn (CommandLog $log) => [
                'status'      => $log->status,
                'executed_at' => $log->executed_at->format('d/m/Y H:i:s'),
                'output'      => $log->output,
            ])
            ->all();
    }

    public function toggle(string $id, bool $enabled, User $actor): ScheduledCommand
    {
        $scheduled = ScheduledCommand::findOrFail($id);

        $scheduled->update([
            'is_enabled'  => $enabled,
            'disabled_at' => $enabled ? null : now(),
            'disabled_by' => $enabled ? null : $actor->id,
        ]);

        ScheduledCommandGate::flushCache();

        return $scheduled->fresh();
    }

    /**
     * Encuentra el registro por id — usado por el controller para resolver
     * min_interval_seconds antes de validar, y luego reutilizado para el
     * update real (evita un segundo findOrFail).
     */
    public function find(string $id): ScheduledCommand
    {
        return ScheduledCommand::findOrFail($id);
    }

    public function updateFrequency(ScheduledCommand $scheduled, string $cronExpression, User $actor): ScheduledCommand
    {
        $scheduled->update([
            'cron_expression'       => $cronExpression,
            'frequency_updated_by'  => $actor->id,
            'frequency_updated_at'  => now(),
        ]);

        ScheduledCommandGate::flushCache();

        return $scheduled->fresh();
    }

    /**
     * Vuelve a la cadencia fija original de routes/console.php (borra el
     * override) — útil si un cambio manual causó un comportamiento
     * inesperado y el superadmin quiere el valor conocido-bueno sin tener
     * que recordar cuál era.
     */
    public function resetFrequency(string $id, User $actor): ScheduledCommand
    {
        $scheduled = ScheduledCommand::findOrFail($id);

        $scheduled->update([
            'cron_expression'      => null,
            'frequency_updated_by' => $actor->id,
            'frequency_updated_at' => now(),
        ]);

        ScheduledCommandGate::flushCache();

        return $scheduled->fresh();
    }

    public function defaultCronFor(string $command): ?string
    {
        return self::DEFAULT_CRON[$command] ?? null;
    }
}
