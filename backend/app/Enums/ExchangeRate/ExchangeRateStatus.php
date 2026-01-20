<?php

namespace App\Enums\ExchangeRate;

enum ExchangeRateStatus: string
{
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
    case OLD = 'OLD';

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }
}
