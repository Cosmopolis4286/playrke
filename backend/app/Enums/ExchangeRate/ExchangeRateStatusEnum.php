<?php

namespace App\Enums\ExchangeRate;

enum ExchangeRateStatusEnum : string
{
    public function isActive() : bool
    {
        return $this === self::ACTIVE;
    }
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
    case OLD = 'OLD';
}
