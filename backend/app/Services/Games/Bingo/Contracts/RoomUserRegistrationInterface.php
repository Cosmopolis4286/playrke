<?php

namespace App\Services\Games\Bingo\Contracts;

use App\Models\Games\Bingo\Room\BingoRoom;
use App\Models\Games\Bingo\Room\User\BingoRoomUser;

/**
 * Interfaz para el servicio de registro de usuarios en salas de bingo
 */
interface RoomUserRegistrationInterface
{
    /**
     * Registrar un nuevo usuario en la sala o actualizar uno existente
     *
     * @param array $userData Datos del usuario incluyendo creator, roomId, numOfCtns, etc.
     * @return array{id: string, isNew: bool}|null Resultado con ID e indicador de nuevo usuario, o null si falla
     */
    public function registerOrUpdateRoomUser(array $userData): ?array;

    /**
     * Validar los datos del usuario antes del registro
     *
     * @param array $userData Datos del usuario a validar
     * @return bool True si los datos son válidos
     */
    public function validateUserData(array $userData): bool;

    /**
     * Buscar un usuario existente en la sala
     *
     * @param string|null $roomUserId ID del usuario en la sala
     * @return BingoRoomUser|null
     */
    public function findRoomUser(?string $roomUserId): ?BingoRoomUser;

    /**
     * Crear un nuevo registro de usuario en la sala
     *
     * @param array $userData Datos del usuario
     * @return BingoRoomUser|null
     */
    public function createRoomUser(array $userData): ?BingoRoomUser;

    /**
     * Actualizar la cantidad de cartones de un usuario
     *
     * @param BingoRoomUser $roomUser Usuario de la sala
     * @param int $additionalCards Cantidad de cartones adicionales
     * @return bool True si se actualizó correctamente
     */
    public function updateUserCards(BingoRoomUser $roomUser, int $additionalCards): bool;
}
