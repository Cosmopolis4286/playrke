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
     * Frecuencia legible por comando — vive aquí (no en BD) porque es
     * metadata de presentación derivada 1:1 de routes/console.php; si se
     * agrega/cambia un comando ahí, se actualiza también este mapa.
     *
     * @var array<string, string>
     */
    private const FREQUENCY_LABELS = [
        'check:expired-bingo-rooms'                    => 'Cada minuto',
        'bingo:start-pending-draws'                     => 'Cada minuto',
        'validate:transactions'                         => 'Cada 5 minutos',
        'bingo:retry-failed-prize-payouts'              => 'Cada 5 minutos',
        'bingo:retry-failed-refunds'                    => 'Cada 5 minutos',
        'generate:bingo-rooms'                          => 'Cada hora',
        'handle:exchange-rates'                         => 'Cada hora',
        'trivia:start-scheduled-rooms'                  => 'Cada minuto',
        'trivia:auto-finish-stale-games'                => 'Cada minuto',
        'trivia:retry-failed-prize-payouts'             => 'Cada 5 minutos',
        'audit:prune'                                   => 'Diario',
        'logs:prune'                                    => 'Diario',
        'logs:alert-threshold'                          => 'Cada 15 minutos',
        'permissions:expire-specific-grants'            => 'Cada 5 minutos',
        'permissions:notify-expiring-specific-grants'   => 'Diario a las 08:00 UTC',
    ];

    /**
     * @return array<int, array{id: string, command: string, label: ?string, description: ?string, frequency: string, is_enabled: bool, disabled_at: ?string, disabled_by: ?string, last_execution: ?array}>
     */
    public function list(): array
    {
        $lastLogs = CommandLog::query()
            ->selectRaw('command, MAX(executed_at) as max_executed_at')
            ->groupBy('command')
            ->pluck('max_executed_at', 'command');

        return ScheduledCommand::query()
            ->with('disabledBy:id,first_name,last_name,username')
            ->orderBy('command')
            ->get()
            ->map(function (ScheduledCommand $scheduled) use ($lastLogs) {
                $lastExecutedAt = $lastLogs->get($scheduled->command);
                $lastLog = $lastExecutedAt
                    ? CommandLog::byCommand($scheduled->command)->orderedByExecution()->first()
                    : null;

                return [
                    'id'             => (string) $scheduled->id,
                    'command'        => $scheduled->command,
                    'label'          => $scheduled->label,
                    'description'    => $scheduled->description,
                    'frequency'      => self::FREQUENCY_LABELS[$scheduled->command] ?? '—',
                    'is_enabled'     => $scheduled->is_enabled,
                    'disabled_at'    => $scheduled->disabled_at?->format('d/m/Y H:i:s'),
                    'disabled_by'    => $scheduled->disabledBy
                        ? trim("{$scheduled->disabledBy->first_name} {$scheduled->disabledBy->last_name}") ?: $scheduled->disabledBy->username
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
}
