<?php

namespace App\Repositories\Games\Bingo\Rooms\Contracts;

use App\Contracts\BaseRepositoryInterface;
use App\Models\Bingo\Rooms\User\BingoRoomUser;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repository de usuarios de salas de bingo
 */
interface BingoRoomUserRepositoryContract extends BaseRepositoryInterface
{
    /**
     * Obtiene un jugador por sala, usuario creador y jugador
     *
     * @param mixed $roomId ID de la sala
     * @param mixed $creatorUserId ID del usuario creador
     * @param mixed $playerUserId ID del jugador
     * @param array $fields Campos a seleccionar
     * @return BingoRoomUser|null
     */
    public function getByPlayerUser($roomId, $creatorUserId, $playerUserId, array $fields = ['*']): ?BingoRoomUser;

    /**
     * Obtiene un valor específico por jugador
     *
     * @param mixed $roomId ID de la sala
     * @param mixed $creatorUserId ID del usuario creador
     * @param mixed $playerUserId ID del jugador
     * @param string $field Campo a obtener
     * @return mixed
     */
    public function getValueByPlayerUser($roomId, $creatorUserId, $playerUserId, string $field = 'id');

    /**
     * Cuenta jugadores por sala con condición
     *
     * @param mixed $roomId ID de la sala
     * @param array $condition Condición [operador, valor]
     * @return int
     */
    public function getPlayerCountByRoom($roomId, array $condition = ['!=', '']): int;

    /**
     * Cuenta ganadores por sala
     *
     * @param mixed $roomId ID de la sala
     * @return int
     */
    public function getWinnersCountByRoom($roomId): int;

    /**
     * Cuenta no ganadores por sala
     *
     * @param mixed $roomId ID de la sala
     * @return int
     */
    public function getNonWinnersCountByRoom($roomId): int;

    /**
     * Verifica si un jugador existe en una sala
     *
     * @param mixed $roomId ID de la sala
     * @param array $creator Array [campo, valor] del creador
     * @param array $player Array [campo, valor] del jugador
     * @return bool
     */
    public function existsPlayerInRoom($roomId, array $creator, array $player): bool;

    /**
     * Obtiene jugadores por sala
     *
     * @param mixed $roomId ID de la sala
     * @return Collection
     */
    public function getByRoom($roomId): Collection;

    /**
     * Obtiene ganadores por sala
     *
     * @param mixed $roomId ID de la sala
     * @return Collection
     */
    public function getWinnersByRoom($roomId): Collection;

    /**
     * Obtiene no ganadores por sala
     *
     * @param mixed $roomId ID de la sala
     * @return Collection
     */
    public function getNonWinnersByRoom($roomId): Collection;

    /**
     * Obtiene jugadores por usuario creador
     *
     * @param mixed $creatorUserId ID del usuario creador
     * @return Collection
     */
    public function getByCreatorUser($creatorUserId): Collection;

    /**
     * Actualiza estado de ganador
     *
     * @param mixed $id ID del jugador en sala
     * @param string $winner Estado ganador ('Y' o 'N')
     * @return bool
     */
    public function updateWinnerStatus($id, string $winner): bool;

    /**
     * Actualiza cantidad de cartones
     *
     * @param mixed $id ID del jugador en sala
     * @param int $ctns Cantidad de cartones
     * @return bool
     */
    public function updateCartonCount($id, int $ctns): bool;

    /**
     * Actualiza automarcador
     *
     * @param mixed $id ID del jugador en sala
     * @param float|null $automarker Valor del automarcador
     * @return bool
     */
    public function updateAutomarker($id, ?float $automarker): bool;
}