<?php

declare(strict_types=1);

namespace App\Exceptions\Domain\ExchangeRate;

use App\Exceptions\Domain\DomainException;

/**
 * Excepciones de dominio relacionadas a la resolución de tasas de cambio.
 *
 * exchange_rates (tasa oficial de la plataforma) es la única fuente de
 * verdad última: cuando ninguna tasa (propia del rol o oficial) existe
 * para una moneda, se lanza esta excepción — nunca se asume una tasa 1:1
 * de forma silenciosa.
 */
final class ExchangeRateException extends DomainException
{
    /**
     * ExchangeRateDeviationCap: la tasa propia se aleja de la oficial más de
     * lo que permite el admin (exchange_rate_max_deviation_pct).
     */
    public static function deviationExceeded(string $currency, float $maxPct, string $official, string $min, string $max) : self
    {
        return new self('EXCHANGE_RATE_DEVIATION_EXCEEDED', 422, replacements: [
            'currency' => $currency,
            'max_pct' => rtrim(rtrim(number_format($maxPct, 2, ',', '.'), '0'), ','),
            'official' => $official,
            'min' => $min,
            'max' => $max,
        ]);
    }

    /**
     * ExchangeRateCrudService::update() requiere el ID de la tasa a
     * actualizar — no aplica a create() (que siempre genera una nueva).
     */
    public static function idRequired() : self
    {
        return new self('ID requerido', 422);
    }

    /**
     * ExchangeRateDomainService::calculatePercentage(): el monto anterior
     * debe ser positivo para poder calcular la variación porcentual
     * (división por cero/negativo no tiene sentido de negocio).
     */
    public static function invalidPreviousAmount() : self
    {
        return new self('Monto anterior inválido', 422);
    }

    /**
     * No existe ninguna tasa de cambio (ni propia del rol, ni oficial)
     * para la moneda indicada.
     */
    public static function noActiveRate(string|int $currencyId) : self
    {
        return new self("No hay tasa de cambio activa registrada para la moneda #{$currencyId}.", 422);
    }

    /**
     * ExchangeRateCrudService: show()/deleteByRole() no encontraron el
     * registro de tasa de cambio indicado por su ID.
     */
    public static function notFound(string $exchangeRateId) : self
    {
        return new self("Tasa de cambio no encontrada: {$exchangeRateId}", 404);
    }

    /**
     * ExchangeRateCrudService::update(): getValueById() no encontró el
     * registro (repo->getValueById($dto->id, 'amount') devolvió null) —
     * mensaje distinto de notFound() porque así existía en el código
     * original, ambos casos son "no encontrada" con texto ligeramente
     * distinto según el punto exacto de la validación.
     */
    public static function notFoundById(string $exchangeRateId) : self
    {
        return new self("Tasa con ID {$exchangeRateId} no encontrada", 404);
    }
}
