<?php

namespace App\Services\ExchangeRate;

use App\Exceptions\Domain\ExchangeRate\ExchangeRateException;
use App\Models\AppParameter;
use App\Models\Currency;
use Illuminate\Support\Facades\Cache;

/**
 * Tope de desvío de las tasas propias (comercializadora / cliente) respecto
 * a la oficial, fijado por el admin en Parámetros
 * ('exchange_rate_max_deviation_pct'; 0 o vacío = sin tope).
 *
 * Se aplica al guardar o activar una tasa propia (ExchangeRateCrudService)
 * y en la actualización automática (ExchangeRateUpdaterService, que en vez
 * de fallar omite esa moneda). Sin tasa oficial para la moneda no hay contra
 * qué comparar y no se limita.
 */
final class ExchangeRateDeviationCap
{
    private const PARAM = 'exchange_rate_max_deviation_pct';

    public function __construct(
        private readonly CurrencyExchangeRateService $official,
    ) {}

    /** Desvío máximo permitido (%), o null si no hay tope. */
    public function maxPct(): ?float
    {
        $value = Cache::remember('setting:' . self::PARAM, 600,
            fn () => AppParameter::active()->byParam(self::PARAM)->value('value'));

        return is_numeric($value) && (float) $value > 0 ? (float) $value : null;
    }

    /**
     * Rango permitido para la moneda, o null si no aplica.
     *
     * @return array{min: float, max: float, official: float, max_pct: float}|null
     */
    public function range(int|string $currencyId): ?array
    {
        $maxPct = $this->maxPct();
        if ($maxPct === null || (string) $currencyId === (string) Currency::usdId()) {
            return null;
        }

        try {
            $official = $this->official->getActiveRate($currencyId);
        } catch (ExchangeRateException) {
            return null;
        }

        return [
            'min'      => round($official * (1 - $maxPct / 100), 4),
            'max'      => round($official * (1 + $maxPct / 100), 4),
            'official' => $official,
            'max_pct'  => $maxPct,
        ];
    }

    public function allows(int|string $currencyId, float $amount): bool
    {
        $range = $this->range($currencyId);

        return $range === null || ($amount >= $range['min'] && $amount <= $range['max']);
    }

    /** @throws ExchangeRateException */
    public function assert(int|string $currencyId, float $amount): void
    {
        if ($this->allows($currencyId, $amount)) {
            return;
        }

        $range    = $this->range($currencyId);
        $currency = Currency::find($currencyId, ['id', 'symbol', 'iso_code']);
        $fmt      = fn (float $v) => trim(($currency?->symbol ?? '') . ' ' . number_format($v, 2, ',', '.'));

        throw ExchangeRateException::deviationExceeded(
            (string) ($currency?->iso_code ?? $currencyId),
            $range['max_pct'],
            $fmt($range['official']),
            $fmt($range['min']),
            $fmt($range['max']),
        );
    }
}
