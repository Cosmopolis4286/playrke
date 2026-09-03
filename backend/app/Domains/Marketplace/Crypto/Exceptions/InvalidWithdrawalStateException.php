<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Crypto\Exceptions;

/**
 * Lanzada cuando se invoca una transición de CryptoWithdrawal desde un
 * estado de entrada distinto al esperado. Ver
 * MARKETPLACE_ARCHITECTURE_AUDIT.md sección 14 — cada método de
 * WithdrawalService valida su estado de entrada explícitamente.
 */
class InvalidWithdrawalStateException extends \DomainException
{
}
