<?php

namespace App\Domains\Games\Bingo\Contracts\Room\User;

use App\Contracts\BaseRepositoryInterface;
use App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repository de usuarios de salas de bingo
 */
interface BingoRoomUserRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Verifica si un jugador existe en una sala
     *
     * @param mixed $roomId  ID de la sala
     * @param array $creator Array [campo, valor] del creador
     * @param array $player  Array [campo, valor] del jugador
     */
    public function existsPlayerInRoom($roomId, array $creator, array $player) : bool;

    /**
     * Obtiene jugadores por usuario creador
     *
     * @param mixed $creatorUserId ID del usuario creador
     */
    public function getByCreatorUser($creatorUserId) : Collection;

    /**
     * Obtiene un jugador por sala, usuario creador y jugador
     *
     * @param mixed $roomId        ID de la sala
     * @param mixed $creatorUserId ID del usuario creador
     * @param mixed $playerUserId  ID del jugador
     * @param array $fields        Campos a seleccionar
     */
    public function getByPlayerUser($roomId, $creatorUserId, $playerUserId, array $fields = ['*']) : ?BingoRoomUser;

    /**
     * Obtiene jugadores por sala
     *
     * @param mixed $roomId ID de la sala
     */
    public function getByRoom($roomId) : Collection;

    /**
     * Obtiene no ganadores por sala
     *
     * @param mixed $roomId ID de la sala
     */
    public function getNonWinnersByRoom($roomId) : Collection;

    /**
     * Cuenta no ganadores por sala
     *
     * @param mixed $roomId ID de la sala
     */
    public function getNonWinnersCountByRoom($roomId) : int;

    /**
     * Cuenta jugadores por sala con condición
     *
     * @param mixed $roomId    ID de la sala
     * @param array $condition Condición [operador, valor]
     */
    public function getPlayerCountByRoom($roomId, array $condition = ['!=', '']) : int;

    /**
     * Obtiene un valor específico por jugador
     *
     * @param mixed  $roomId        ID de la sala
     * @param mixed  $creatorUserId ID del usuario creador
     * @param mixed  $playerUserId  ID del jugador
     * @param string $field         Campo a obtener
     *
     * @return mixed
     */
    public function getValueByPlayerUser($roomId, $creatorUserId, $playerUserId, string $field = 'id');

    /**
     * Obtiene ganadores por sala
     *
     * @param mixed $roomId ID de la sala
     */
    public function getWinnersByRoom($roomId) : Collection;

    /**
     * Cuenta ganadores por sala
     *
     * @param mixed $roomId ID de la sala
     */
    public function getWinnersCountByRoom($roomId) : int;

    /**
     * Actualiza automarcador
     *
     * @param mixed      $id         ID del jugador en sala
     * @param float|null $automarker Valor del automarcador
     */
    public function updateAutomarker($id, ?float $automarker) : bool;

    /**
     * Actualiza cantidad de cartones
     *
     * @param mixed $id        ID del jugador en sala
     * @param int   $cardCount Cantidad de cartones
     */
    public function updateCartonCount($id, int $cardCount) : bool;

    /**
     * Actualiza estado de ganador
     *
     * @param mixed  $id     ID del jugador en sala
     * @param string $winner Estado ganador ('Y' o 'N')
     */
    public function updateWinnerStatus($id, string $winner) : bool;
}
