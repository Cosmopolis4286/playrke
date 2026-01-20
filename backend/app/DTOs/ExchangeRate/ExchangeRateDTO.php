<?php

namespace App\DTOs\ExchangeRate;

final class ExchangeRateDTO
{
    /**
     * DTO de dominio para Tasa de Cambio.
     *
     * Objeto inmutable que transporta datos ya resueltos
     * entre capas del dominio y aplicación.
     *
     * @param int|null $id           Identificador de la tasa (solo en update)
     * @param int      $currencyId   ID interno de la moneda (YA RESUELTO)
     * @param float    $amount       Valor de la tasa de cambio
     * @param float    $percentage   Porcentaje adicional
     * @param string   $status       Estado de la tasa (ACTIVE / INACTIVE)
     * @param string|null $token     Token de usuario conectado por API
     */
    public function __construct(
        public readonly ?int $id,
        public readonly int $currencyId,
        public readonly float $amount,
        public readonly float $percentage,
        public readonly string $status,
        public readonly string $token
    ) {}

    /**
     * Datos listos para persistencia en el repositorio.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'currency_id' => $this->currencyId,
            'amount'      => $this->amount,
            'percentage'  => $this->percentage,
            'status'      => $this->status,
        ], static fn($value) => $value !== null);
    }
}
