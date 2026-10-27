<?php

namespace App\DTOs\ExchangeRate;

/**
 * DTO de dominio para Tasa de Cambio de Admin.
 *
 * Objeto inmutable que transporta datos ya resueltos
 * entre capas del dominio y aplicación para el rol admin.
 */
final class AdminExchangeRateDTO
{
    /**
     * @param int|null    $id         identificador de la tasa (solo en update)
     * @param int         $currencyId ID interno de la moneda (YA RESUELTO)
     * @param float       $value      valor de la tasa de cambio
     * @param string|null $tokenId    token de usuario conectado por API (opcional)
     */
    public function __construct(
        public readonly ?int $id,
        public readonly int $currencyId,
        public readonly float $value,
        public readonly ?string $tokenId = null,
    ) {
    }

    /**
     * Datos listos para persistencia en el repositorio.
     *
     * Omite el ID y token ya que no se suelen persistir en esta capa.
     *
     * @return array<string, mixed>
     */
    public function toArray() : array
    {
        return [
            'currency_id' => $this->currencyId,
            'value' => $this->value,
        ];
    }
}
