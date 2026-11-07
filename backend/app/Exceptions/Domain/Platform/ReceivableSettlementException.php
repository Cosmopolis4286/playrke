<?php

declare(strict_types=1);

namespace App\Exceptions\Domain\Platform;

use App\Exceptions\Domain\DomainException;

/**
 * Cobro de deudas de un comercio (PlatformReceivableService::settle()).
 */
final class ReceivableSettlementException extends DomainException
{
    /**
     * El total pendiente cambió desde que el admin lo vio (se anuló o se
     * agregó una venta): no se cobra nada para no registrar un monto distinto
     * al que el comercio pagó.
     */
    public static function amountMismatch(float $current, float $expected) : self
    {
        return new self('RECEIVABLE_SETTLEMENT_AMOUNT_MISMATCH', 409, replacements: [
            'current' => number_format($current, 2, ',', '.'),
            'expected' => number_format($expected, 2, ',', '.'),
        ]);
    }
}
