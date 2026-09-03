<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Crypto\Exceptions;

/**
 * Lanzada por DepositWatcher cuando una transacción reportada no pasa
 * validación (red/token fuera de whitelist, confirmaciones insuficientes,
 * dirección destino no asignada a ningún usuario). Nunca se acredita nada
 * cuando esta excepción se lanza.
 */
class InvalidCryptoTransactionException extends \DomainException
{
}
