<?php

namespace App\Services\Games\Bingo;

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
        private readonly \App\Services\Games\Bingo\Room\BingoRoomService $gameRoom,
    ) {}

    /**
     * Obtener contexto las salas de juego BINGO.
     *
     * @param string $context
     */
    public function gameRooms(string $context)
    {
        $this->gameRoom->context($context);
    }
}
