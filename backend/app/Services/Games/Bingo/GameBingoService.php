<?php

namespace App\Services\Games\Bingo;

use App\Models\Roles\Commerce;

/**
 * Servicio de aplicación para exponer
 * los casos de uso del dominio GameBingo.
 *
 * Responsabilidades:
 * - Orquestar casos de uso
 * - Delegar operaciones a servicios de dominio
 * - Mantener controllers desacoplados
 *
 * NO contiene lógica de dominio ni infraestructura.
 */
final class GameBingoService
{
    public function __construct(
        private readonly \App\Services\Games\Bingo\GameRooms\BingoGameRoomsService $gameRoom,
    ) {}

    /**
     * Obtener todos las salas.
     *
     * @param string $contex
     */
    public function gameRooms(string $context)
    {
        $this->gameRoom->context($context);
    }
}
