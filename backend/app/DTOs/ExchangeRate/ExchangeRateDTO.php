<?php

namespace App\DTOs\ExchangeRate;

use InvalidArgumentException;

/**
 * Data Transfer Object para ExchangeRate.
 *
 * Centraliza los datos necesarios para crear, actualizar
 * y modificar el estado de una tasa de cambio, desacoplando
 * la capa HTTP del dominio.
 */
final class ExchangeRateDTO
{
    public function __construct(
        public readonly string $creatorType,   // commerce | customer
        public readonly string $currencyId,
        public readonly float $value,
        public readonly ?string $id = null,     // Usado en update / delete / toggle
    ) {}

    /**
     * Crear DTO desde un request validado.
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['creator_type'])) {
            throw new InvalidArgumentException('creator_type es requerido');
        }

        return new self(
            creatorType: $data['creator_type'],
            currencyId: $data['currency_id'],
            value: (float) $data['value'],
            id: $data['id'] ?? null,
        );
    }

    /**
     * Crear DTO desde un payload cifrado (URLs / botones).
     */
    public static function fromPayload(string $payload): self
    {
        $data = json_decode(urldecode($payload), true);

        if (!$data || !isset($data['creatorType'], $data['id'])) {
            throw new InvalidArgumentException('Payload inválido');
        }

        return new self(
            creatorType: $data['creatorType'],
            currencyId: $data['currency'] ?? '',
            value: isset($data['exchange_rate']) ? (float) $data['exchange_rate'] : 0,
            id: decrypt($data['id']),
        );
    }

    /**
     * Convertir DTO a array para persistencia.
     */
    public function toArray(): array
    {
        return [
            'currency_id' => $this->currencyId,
            'value'       => $this->value,
        ];
    }
}
