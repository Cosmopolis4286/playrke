<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Escrow\Exceptions;

use App\Exceptions\Domain\DomainException;

/**
 * Lanzada cuando se invoca una transición de Escrow (fund/lockAsset/release/
 * cancel/refund/dispute/resolve) desde un estado de entrada distinto al
 * esperado. Cada método de EscrowService valida su estado de entrada
 * explícitamente — ver MARKETPLACE_ARCHITECTURE_AUDIT.md sección 17.
 *
 * 409 (conflicto de estado), no 422 — antes extendía \DomainException
 * nativo y caía en 500 genérico en producción (ver docblock de
 * InvalidListingException para el porqué exacto).
 */
class InvalidEscrowStateException extends DomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 409);
    }
}
