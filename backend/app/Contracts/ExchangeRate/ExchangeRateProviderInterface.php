<?php

namespace App\Contracts\ExchangeRate;

interface ExchangeRateProviderInterface
{
    public function fetch(): array;
}
