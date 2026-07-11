<?php

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
     * No existe ninguna tasa de cambio (ni propia del rol, ni oficial)
     * para la moneda indicada.
     */
    public static function noActiveRate(string|int $currencyId): self
    {
        return new self("No hay tasa de cambio activa registrada para la moneda #{$currencyId}.");
    }
}
