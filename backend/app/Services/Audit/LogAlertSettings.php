<?php

namespace App\Services\Audit;

use App\Models\AppParameter;
use Illuminate\Support\Facades\Cache;

/**
 * Lectura de los settings 'log_alert_error_threshold' y
 * 'log_alert_window_minutes' (ver AppParameterSeeder) — umbral de errores en la
 * tabla `system_logs` dentro de la ventana configurada que dispara una
 * alerta (ver LogAlertThresholdCommand).
 */
final class LogAlertSettings
{
    private const THRESHOLD_CACHE_KEY = 'setting:log_alert_error_threshold';
    private const WINDOW_CACHE_KEY = 'setting:log_alert_window_minutes';
    private const CACHE_TTL_SECONDS = 600;

    private const DEFAULT_THRESHOLD = 20;
    private const DEFAULT_WINDOW_MINUTES = 15;

    public static function errorThreshold(): int
    {
        $value = Cache::remember(
            self::THRESHOLD_CACHE_KEY,
            self::CACHE_TTL_SECONDS,
            fn () => AppParameter::active()->byParam('log_alert_error_threshold')->value('value'),
        );

        return $value !== null && (int) $value > 0 ? (int) $value : self::DEFAULT_THRESHOLD;
    }

    public static function windowMinutes(): int
    {
        $value = Cache::remember(
            self::WINDOW_CACHE_KEY,
            self::CACHE_TTL_SECONDS,
            fn () => AppParameter::active()->byParam('log_alert_window_minutes')->value('value'),
        );

        return $value !== null && (int) $value > 0 ? (int) $value : self::DEFAULT_WINDOW_MINUTES;
    }
}
