<?php

namespace App\Logging;

use App\Models\SystemLog;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Throwable;

/**
 * Handler de Monolog que persiste errores/warnings en la tabla
 * `system_logs` (ver migración 2024_01_01_000724). Solo se activan niveles
 * Warning en adelante — Debug/Info/Notice quedan solo en archivo/consola
 * para no saturar la tabla con ruido de bajo valor.
 *
 * Fire-and-forget: un fallo al escribir en BD nunca debe romper el logging
 * real de la aplicación (ej. conexión caída durante el propio manejo de un
 * error 500).
 */
class DatabaseLogHandler extends AbstractProcessingHandler
{
    public function __construct(int|string|Level $level = Level::Warning)
    {
        parent::__construct($level, bubble: true);
    }

    protected function write(LogRecord $record) : void
    {
        try {
            $exception = $record->context['exception'] ?? null;
            $meta = $record->context;
            unset($meta['exception']);

            /*
             * Usuario autenticado al momento del error, cuando exista — sin
             * esto, /logs no tiene forma de correlacionar un error con "qué
             * hizo este usuario justo antes" en /audit.
             */
            if (Auth::check()) {
                $meta['user_id'] = Auth::id();
            }

            $level = $this->mapLevel($record->level);
            $stack = $exception instanceof Throwable ? $exception->getTraceAsString() : null;
            $fingerprint = $this->fingerprint($level, $record->message, $stack);

            /**
             * Agrupa errores repetidos bajo la misma fila (occurrences++) en
             * vez de insertar una fila nueva por cada ocurrencia — sin esto,
             * un error en loop (ej. job que reintenta cada minuto) satura la
             * tabla con cientos de filas idénticas.
             *
             * increment() primero (UPDATE ... WHERE fingerprint = ?) en vez de
             * where()->first() + save(): si ninguna fila coincide, devuelve 0
             * filas afectadas sin haber leído nada, y el create() de abajo la
             * crea. Con fingerprint ahora unique() a nivel de BD (ver
             * migración add_stability_indexes_to_system_logs_table), dos
             * requests concurrentes con el mismo fingerprint ya no pueden
             * generar dos filas: como mucho, ambas intentan el create() tras
             * ver 0 filas afectadas, y la segunda choca contra la restricción
             * unique — se captura y se reintenta como increment(), quedando
             * igual de agrupado que si hubiera llegado un instante después.
             */
            $updated = SystemLog::where('fingerprint', $fingerprint)
                ->update(['occurrences' => DB::raw('occurrences + 1'), 'last_seen_at' => now()]);

            if ($updated > 0) {
                return;
            }

            try {
                SystemLog::create([
                    'level' => $level,
                    'message' => $record->message,
                    'context' => $record->channel,
                    'stack' => $stack,
                    'meta' => $meta ?: null,
                    'fingerprint' => $fingerprint,
                    'occurrences' => 1,
                    'last_seen_at' => now(),
                ]);
            } catch (QueryException $e) {
                if (! $this->isUniqueConstraintViolation($e)) {
                    throw $e;
                }

                // Otro request ganó la carrera y ya creó la fila entre
                // nuestro update() (0 filas) y este create() — nos sumamos a
                // su ocurrencia en vez de duplicar.
                SystemLog::where('fingerprint', $fingerprint)
                    ->update(['occurrences' => DB::raw('occurrences + 1'), 'last_seen_at' => now()]);
            }
        } catch (Throwable) {
            // Nunca interrumpir el flujo de logging real por un fallo de persistencia.
        }
    }

    /**
     * Hash de agrupación: nivel + mensaje + primera línea "propia" del stack
     * (no vendor/) — dos errores con el mismo mensaje pero orígenes de código
     * distintos no deben agruparse entre sí.
     */
    private function fingerprint(string $level, string $message, ?string $stack) : string
    {
        $originLine = '';

        if ($stack !== null) {
            foreach (explode("\n", $stack) as $line) {
                if (str_contains($line, '/vendor/')) {
                    continue;
                }
                $originLine = trim($line);

                break;
            }
        }

        return hash('sha256', $level . '|' . $message . '|' . $originLine);
    }

    private function isUniqueConstraintViolation(QueryException $e) : bool
    {
        // 23000 = SQLSTATE de integridad referencial/unicidad en MySQL/MariaDB/SQLite.
        return $e->getCode() === '23000';
    }

    /**
     * Mapea el nivel Monolog al valor persistido en system_logs.level.
     * Antes colapsaba todo lo >= Error a 'error', perdiendo la distinción
     * entre un error común y un critical/emergency (sistema caído) — ambos
     * se veían idénticos en /logs. Ahora cada nivel de PSR-3/Monolog >=
     * Warning tiene su propio valor; la columna ya es string(10), que cabe
     * el más largo ('emergency').
     */
    private function mapLevel(Level $level) : string
    {
        return match (true) {
            $level->value >= Level::Emergency->value => 'emergency',
            $level->value >= Level::Alert->value => 'alert',
            $level->value >= Level::Critical->value => 'critical',
            $level->value >= Level::Error->value => 'error',
            default => 'warn',
        };
    }
}
