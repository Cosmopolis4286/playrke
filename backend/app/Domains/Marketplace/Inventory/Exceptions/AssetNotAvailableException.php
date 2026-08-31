<?php

declare(strict_types=1);

namespace App\Domains\Marketplace\Inventory\Exceptions;

/**
 * Lanzada cuando una transición de PlayerAsset esperada bajo lock afecta 0
 * filas: el UPDATE ... WHERE status = X ya validó bajo lockForUpdate() que
 * el estado de entrada era el esperado, así que llegar aquí significa un
 * conflicto real (otro proceso ganó la carrera, o el estado no era el que
 * el caller asumía) — nunca una condición esperada a ignorar en silencio.
 */
class AssetNotAvailableException extends \DomainException
{
}
