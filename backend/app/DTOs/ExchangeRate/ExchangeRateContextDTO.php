<?php

namespace App\DTOs\ExchangeRate;

final class ExchangeRateContextDTO
{
    public function __construct(
        public readonly string $bladeLayout,
        public readonly string $title,
        public readonly string $name,
        public readonly bool $returnBtn,
        public readonly array $creator,
        public readonly array $currencies,
        public readonly mixed $exchangeRates
    ) {}

    public function toView(): array
    {
        return [
            'bladeLayout'   => $this->bladeLayout,
            'title'         => $this->title,
            'name'          => $this->name,
            'returnBtn'     => $this->returnBtn,
            'creator'       => $this->creator,
            'currencies'    => $this->currencies,
            'exchangeRates' => $this->exchangeRates,
        ];
    }
}
