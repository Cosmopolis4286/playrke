<?php

namespace App\Services\Games\Bingo\Contracts;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;

/**
 * Interfaz para el servicio de validación de salas de bingo
 */
interface RoomValidationInterface
{
    /**
     * Validar que una sala esté disponible para compras
     *
     * @param string $roomId ID de la sala a validar
     * @return BingoRoom|null Sala encontrada y válida o null si no está disponible
     */
    public function validateRoomAvailability(string $roomId): ?BingoRoom;

    /**
     * Validar el estado de una sala
     *
     * @param BingoRoom $room Sala a validar
     * @return bool True si la sala está en estado válido para compras
     */
    public function validateRoomStatus(BingoRoom $room): bool;

    /**
     * Validar que una sala tenga capacidad disponible
     *
     * @param BingoRoom $room Sala a validar
     * @param int $additionalPlayers Cantidad de jugadores adicionales
     * @return bool True si hay capacidad disponible
     */
    public function validateRoomCapacity(BingoRoom $room, int $additionalPlayers = 1): bool;

    /**
     * Obtener detalles de una sala para validación
     *
     * @param string $roomId ID de la sala
     * @return BingoRoom|null Sala con sus detalles o null si no existe
     */
    public function getRoomDetails(string $roomId): ?BingoRoom;

    /**
     * Validar que una sala exista y esté activa
     *
     * @param string $roomId ID de la sala
     * @return bool True si la sala existe y está activa
     */
    public function validateRoomExists(string $roomId): bool;
}
