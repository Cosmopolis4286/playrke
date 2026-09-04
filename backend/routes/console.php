<?php

use App\Support\Scheduling\ScheduledCommandGate;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Facades\Schedule;

/**
 * Registra un comando programado aplicando, en orden de prioridad:
 *   1. cron_expression editado desde el panel Jobs/Scheduler (BD), si existe.
 *   2. $fallback (método fijo original: ->everyMinute(), ->hourly(), etc.).
 *
 * $fallback recibe el Event y debe devolverlo, para poder encadenar el
 * método de frecuencia igual que antes cuando no hay override.
 *
 * function_exists() evita "Cannot redeclare function schedule_dynamic()":
 * `php artisan test` puede bootstrapear el kernel de consola más de una vez
 * en el mismo proceso PHP (confirmado reproducible con test-docker.sh,
 * incluso en suites sin relación a este archivo) — sin la guardia, ese
 * segundo require de routes/console.php mata la suite completa con un
 * fatal error antes de imprimir un solo resultado.
 */
if (!function_exists('schedule_dynamic')) {
    function schedule_dynamic(string $command, callable $fallback): Event
    {
        $event = Schedule::command($command)
            ->when(fn () => ScheduledCommandGate::isEnabled($command))
            ->timezone('UTC')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/schedule.log'));

        $cron = ScheduledCommandGate::cronFor($command);

        return $cron ? $event->cron($cron) : $fallback($event);
    }
}

schedule_dynamic('check:expired-bingo-rooms', fn (Event $e) => $e->everyMinute());
schedule_dynamic('bingo:start-pending-draws', fn (Event $e) => $e->everyMinute());
schedule_dynamic('validate:transactions', fn (Event $e) => $e->everyFiveMinutes());
schedule_dynamic('bingo:retry-failed-prize-payouts', fn (Event $e) => $e->everyFiveMinutes());
schedule_dynamic('bingo:retry-failed-refunds', fn (Event $e) => $e->everyFiveMinutes());
schedule_dynamic('generate:bingo-rooms', fn (Event $e) => $e->hourly());
schedule_dynamic('handle:exchange-rates', fn (Event $e) => $e->hourly());
schedule_dynamic('trivia:start-scheduled-rooms', fn (Event $e) => $e->everyMinute());
schedule_dynamic('trivia:auto-finish-stale-games', fn (Event $e) => $e->everyMinute());
schedule_dynamic('trivia:retry-failed-prize-payouts', fn (Event $e) => $e->everyFiveMinutes());
schedule_dynamic('audit:prune', fn (Event $e) => $e->daily());
schedule_dynamic('logs:prune', fn (Event $e) => $e->daily());
schedule_dynamic('logs:alert-threshold', fn (Event $e) => $e->everyFifteenMinutes());
schedule_dynamic('permissions:expire-specific-grants', fn (Event $e) => $e->everyFiveMinutes());
schedule_dynamic('permissions:notify-expiring-specific-grants', fn (Event $e) => $e->dailyAt('08:00'));
schedule_dynamic('suspensions:expire-temporary', fn (Event $e) => $e->everyFiveMinutes());
schedule_dynamic('trivia:cancel-stale-rooms', fn (Event $e) => $e->everyFiveMinutes());
schedule_dynamic('kyc:notify-pending', fn (Event $e) => $e->dailyAt('08:00'));
schedule_dynamic('wallet:notify-stale-movements', fn (Event $e) => $e->everyFifteenMinutes());
schedule_dynamic('notifications:prune', fn (Event $e) => $e->daily());
schedule_dynamic('marketplace:reconcile-escrows', fn (Event $e) => $e->everyFiveMinutes());
schedule_dynamic('marketplace:reconcile', fn (Event $e) => $e->everyFifteenMinutes());
schedule_dynamic('marketplace:reconcile-crypto-deposits', fn (Event $e) => $e->everyFifteenMinutes());
schedule_dynamic('payments:watch-orders', fn (Event $e) => $e->everyMinute());
