<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Contracts;

use App\Domains\Games\Trivia\DTOs\RoomDTO;
use App\Domains\Games\Trivia\DTOs\PlayerDTO;

/**
 * Interfaz para gestión de salas de trivia.
 */
interface RoomServiceInterface
{
    /**
     * Crear una nueva sala de trivia.
     */
    public function createRoom(array $config): RoomDTO;

    /**
     * Unir un jugador a una sala.
     */
    public function joinRoom(string $roomId, string $playerId, array $playerData): PlayerDTO;

    /**
     * Eliminar un jugador de una sala.
     */
    public function leaveRoom(string $roomId, string $playerId): void;

    /**
     * Obtener información de una sala.
     */
    public function getRoom(string $roomId): RoomDTO;

    /**
     * Listar jugadores en una sala.
     * @return array<PlayerDTO>
     */
    public function listPlayers(string $roomId): array;

    /**
     * Verificar si una sala está llena.
     */
    public function isRoomFull(string $roomId): bool;

    /**
     * Cerrar una sala.
     */
    public function closeRoom(string $roomId, string $reason): void;

    /**
     * Verificar si un jugador está en una sala.
     */
    public function isPlayerInRoom(string $roomId, string $playerId): bool;

    /**
     * Actualizar configuración de la sala.
     */
    public function updateRoomConfig(string $roomId, array $config): RoomDTO;
}
