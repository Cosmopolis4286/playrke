<?php

namespace App\Services\Games\Room;

class RoomTypeService
{
    /**
     * Lista de tipos de sala válidos.
     *
     * @var array
     */
    protected array $validRoomTypes = [
        'automatic-rooms',
        'private-rooms',
        'public-rooms',
        'other-public-rooms',
    ];

    /**
     * Verifica si un tipo de sala es válido.
     *
     * @param string $roomType
     * @return bool
     */
    public function isValid(string $roomType): bool
    {
        return in_array($roomType, $this->validRoomTypes, true);
    }

    /**
     * Obtiene todos los tipos de sala válidos.
     *
     * @return array
     */
    public function getValidRoomTypes(): array
    {
        return $this->validRoomTypes;
    }
}
