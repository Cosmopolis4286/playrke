<?php

namespace App\Infrastructure\Persistence\ExchangeRate;

use App\Domain\ExchangeRate\ExchangeRateStatus;
use App\DTOs\ExchangeRate\ExchangeRateDTO;

final class ExchangeRateMapper
{
    private function __construct() {}

    /**
     * Mapear DTO de dominio a estructura de persistencia.
     *
     * @return array<string, mixed>
     */
    public static function toPersistence(ExchangeRateDTO $dto): array
    {
        return [
            'commerce_id' => $dto->creatorType === 'commerce' ? $dto->creatorId : null,
            'customer_id' => $dto->creatorType === 'customer' ? $dto->creatorId : null,
            'currency_id' => $dto->currencyId,
            'exchange_rate' => $dto->rate,
            'percentage' => $dto->percentage,
            'status' => ExchangeRateStatus::ACTIVE->value,
        ];
    }
}
