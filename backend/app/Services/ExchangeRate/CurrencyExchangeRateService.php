<?php

namespace App\Services\ExchangeRate;

use App\Exceptions\Domain\ExchangeRate\ExchangeRateException;
use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Support\Facades\Cache;

/**
 * Única fuente de verdad para la tasa de cambio OFICIAL de la plataforma
 * (tabla `exchange_rates`).
 *
 * Distinto de ExchangeRateDomainService (que resuelve tasas específicas por
 * rol/entidad de comercio, que a su vez cae aquí si el rol no tiene tasa
 * propia configurada): aquí solo se busca la tasa ACTIVE más reciente
 * registrada para una moneda, sin contexto de usuario ni de rol.
 */
final class CurrencyExchangeRateService
{
    /**
     * TTL corto: la tasa cambia con poca frecuencia (ediciones manuales de
     * admin/commerce/customer), pero este método se llama en loops por sala
     * (lobby de bingo, listados admin) — un TTL de segundos elimina la
     * mayoría de queries repetidas sin arriesgar montos desactualizados
     * de forma perceptible.
     */
    private const TTL_SECONDS = 30;

    /**
     * Tasa de cambio oficial vigente para una moneda.
     *
     * USD siempre es 1:1 consigo misma. Para cualquier otra moneda, si no
     * hay tasa ACTIVE registrada se lanza ExchangeRateException — nunca se
     * asume 1:1 en silencio (eso corrompía prize_pool/total_sales/pagos,
     * tratando montos en moneda local como si ya fueran USD).
     *
     * @throws ExchangeRateException Si no hay tasa activa para la moneda.
     */
    public function getActiveRate(string|int|null $currencyId): float
    {
        if (!$currencyId || (string) $currencyId === (string) Currency::usdId()) {
            return 1.0;
        }

        $amount = Cache::remember(
            "exchange_rate:active:{$currencyId}",
            self::TTL_SECONDS,
            fn () => ExchangeRate::where('currency_id', $currencyId)
                ->where('status', 'ACTIVE')
                ->orderByDesc('created_at')
                ->value('amount')
        );

        if (!$amount || (float) $amount <= 0) {
            throw ExchangeRateException::noActiveRate($currencyId);
        }

        return (float) $amount;
    }
}
