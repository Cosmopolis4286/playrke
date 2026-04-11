<?php

namespace App\Models\Games\Bingo\Room;

use App\Domains\Games\Bingo\Models\Room\BingoRoomConfig as DomainBingoRoomConfig;

/**
 * Modelo BingoRoomConfig - Capa de compatibilidad
 *
 * Este modelo extiende el modelo del dominio para mantener
 * compatibilidad con código existente durante la migración.
 *
 * @deprecated Usar App\Domains\Games\Bingo\Models\Room\BingoRoomConfig en nuevo código
 */
class BingoRoomConfig extends DomainBingoRoomConfig
{
    // Este modelo hereda todo del dominio
    // No agregar lógica aquí - el dominio es la fuente de verdad
}
