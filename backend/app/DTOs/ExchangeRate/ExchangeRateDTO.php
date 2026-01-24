<?php

namespace App\DTOs\ExchangeRate;

/**
 * DTO de dominio para Tasa de Cambio.
 *
 * Objeto inmutable que transporta datos ya resueltos
 * entre capas del dominio y aplicación.
 */
final class ExchangeRateDTO
{
    /**
     * @param int|null    $id         Identificador de la tasa (solo en update).
     * @param int         $currencyId ID interno de la moneda (YA RESUELTO).
     * @param float       $amount     Valor de la tasa de cambio.
     * @param string      $status     Estado de la tasa (ACTIVE / INACTIVE).
     * @param string|null $tokenId    Token de usuario conectado por API (opcional).
     */
    public function __construct(
        public readonly ?int $id,
        public readonly int $currencyId,
        public readonly float $amount,
        public readonly string $status,
        public readonly ?string $tokenId = null
    ) {}

    /**
     * Datos listos para persistencia en el repositorio.
     *
     * Omite el ID y token ya que no se suelen persistir en esta capa.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'currency_id' => $this->currencyId,
            'amount'      => $this->amount,
            'status'      => $this->status,
        ];
    }
}
