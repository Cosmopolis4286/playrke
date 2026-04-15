<?php

namespace App\Services\Games\Bingo\Room\Validation;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Services\Games\Bingo\Contracts\RoomValidationInterface;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para validación de salas de bingo
 */
class RoomValidationService implements RoomValidationInterface
{
    /**
     * Validar que una sala esté disponible para compras
     *
     * @param string $roomId ID de la sala a validar
     * @return BingoRoom|null Sala encontrada y válida o null si no está disponible
     */
    public function validateRoomAvailability(string $roomId): ?BingoRoom
    {
        try {
            $room = $this->getRoomDetails($roomId);
            
            if (!$room) {
                Log::info('Sala no encontrada', ['roomId' => $roomId]);
                return null;
            }

            if (!$this->validateRoomStatus($room)) {
                Log::info('Sala no disponible por estado', ['roomId' => $roomId, 'status' => $room->status->code ?? 'unknown']);
                return null;
            }

            return $room;
        } catch (\Throwable $e) {
            Log::error('Error al validar disponibilidad de sala: ' . $e->getMessage(), ['roomId' => $roomId]);
            return null;
        }
    }

    /**
     * Validar el estado de una sala
     *
     * @param BingoRoom $room Sala a validar
     * @return bool True si la sala está en estado válido para compras
     */
    public function validateRoomStatus(BingoRoom $room): bool
    {
        try {
            // Solo permitir compras en salas con estado ACTIVE
            return $room->status && $room->status->code === 'ACTIVE';
        } catch (\Throwable $e) {
            Log::error('Error al validar estado de sala: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Validar que una sala tenga capacidad disponible
     *
     * @param BingoRoom $room Sala a validar
     * @param int $additionalPlayers Cantidad de jugadores adicionales
     * @return bool True si hay capacidad disponible
     */
    public function validateRoomCapacity(BingoRoom $room, int $additionalPlayers = 1): bool
    {
        try {
            // Por ahora, no hay límite de jugadores definido
            // Se puede implementar lógica de capacidad aquí si es necesario
            return true;
        } catch (\Throwable $e) {
            Log::error('Error al validar capacidad de sala: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener detalles de una sala para validación
     *
     * @param string $roomId ID de la sala
     * @return BingoRoom|null Sala con sus detalles o null si no existe
     */
    public function getRoomDetails(string $roomId): ?BingoRoom
    {
        try {
            return BingoRoom::with('Config')->select(
                'id',
                'total_players',
                'total_cards',
                'total_sales',
                'rke_commission',
                'room_commission',
                'seller_commission',
                'prize_pool',
                'full_house_prize',
            )->whereHas('status', function($query) {
                $query->where('code', 'ACTIVE');
            })->where('id', $roomId)->first();
        } catch (\Throwable $e) {
            Log::error('Error al obtener detalles de sala: ' . $e->getMessage(), ['roomId' => $roomId]);
            return null;
        }
    }

    /**
     * Validar que una sala exista y esté activa
     *
     * @param string $roomId ID de la sala
     * @return bool True si la sala existe y está activa
     */
    public function validateRoomExists(string $roomId): bool
    {
        try {
            return BingoRoom::where('id', $roomId)
                ->whereHas('status', function($query) {
                    $query->where('code', 'ACTIVE');
                })
                ->exists();
        } catch (\Throwable $e) {
            Log::error('Error al validar existencia de sala: ' . $e->getMessage(), ['roomId' => $roomId]);
            return false;
        }
    }
}
