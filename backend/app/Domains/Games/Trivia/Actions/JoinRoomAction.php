<?php

declare(strict_types=1);

namespace App\Domains\Trivia\Actions;

use App\Domains\Trivia\Contracts\RoomServiceInterface;
use App\Domains\Trivia\DTOs\PlayerDTO;
use App\Domains\Trivia\DTOs\RoomDTO;
use RuntimeException;

/**
 * Action: Unirse a Sala.
 * 
 * Caso de uso para que un jugador se una a una sala existente.
 */
class JoinRoomAction
{
    public function __construct(
        private readonly RoomServiceInterface $roomService
    ) {}

    /**
     * Ejecutar la acción.
     * 
     * @param string $roomId ID o código de la sala
     * @param string $playerId ID del jugador
     * @param string $nickname Nombre del jugador
     * @param array $playerData Datos adicionales del jugador
     */
    public function execute(
        string $roomId,
        string $playerId,
        string $nickname,
        array $playerData = []
    ): array {
        // Validar nickname
        $nickname = trim($nickname);
        if (strlen($nickname) < 2 || strlen($nickname) > 30) {
            throw new RuntimeException('El nickname debe tener entre 2 y 30 caracteres');
        }

        // Verificar que el jugador no está en otra sala activa
        // (Implementar según reglas de negocio)

        // Unir a sala
        $playerData['nickname'] = $nickname;
        $player = $this->roomService->joinRoom($roomId, $playerId, $playerData);

        // Obtener información actualizada de la sala
        $room = $this->roomService->getRoom($roomId);
        $players = $this->roomService->listPlayers($roomId);

        return [
            'player' => $player,
            'room' => $room,
            'players' => $players,
            'message' => 'Te has unido a la sala exitosamente',
        ];
    }

    /**
     * Buscar sala por código.
     */
    public function findRoomByCode(string $code): RoomDTO
    {
        // Implementar búsqueda por código
        // Por ahora, asumimos que el código es suficiente para encontrar la sala
        throw new RuntimeException('Método no implementado - usar ID de sala directamente');
    }
}
