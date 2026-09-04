<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Crypto\Exceptions;

use App\Exceptions\Domain\DomainException;

/**
 * Lanzada cuando se invoca una transición de CryptoWithdrawal desde un
 * estado de entrada distinto al esperado. Ver
 * MARKETPLACE_ARCHITECTURE_AUDIT.md sección 14 — cada método de
 * WithdrawalService valida su estado de entrada explícitamente.
 *
 * 409 (conflicto de estado), no 422 — antes extendía \DomainException
 * nativo y caía en 500 genérico en producción (ver docblock de
 * InvalidListingException para el porqué exacto).
 */
class InvalidWithdrawalStateException extends DomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 409);
    }
}
