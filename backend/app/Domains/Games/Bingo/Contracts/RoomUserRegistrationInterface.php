<?php

namespace App\Domains\Games\Bingo\Contracts;

use App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser;

/**
 * Interfaz para el servicio de registro de usuarios en salas de bingo
 */
interface RoomUserRegistrationInterface
{
    /**
     * Crear un nuevo registro de usuario en la sala
     *
     * @param array $userData Datos del usuario
     */
    public function createRoomUser(array $userData) : ?BingoRoomUser;

    /**
     * Buscar un usuario existente en la sala
     *
     * @param string|null $roomUserId ID del usuario en la sala
     */
    public function findRoomUser(?string $roomUserId) : ?BingoRoomUser;

    /**
     * Registrar un nuevo usuario en la sala o actualizar uno existente
     *
     * @param array $userData datos del usuario incluyendo creator, roomId, cardCount, etc
     *
     * @return array{id: string, isNew: bool}|null Resultado con ID e indicador de nuevo usuario, o null si falla
     */
    public function registerOrUpdateRoomUser(array $userData) : ?array;

    /**
     * Actualizar la cantidad de cartones de un usuario
     *
     * @param BingoRoomUser $roomUser        Usuario de la sala
     * @param int           $additionalCards Cantidad de cartones adicionales
     *
     * @return bool True si se actualizó correctamente
     */
    public function updateUserCards(BingoRoomUser $roomUser, int $additionalCards) : bool;

    /**
     * Validar los datos del usuario antes del registro
     *
     * @param array $userData Datos del usuario a validar
     *
     * @return bool True si los datos son válidos
     */
    public function validateUserData(array $userData) : bool;
}
