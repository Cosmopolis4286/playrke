<?php

namespace App\DTOs\ExchangeRate;

use DomainException;

final class ExchangeRateDTO
{
    /**
     * DTO de dominio para Tasa de Cambio.
     *
     * @param int|null $id           Identificador de la tasa (solo en update)
     * @param int      $currencyId   ID interno de la moneda
     * @param float    $amount       Valor de la tasa de cambio
     * @param float    $percentage   Porcentaje adicional (opcional)
     * @param string   $status       Estado de la tasa (ACTIVE / INACTIVE)
     */
    public function __construct(
        public readonly ?int $id,
        public readonly int $currencyId,
        public readonly float $amount,
        public readonly float $percentage,
        public readonly string $status,
    ) {}

    /**
     * Construye el DTO a partir de datos validados del Request.
     *
     * Espera un payload con las claves:
     * - currency (código ISO 4217)
     * - exchange_rate
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? null,
            self::resolveCurrencyId($data['currency']),
            (float) $data['exchange_rate'],
            0.0,
            $data['status'] ?? 'ACTIVE',
        );
    }

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

    /**
     * Resuelve el ID interno de la moneda a partir del código ISO.
     *
     * Esta lógica pertenece al dominio, no al Controller ni al Request.
     */
    private static function resolveCurrencyId(string $currencyCode): int
    {
        return (int) \App\Models\Currency::query()
            ->where('code', $currencyCode)
            ->value('id')
            ?? throw new DomainException("Moneda inválida: {$currencyCode}");
    }
}
