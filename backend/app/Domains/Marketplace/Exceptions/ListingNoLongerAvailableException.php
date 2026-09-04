<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Exceptions;

use App\Exceptions\Domain\DomainException;

/**
 * Lanzada cuando UPDATE marketplace_listings ... WHERE status='ACTIVE' afecta
 * 0 filas al intentar comprar/cancelar/expirar: otro proceso ya cambió el
 * estado (venta concurrente ganada por otro comprador, o el vendedor ya
 * canceló) — ver MARKETPLACE_ARCHITECTURE_AUDIT.md sección 8.
 *
 * 409 (conflicto de estado concurrente), no 422 — antes extendía
 * \DomainException nativo y caía en 500 genérico en producción (ver
 * docblock de InvalidListingException para el porqué exacto).
 */
class ListingNoLongerAvailableException extends DomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 409);
    }
}
