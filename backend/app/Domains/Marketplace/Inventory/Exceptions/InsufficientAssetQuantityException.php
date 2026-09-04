<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Inventory\Exceptions;

use App\Exceptions\Domain\DomainException;

/**
 * Lanzada cuando se intenta descontar más quantity de la disponible en un
 * PlayerAsset stackable — mismo rol que "saldo insuficiente" en Wallet,
 * pero para inventario en vez de dinero.
 *
 * Antes extendía \DomainException nativo y caía en 500 genérico en
 * producción (ver docblock de InvalidListingException para el porqué
 * exacto).
 */
class InsufficientAssetQuantityException extends DomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 422);
    }
}
