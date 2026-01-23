<?php

namespace App\DTOs\ExchangeRate;

use App\DTOs\ContextDTO;

/**
 * Data Transfer Object para el contexto de tasa de cambio.
 *
 * Transporta información necesaria para la vista o capa superior.
 */
final class ExchangeRateContextServiceDTO implements ContextDTO
{
    /**
     * @param array|object $currencies    Lista u objeto con monedas disponibles.
     * @param mixed        $exchangeRates Datos de las tasas de cambio.
     * @param string       $roleUser      Rol del usuario (ej. 'commerce', 'customer').
     * @param string|null  $tokenId       Token de usuario conectado por API (opcional).
     */
    public function __construct(
        public readonly array|object $currencies,
        public readonly mixed $exchangeRates,
        public readonly string $roleUser,
        public readonly ?string $tokenId = null,
    ) {}

    /**
     * Convierte el DTO en un array para la vista.
     *
     * @return array<string, mixed>
     */
    public function toView(): array
    {
        return [
            'currencies'    => $this->currencies,
            'exchangeRates' => $this->exchangeRates,
            'roleUser'      => $this->roleUser,
            'tokenId'       => $this->tokenId,
        ];
    }
}
