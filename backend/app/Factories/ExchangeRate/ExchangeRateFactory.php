<?php

namespace App\Factories\ExchangeRate;

use App\Contracts\CurrencyRepositoryInterface;
use App\DTOs\ExchangeRate\ExchangeRateDTO;

final class ExchangeRateFactory
{
    public function __construct(
        private CurrencyRepositoryInterface $currencyRepository
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public function fromRequest(array $data): ExchangeRateDTO
    {
        return new ExchangeRateDTO(
            $data['id'] ?? null,
            $this->currencyRepository->getValueByISOCode($data['currency'], 'id'),
            (float) $data['exchange_rate'],
            0.0,
            $data['status'] ?? 'ACTIVE',
        );
    }
}
