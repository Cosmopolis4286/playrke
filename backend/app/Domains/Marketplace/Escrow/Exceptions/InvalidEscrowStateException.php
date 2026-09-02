<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Escrow\Exceptions;

/**
 * Lanzada cuando se invoca una transición de Escrow (fund/lockAsset/release/
 * cancel/refund/dispute/resolve) desde un estado de entrada distinto al
 * esperado. Cada método de EscrowService valida su estado de entrada
 * explícitamente — ver MARKETPLACE_ARCHITECTURE_AUDIT.md sección 17.
 */
class InvalidEscrowStateException extends \DomainException
{
}
