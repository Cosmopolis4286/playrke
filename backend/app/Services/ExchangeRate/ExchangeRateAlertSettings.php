<?php

namespace App\Services\ExchangeRate;

use App\Models\AppParameter;
use Illuminate\Support\Facades\Cache;

/**
 * Umbrales de ExchangeRateAlertService, configurables desde Parámetros de la
 * app (ver AppParameterSeeder). Mismo patrón que LogAlertSettings.
 */
final class ExchangeRateAlertSettings
{
    private const CACHE_TTL_SECONDS = 600;

    /** Cambio mínimo (%) de una tasa que se avisa a la red. */
    public static function changePct() : float
    {
        return self::number('exchange_alert_change_pct', 3.0);
    }

    /** Desvío máximo (%) de la tasa propia respecto a la oficial. */
    public static function deviationPct() : float
    {
        return self::number('exchange_alert_deviation_pct', 10.0);
    }

    /** Horas sin actualizar una tasa propia para avisar a su dueño. */
    public static function staleHours() : int
    {
        return (int) self::number('exchange_alert_stale_hours', 24.0);
    }

    private static function number(string $param, float $default) : float
    {
        $value = Cache::remember(
            "setting:{$param}",
            self::CACHE_TTL_SECONDS,
            fn () => AppParameter::active()->byParam($param)->value('value'),
        );

        return is_numeric($value) && (float) $value > 0 ? (float) $value : $default;
    }
}
