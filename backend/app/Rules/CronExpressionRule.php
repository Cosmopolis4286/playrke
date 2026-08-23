<?php

namespace App\Rules;

use Closure;
use Cron\CronExpression;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida que un string sea una expresión cron sintácticamente correcta
 * (5 campos: minuto hora día mes día-semana) y, opcionalmente, que no sea
 * más agresiva que un piso mínimo de segundos entre ejecuciones — usada al
 * editar la frecuencia de un comando programado desde el panel Jobs/Scheduler
 * (ver JobsCenterController::updateScheduledCommandFrequency()).
 *
 * El piso se mide comparando dos ejecuciones consecutivas reales a partir de
 * "ahora" en vez de inspeccionar los campos crudos del cron, porque el campo
 * "minuto" por sí solo no basta: "0,30 * * * *" no usa la sintaxis de paso
 * (step) pero sí corre cada 30 minutos, y una inspección textual lo dejaría
 * pasar por error.
 */
final class CronExpressionRule implements ValidationRule
{
    public function __construct(private readonly ?int $minIntervalSeconds = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $expression = trim((string) $value);

        try {
            $cron = new CronExpression($expression);
        } catch (\Throwable) {
            $fail('La expresión cron no es válida. Usa el formato estándar de 5 campos: minuto hora día mes día-semana.');

            return;
        }

        if ($this->minIntervalSeconds === null) {
            return;
        }

        $first  = $cron->getNextRunDate('now', 0, true);
        $second = $cron->getNextRunDate($first, 0, false);
        $actualIntervalSeconds = $second->getTimestamp() - $first->getTimestamp();

        if ($actualIntervalSeconds < $this->minIntervalSeconds) {
            $minMinutes = (int) ceil($this->minIntervalSeconds / 60);
            $fail("Este comando no puede ejecutarse más seguido que cada {$minMinutes} minuto(s), por seguridad.");
        }
    }
}
