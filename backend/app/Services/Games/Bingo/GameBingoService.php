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
        private readonly \App\Services\Games\Bingo\Card\BingoCardService $bingoCard,
    ) {}

    /**
     * Obtener contexto las salas de juego BINGO.
     *
     * @param string $context
     * @param int|null $userId
     *
     * @return array
     * @throws \BingoRoomException Si el contexto no es válido
     */
    public function gameRooms(string $context, ?int $userId = null): array
    {
        return $this->gameRoom->context($context, $userId);
    }

    /**
     * Generar cartón de bingo.
     * @return array
     */
    public function cards(): array
    {
        return $this->bingoCard->context('generate-card');
    }
}
