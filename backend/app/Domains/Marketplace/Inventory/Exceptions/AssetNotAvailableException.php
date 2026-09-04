<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Inventory\Exceptions;

use App\Exceptions\Domain\DomainException;

/**
 * Lanzada cuando una transición de PlayerAsset esperada bajo lock afecta 0
 * filas: el UPDATE ... WHERE status = X ya validó bajo lockForUpdate() que
 * el estado de entrada era el esperado, así que llegar aquí significa un
 * conflicto real (otro proceso ganó la carrera, o el estado no era el que
 * el caller asumía) — nunca una condición esperada a ignorar en silencio.
 *
 * Antes extendía \DomainException nativo y caía en 500 genérico en
 * producción (ver docblock de InvalidListingException para el porqué
 * exacto).
 */
class AssetNotAvailableException extends DomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 422);
    }
}
