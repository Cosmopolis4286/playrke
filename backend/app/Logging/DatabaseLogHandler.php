<?php

namespace App\Logging;

use App\Models\SystemLog;
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

            SystemLog::create([
                'level'   => $this->mapLevel($record->level),
                'message' => $record->message,
                'context' => $record->channel,
                'stack'   => $exception instanceof Throwable ? $exception->getTraceAsString() : null,
                'meta'    => $meta ?: null,
            ]);
        } catch (Throwable) {
            // Nunca interrumpir el flujo de logging real por un fallo de persistencia.
        }
    }

    private function mapLevel(Level $level): string
    {
        return $level->value >= Level::Error->value ? 'error' : 'warn';
    }
}
