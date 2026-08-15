<?php

namespace App\Logging;

use App\Models\SystemLog;
use Illuminate\Support\Facades\Auth;
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

    protected function write(LogRecord $record): void
    {
        try {
            $exception = $record->context['exception'] ?? null;
            $meta = $record->context;
            unset($meta['exception']);

            /**
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
             */
            $existing = SystemLog::where('fingerprint', $fingerprint)->first();

            if ($existing) {
                $existing->increment('occurrences');
                $existing->forceFill(['last_seen_at' => now()])->save();
                return;
            }

            SystemLog::create([
                'level'        => $level,
                'message'      => $record->message,
                'context'      => $record->channel,
                'stack'        => $stack,
                'meta'         => $meta ?: null,
                'fingerprint'  => $fingerprint,
                'occurrences'  => 1,
                'last_seen_at' => now(),
            ]);
        } catch (Throwable) {
            // Nunca interrumpir el flujo de logging real por un fallo de persistencia.
        }
    }

    private function mapLevel(Level $level): string
    {
        return $level->value >= Level::Error->value ? 'error' : 'warn';
    }

    /**
     * Hash de agrupación: nivel + mensaje + primera línea "propia" del stack
     * (no vendor/) — dos errores con el mismo mensaje pero orígenes de código
     * distintos no deben agruparse entre sí.
     */
    private function fingerprint(string $level, string $message, ?string $stack): string
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
}
