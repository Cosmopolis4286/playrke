<?php

namespace App\Services\ExchangeRate;

use App\Models\ExchangeRate;

/**
 * Consulta simple de la tasa de cambio oficial vigente de una moneda.
 *
 * Distinto de ExchangeRateDomainService (que resuelve tasas específicas por
 * rol/entidad de comercio): aquí solo se busca la tasa ACTIVE más reciente
 * registrada para una moneda, sin contexto de usuario ni de rol.
 */
final class CurrencyExchangeRateService
{
    /**
     * Tasa de cambio oficial vigente para una moneda (1.0 si es USD o no hay tasa registrada).
     */
    public function getActiveRate(?int $currencyId): float
    {
        if (!$currencyId || $currencyId === 1) {
            return 1.0;
        }

        $amount = ExchangeRate::where('currency_id', $currencyId)
            ->where('status', 'ACTIVE')
            ->orderByDesc('created_at')
            ->value('amount');

        return $amount ? (float) $amount : 1.0;
    }
}
