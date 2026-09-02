<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Exceptions;

/**
 * Lanzada cuando UPDATE marketplace_listings ... WHERE status='ACTIVE' afecta
 * 0 filas al intentar comprar/cancelar/expirar: otro proceso ya cambió el
 * estado (venta concurrente ganada por otro comprador, o el vendedor ya
 * canceló) — ver MARKETPLACE_ARCHITECTURE_AUDIT.md sección 8.
 */
class ListingNoLongerAvailableException extends \DomainException
{
}
