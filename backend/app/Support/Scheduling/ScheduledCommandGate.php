<?php

namespace App\Support\Scheduling;

use App\Models\Commands\ScheduledCommand;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Consultado desde routes/console.php (->when(fn () => ScheduledCommandGate::isEnabled('...')))
 * para que el superadmin pueda pausar un comando programado sin tocar
 * código. Métodos estáticos (no Service inyectado) porque console.php corre
 * en el bootstrap de la aplicación, fuera del ciclo normal de request/container.
 *
 * Fail-open por diseño: si la tabla no existe todavía (entre migrate y
 * seed) o la BD está caída, el scheduler debe seguir corriendo los jobs de
 * negocio (bingo, trivia, wallet) — un problema transitorio de esta feature
 * de gestión nunca debe silenciar el resto del sistema.
 */
final class ScheduledCommandGate
{
    private const CACHE_KEY = 'scheduled_commands.flags';
    private const CACHE_TTL = 60;
    private const CRON_CACHE_KEY = 'scheduled_commands.cron';

    /**
     * Cron expression editada desde la UI para este comando, o null si el
     * comando no tiene override (routes/console.php debe usar su método fijo
     * original, ej. ->everyMinute(), como fallback). Fail-open igual que
     * isEnabled(): un error aquí nunca debe tumbar el schedule completo.
     */
    public static function cronFor(string $command) : ?string
    {
        try {
            $overrides = Cache::remember(
                self::CRON_CACHE_KEY,
                self::CACHE_TTL,
                fn () => ScheduledCommand::query()->whereNotNull('cron_expression')->pluck('cron_expression', 'command'),
            );

            return $overrides[$command] ?? null;
        } catch (Throwable $e) {
            Log::warning('ScheduledCommandGate: no se pudo resolver cron_expression, se usa el default de código.', [
                'command' => $command,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Invalidar el cache tras un toggle/edición desde la UI — el próximo
     * tick del scheduler (máximo 1 minuto, por cómo corre schedule:run)
     * refleja el cambio sin esperar el TTL completo.
     */
    public static function flushCache() : void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::CRON_CACHE_KEY);
    }

    public static function isEnabled(string $command) : bool
    {
        try {
            $flags = Cache::remember(
                self::CACHE_KEY,
                self::CACHE_TTL,
                fn () => ScheduledCommand::query()->pluck('is_enabled', 'command'),
            );

            // Comando sin registro en BD (agregado en código pero el seeder
            // aún no corrió) — default activo, mismo principio fail-open.
            return (bool) ($flags[$command] ?? true);
        } catch (Throwable $e) {
            Log::warning('ScheduledCommandGate: no se pudo resolver el estado, se asume activo.', [
                'command' => $command,
                'error' => $e->getMessage(),
            ]);

            return true;
        }
    }
}
