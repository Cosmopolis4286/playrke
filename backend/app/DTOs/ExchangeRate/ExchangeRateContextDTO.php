<?php

namespace App\DTOs\ExchangeRate;

use App\DTOs\ContextDTO;

/**
 * Data Transfer Object para el contexto de tasa de cambio.
 *
 * Transporta información necesaria para la vista o capa superior.
 */
final class ExchangeRateContextDTO implements ContextDTO
{
    /**
     * @param array|object $currencies           lista u objeto con monedas disponibles
     * @param mixed        $exchangeRates        datos de las tasas de cambio
     * @param string       $roleUser             Rol del usuario (ej. 'commerce', 'customer').
     * @param string|null  $tokenId              token de usuario conectado por API (opcional)
     * @param bool         $autoUpdateEnabled    estado del cronjob de actualización automática
     * @param bool         $bcvAutoUpdateEnabled estado del cronjob de actualización automática vía BCV (VES)
     */
    public function __construct(
        public readonly array|object $currencies,
        public readonly mixed $exchangeRates,
        public readonly string $roleUser,
        public readonly ?string $tokenId = null,
        public readonly bool $autoUpdateEnabled = false,
        public readonly bool $bcvAutoUpdateEnabled = false,
    ) {
    }

    /**
     * Convierte el DTO en un array para la vista.
     *
     * @return array<string, mixed>
     */
    public function toView() : array
    {
        return [
            'currencies' => $this->currencies,
            'exchangeRates' => $this->exchangeRates,
            'roleUser' => $this->roleUser,
            'tokenId' => $this->tokenId,
            'autoUpdateEnabled' => $this->autoUpdateEnabled,
            'bcvAutoUpdateEnabled' => $this->bcvAutoUpdateEnabled,
        ];
    }
}
