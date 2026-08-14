<?php

namespace App\Services\Audit;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Lectura del setting 'audit_retention_days' (ver SettingSeeder) — días que
 * se conservan los registros de `audits` antes de ser purgados por
 * AuditPruneCommand. Antes no existía política de retención: la tabla
 * crecía sin límite.
 */
final class AuditRetentionSettings
{
    private const CACHE_KEY = 'setting:audit_retention_days';
    private const CACHE_TTL_SECONDS = 600;
    private const DEFAULT_DAYS = 90;

    public static function retentionDays(): int
    {
        $days = Cache::remember(
            self::CACHE_KEY,
            self::CACHE_TTL_SECONDS,
            fn () => Setting::active()->byParam('audit_retention_days')->value('value'),
        );

        return $days !== null && (int) $days > 0 ? (int) $days : self::DEFAULT_DAYS;
    }
}
