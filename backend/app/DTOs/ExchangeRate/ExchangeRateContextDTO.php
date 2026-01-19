<?php

namespace App\DTOs\ExchangeRate;

use App\DTOs\ContextDTO;

final class ExchangeRateContextDTO implements ContextDTO
{
    public function __construct(
        public readonly array|object $currencies,
        public readonly mixed $exchangeRates,
        public readonly string $roleUser,
        public readonly string $tokenId
    ) {}

    public function toView(): array
    {
        return [
            'currencies'    => $this->currencies,
            'exchangeRates' => $this->exchangeRates,
            'roleUser'      => $this->roleUser,
            'tokenId'       => $this->tokenId
        ];
    }
}
