<?php

namespace App\Services\ExchangeRate;

use DomainException;

final class ExchangeRateDomainService
{
    public function calculatePercentage(float $old, float $new): float
    {
        if ($old <= 0) {
            throw new DomainException('Monto anterior inválido');
        }

        return round((($new * 100) / $old) - 100, 2);
    }
}
