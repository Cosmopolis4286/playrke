<?php

namespace App\Models\Games\Bingo\Room;

use App\Domains\Games\Bingo\Models\BingoRoom as DomainBingoRoom;

/**
 * Modelo BingoRoom - Capa de compatibilidad
 *
 * Este modelo extiende el modelo del dominio para mantener
 * compatibilidad con código existente durante la migración.
 *
 * @deprecated Usar App\Domains\Games\Bingo\Models\BingoRoom en nuevo código
 */
class BingoRoom extends DomainBingoRoom
{
    // Este modelo hereda todo del dominio
    // No agregar lógica aquí - el dominio es la fuente de verdad
}
