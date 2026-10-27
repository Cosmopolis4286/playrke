<?php

namespace App\Services\Audit;

use App\Models\AppParameter;
use Illuminate\Support\Facades\Cache;

/**
 * Lectura del parámetro 'system_log_retention_days' (ver AppParameterSeeder) — días
 * que se conservan los registros de `system_logs` antes de ser purgados por
 * LogPruneCommand. Retención más corta que audits: son errores/warnings
 * técnicos de mayor volumen y menor valor histórico que auditoría de negocio.
 */
final class SystemLogRetentionSettings
{
    private const CACHE_KEY = 'setting:system_log_retention_days';
    private const CACHE_TTL_SECONDS = 600;
    private const DEFAULT_DAYS = 30;

    public static function retentionDays() : int
    {
        $days = Cache::remember(
            self::CACHE_KEY,
            self::CACHE_TTL_SECONDS,
            fn () => AppParameter::active()->byParam('system_log_retention_days')->value('value'),
        );

        return $days !== null && (int) $days > 0 ? (int) $days : self::DEFAULT_DAYS;
    }
}
