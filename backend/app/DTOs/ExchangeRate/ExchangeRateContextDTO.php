<?php

namespace App\DTOs\ExchangeRate;

final class ExchangeRateContextDTO
{
    public function __construct(
        public readonly array $currencies,
        public readonly mixed $exchangeRates,
        public readonly string $tokenId
    ) {}

    public function toView(): array
    {
        return [
            'currencies'    => $this->currencies,
            'exchangeRates' => $this->exchangeRates,
            'tokenId'       => $this->tokenId
        ];
    }
}
