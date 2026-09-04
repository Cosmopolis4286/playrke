<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Crypto\Exceptions;

use App\Exceptions\Domain\DomainException;

/**
 * Lanzada por DepositWatcher cuando una transacción reportada no pasa
 * validación (red/token fuera de whitelist, confirmaciones insuficientes,
 * dirección destino no asignada a ningún usuario). Nunca se acredita nada
 * cuando esta excepción se lanza.
 *
 * Antes extendía \DomainException nativo y caía en 500 genérico en
 * producción (ver docblock de InvalidListingException para el porqué
 * exacto).
 */
class InvalidCryptoTransactionException extends DomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 422);
    }
}
