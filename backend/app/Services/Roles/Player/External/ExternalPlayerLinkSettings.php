<?php

namespace App\Services\Roles\Player\External;

use App\Models\AppParameter;
use Illuminate\Support\Facades\Cache;

/**
 * Lectura del setting 'external_player_link_expiration_days' (ver
 * AppParameterSeeder) — días de vigencia del link de un jugador externo antes de
 * expirar. Compartido entre el flujo de venta (ExternalPlayerLinkService) y el panel
 * admin (ExternalPlayerAdminService::regenerateLink()), antes era un valor
 * fijo de 7 días sin forma de ajustarlo.
 */
final class ExternalPlayerLinkSettings
{
    private const CACHE_KEY = 'setting:external_player_link_expiration_days';
    private const CACHE_TTL_SECONDS = 600;
    private const DEFAULT_DAYS = 7;

    public static function expirationDays(): int
    {
        $days = Cache::remember(
            self::CACHE_KEY,
            self::CACHE_TTL_SECONDS,
            fn () => AppParameter::active()->byParam('external_player_link_expiration_days')->value('value'),
        );

        return $days !== null && (int) $days > 0 ? (int) $days : self::DEFAULT_DAYS;
    }
}
