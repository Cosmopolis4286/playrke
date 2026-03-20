<?php

namespace App\Services\Games\Bingo\Room\User;

use App\Models\Games\Bingo\Room\BingoRoom;
use App\Models\Games\Bingo\Room\User\BingoRoomUser;
use App\Services\Games\Bingo\Contracts\RoomUserRegistrationInterface;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para el registro de usuarios en salas de bingo
 */
class RoomUserRegistrationService implements RoomUserRegistrationInterface
{
    /**
     * Registrar un nuevo usuario en la sala o actualizar uno existente
     *
     * @param array $userData Datos del usuario
     * @return array{id: string, isNew: bool}|null Resultado con ID e indicador de nuevo usuario, o null si falla
     */
    public function registerOrUpdateRoomUser(array $userData): ?array
    {
        try {
            // Validar datos del usuario
            if (!$this->validateUserData($userData)) {
                Log::error('Datos de usuario inválidos', ['userData' => $userData]);
                return null;
            }

            // Buscar usuario existente
            $roomUser = $this->findRoomUser($userData['roomUser'] ?? null);
            $isNew = false;

            if (!$roomUser) {
                // Crear nuevo usuario
                $roomUser = $this->createRoomUser($userData);
                if (!$roomUser) {
                    return null;
                }
                $isNew = true;
            } else {
                // Actualizar cartones del usuario existente
                if (!$this->updateUserCards($roomUser, $userData['numOfCtns'])) {
                    return null;
                }
            }

            return ['id' => $roomUser->id, 'isNew' => $isNew];
        } catch (\Throwable $e) {
            Log::error('Error al registrar o actualizar el usuario de sala: ' . $e->getMessage());
            Log::error('Datos userData:', $userData);
            return null;
        }
    }

    /**
     * Validar los datos del usuario antes del registro
     *
     * @param array $userData Datos del usuario a validar
     * @return bool True si los datos son válidos
     */
    public function validateUserData(array $userData): bool
    {
        // Validar campos requeridos
        $requiredFields = ['roomId', 'creator', 'numOfCtns'];
        
        foreach ($requiredFields as $field) {
            if (!isset($userData[$field])) {
                Log::error("Campo requerido faltante: {$field}", ['userData' => $userData]);
                return false;
            }
        }

        // Validar que el creator tenga un ID
        if (!isset($userData['creator']['id'])) {
            Log::error('creator id no encontrado en userData', ['userData' => $userData]);
            return false;
        }

        // Validar que numOfCtns sea un número positivo
        if (!is_numeric($userData['numOfCtns']) || $userData['numOfCtns'] <= 0) {
            Log::error('numOfCtns debe ser un número positivo', ['numOfCtns' => $userData['numOfCtns']]);
            return false;
        }

        return true;
    }

    /**
     * Buscar un usuario existente en la sala
     *
     * @param string|null $roomUserId ID del usuario en la sala
     * @return BingoRoomUser|null
     */
    public function findRoomUser(?string $roomUserId): ?BingoRoomUser
    {
        if (!$roomUserId) {
            return null;
        }

        return BingoRoomUser::find($roomUserId);
    }

    /**
     * Crear un nuevo registro de usuario en la sala
     *
     * @param array $userData Datos del usuario
     * @return BingoRoomUser|null
     */
    public function createRoomUser(array $userData): ?BingoRoomUser
    {
        try {
            $roomUserData = [
                'room_id' => $userData['roomId'],
                'user_id' => $userData['creator']['id'],
                'cards_purchased' => $userData['numOfCtns'],
                'automarker' => 1,
            ];

            return BingoRoomUser::create($roomUserData);
        } catch (\Throwable $e) {
            Log::error('Error al crear usuario de sala: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualizar la cantidad de cartones de un usuario
     *
     * @param BingoRoomUser $roomUser Usuario de la sala
     * @param int $additionalCards Cantidad de cartones adicionales
     * @return bool True si se actualizó correctamente
     */
    public function updateUserCards(BingoRoomUser $roomUser, int $additionalCards): bool
    {
        try {
            return $roomUser->increment('cards_purchased', $additionalCards);
        } catch (\Throwable $e) {
            Log::error('Error al actualizar cartones del usuario: ' . $e->getMessage());
            return false;
        }
    }
}
