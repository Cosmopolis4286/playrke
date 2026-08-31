<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Inventory\Exceptions;

/**
 * Lanzada cuando se intenta descontar más quantity de la disponible en un
 * PlayerAsset stackable — mismo rol que "saldo insuficiente" en Wallet,
 * pero para inventario en vez de dinero.
 */
class InsufficientAssetQuantityException extends \DomainException
{
}
