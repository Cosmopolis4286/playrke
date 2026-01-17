<?php

namespace App\DTOs\ExchangeRate;

final class ExchangeRateContextDTO
{
    public function __construct(
        public readonly string $bladeLayout,
        public readonly array $creator,
        public readonly array $currencies,
        public readonly mixed $exchangeRates,
        public readonly bool $isLogged,
        public readonly string $name,
        public readonly string $title
    ) {}

    public function toView(): array
    {
        return [
            'bladeLayout'   => $this->bladeLayout,
            'creator'       => $this->creator,
            'currencies'    => $this->currencies,
            'exchangeRates' => $this->exchangeRates,
            'isLogged'       => $this->isLogged,
            'name'          => $this->name,
            'title'         => $this->title,
        ];
    }
}
