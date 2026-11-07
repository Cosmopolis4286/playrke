<?php

namespace App\Domains\Games\Bingo\Services\Room\User;

use App\Domains\Games\Bingo\Contracts\RoomUserRegistrationInterface;
use App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Servicio para el registro de usuarios en salas de bingo
 */
class RoomUserRegistrationService implements RoomUserRegistrationInterface
{
    /**
     * Crear un nuevo registro de usuario en la sala
     *
     * @param array $userData Datos del usuario
     */
    public function createRoomUser(array $userData) : ?BingoRoomUser
    {
        try {
            $roomUserData = [
                'room_id' => $userData['roomId'],
                'user_id' => $userData['creator']['id'],
                'cards_purchased' => $userData['cardCount'],
                'role' => ! empty($userData['externalPlayerId'])
                    ? BingoRoomUser::ROLE_SELLER
                    : BingoRoomUser::ROLE_PLAYER,
                'automarker' => 1,
            ];

            return BingoRoomUser::create($roomUserData);
        } catch (Throwable $e) {
            Log::error('Error al crear usuario de sala: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Buscar un usuario existente en la sala
     *
     * @param string|null $roomUserId ID del usuario en la sala
     */
    public function findRoomUser(?string $roomUserId) : ?BingoRoomUser
    {
        if (! $roomUserId) {
            return null;
        }

        return BingoRoomUser::find($roomUserId);
    }

    /**
     * Registrar un nuevo usuario en la sala o actualizar uno existente
     *
     * @param array $userData Datos del usuario
     *
     * @return array{id: string, isNew: bool}|null Resultado con ID e indicador de nuevo usuario, o null si falla
     */
    public function registerOrUpdateRoomUser(array $userData) : ?array
    {
        try {
            // Validar datos del usuario
            if (! $this->validateUserData($userData)) {
                Log::error('Datos de usuario inválidos', ['userData' => $userData]);

                return null;
            }

            // Buscar usuario existente
            $roomUser = null;
            $isNew = false;

            // Si se proporciona roomUser ID, buscar por ID
            if (isset($userData['roomUser']) && $userData['roomUser']) {
                $roomUser = $this->findRoomUser($userData['roomUser']);
            }

            // Si no se encontró por ID, buscar por room_id + user_id
            if (! $roomUser && isset($userData['roomId']) && isset($userData['creator']['id'])) {
                $roomUser = BingoRoomUser::where('room_id', $userData['roomId'])
                    ->where('user_id', $userData['creator']['id'])
                    ->first();
            }

            if (! $roomUser) {
                // Crear nuevo usuario
                $roomUser = $this->createRoomUser($userData);
                if (! $roomUser) {
                    return null;
                }
                $isNew = true;
            } else {
                // Actualizar cartones del usuario existente
                if (! $this->updateUserCards($roomUser, $userData['cardCount'])) {
                    return null;
                }
            }

            // Una fila puede representar a un jugador directo, a un vendedor
            // que agrupa compradores externos o a ambos. No contar al vendedor
            // POS como participante, pero conservar el rol mixto si también juega.
            $resolvedRole = $this->resolveRole($userData, $roomUser);
            if ($roomUser->role !== $resolvedRole) {
                $roomUser->update(['role' => $resolvedRole]);
            }

            return ['id' => $roomUser->id, 'isNew' => $isNew];
        } catch (Throwable $e) {
            Log::error('Error al registrar o actualizar el usuario de sala: ' . $e->getMessage());
            Log::error('Datos userData:', $userData);

            return null;
        }
    }


    private function resolveRole(array $userData, BingoRoomUser $roomUser) : string
    {
        if ($roomUser->role === BingoRoomUser::ROLE_ADMIN) {
            return BingoRoomUser::ROLE_ADMIN;
        }

        $isExternalSale = ! empty($userData['externalPlayerId']);
        $hasPlayerRole = in_array($roomUser->role, [
            BingoRoomUser::ROLE_PLAYER,
            BingoRoomUser::ROLE_PLAYER_SELLER,
        ], true);
        $hasSellerRole = in_array($roomUser->role, [
            BingoRoomUser::ROLE_SELLER,
            BingoRoomUser::ROLE_PLAYER_SELLER,
        ], true);

        $hasPlayerRole = $hasPlayerRole || ! $isExternalSale;
        $hasSellerRole = $hasSellerRole || $isExternalSale;

        return match (true) {
            $hasPlayerRole && $hasSellerRole => BingoRoomUser::ROLE_PLAYER_SELLER,
            $hasSellerRole => BingoRoomUser::ROLE_SELLER,
            default => BingoRoomUser::ROLE_PLAYER,
        };
    }

    /**
     * Actualizar la cantidad de cartones de un usuario
     *
     * @param BingoRoomUser $roomUser        Usuario de la sala
     * @param int           $additionalCards Cantidad de cartones adicionales
     *
     * @return bool True si se actualizó correctamente
     */
    public function updateUserCards(BingoRoomUser $roomUser, int $additionalCards) : bool
    {
        try {
            return $roomUser->increment('cards_purchased', $additionalCards);
        } catch (Throwable $e) {
            Log::error('Error al actualizar cartones del usuario: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Validar los datos del usuario antes del registro
     *
     * @param array $userData Datos del usuario a validar
     *
     * @return bool True si los datos son válidos
     */
    public function validateUserData(array $userData) : bool
    {
        // Validar campos requeridos
        $requiredFields = ['roomId', 'creator', 'cardCount'];

        foreach ($requiredFields as $field) {
            if (! isset($userData[$field])) {
                Log::error("Campo requerido faltante: {$field}", ['userData' => $userData]);

                return false;
            }
        }

        // Validar que el creator tenga un ID
        if (! isset($userData['creator']['id'])) {
            Log::error('creator id no encontrado en userData', ['userData' => $userData]);

            return false;
        }

        // Validar que cardCount sea un número positivo
        if (! is_numeric($userData['cardCount']) || $userData['cardCount'] <= 0) {
            Log::error('cardCount debe ser un número positivo', ['cardCount' => $userData['cardCount']]);

            return false;
        }

        return true;
    }
}
