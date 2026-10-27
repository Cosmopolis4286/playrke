<?php

namespace App\Domains\Games\Bingo\Repositories\Room;

use App\Domains\Games\Bingo\Contracts\Room\User\BingoRoomUserRepositoryInterface;
use App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

/**
 * Repository para manejar operaciones de usuarios de salas de bingo
 */
class BingoRoomUserRepository extends BaseRepository implements BingoRoomUserRepositoryInterface
{
    /**
     * Constructor del repository
     */
    public function __construct(BingoRoomUser $model)
    {
        $this->model = $model;
    }

    /**
     * Verifica si un jugador existe en una sala
     *
     * @param mixed $roomId  ID de la sala
     * @param array $creator Array [campo, valor] del creador
     * @param array $player  Array [campo, valor] del jugador
     */
    public function existsPlayerInRoom($roomId, array $creator, array $player) : bool
    {
        return $this->model->where('room_id', $roomId)
            ->where($creator[0], $creator[1])
            ->where($player[0], $player[1])
            ->exists();
    }

    /**
     * Obtiene las filas de sala de un usuario (bingo_room_users no
     * distingue "creador" por columna propia, solo tiene user_id/role).
     *
     * @param mixed $creatorUserId ID del usuario (user_id)
     */
    public function getByCreatorUser($creatorUserId) : Collection
    {
        return $this->model->where('user_id', $creatorUserId)->get();
    }

    /**
     * Obtiene un jugador por sala y usuario.
     *
     * bingo_room_users no distingue "creador" vs "jugador" por columna
     * (solo tiene room_id/user_id/role), por lo que $creatorUserId no
     * se usa como filtro; se conserva en la firma por compatibilidad
     * con el contrato.
     *
     * @param mixed $roomId        ID de la sala
     * @param mixed $creatorUserId sin uso (ver nota arriba)
     * @param mixed $playerUserId  ID del jugador (user_id)
     * @param array $fields        Campos a seleccionar
     */
    public function getByPlayerUser($roomId, $creatorUserId, $playerUserId, array $fields = ['*']) : ?BingoRoomUser
    {
        return $this->model->select($fields)
            ->where('room_id', $roomId)
            ->where('user_id', $playerUserId)
            ->first();
    }

    /**
     * Obtiene jugadores por sala
     *
     * @param mixed $roomId ID de la sala
     */
    public function getByRoom($roomId) : Collection
    {
        return $this->model->where('room_id', $roomId)->get();
    }

    /**
     * Obtiene no ganadores por sala
     *
     * @param mixed $roomId ID de la sala
     */
    public function getNonWinnersByRoom($roomId) : Collection
    {
        return $this->model->where('room_id', $roomId)
            ->where('winner', 'N')
            ->get();
    }

    /**
     * Cuenta no ganadores por sala
     *
     * @param mixed $roomId ID de la sala
     */
    public function getNonWinnersCountByRoom($roomId) : int
    {
        return $this->model->where('room_id', $roomId)
            ->where('winner', 'N')
            ->count();
    }

    /**
     * Cuenta jugadores por sala con condición
     *
     * @param mixed $roomId    ID de la sala
     * @param array $condition Condición [operador, valor]
     */
    public function getPlayerCountByRoom($roomId, array $condition = ['!=', '']) : int
    {
        if (! is_array($condition) || count($condition) !== 2) {
            throw new InvalidArgumentException('La condición debe ser un arreglo con dos elementos: [operador, valor].');
        }

        [$operator, $value] = $condition;

        return $this->model->where('room_id', $roomId)
            ->where('winner', $operator, $value)
            ->count();
    }

    /**
     * Obtiene un valor específico por jugador.
     *
     * @param mixed  $roomId        ID de la sala
     * @param mixed  $creatorUserId sin uso (ver nota en getByPlayerUser)
     * @param mixed  $playerUserId  ID del jugador (user_id)
     * @param string $field         Campo a obtener
     *
     * @return mixed
     */
    public function getValueByPlayerUser($roomId, $creatorUserId, $playerUserId, string $field = 'id')
    {
        return $this->model->where('room_id', $roomId)
            ->where('user_id', $playerUserId)
            ->value($field);
    }

    /**
     * Obtiene ganadores por sala
     *
     * @param mixed $roomId ID de la sala
     */
    public function getWinnersByRoom($roomId) : Collection
    {
        return $this->model->where('room_id', $roomId)
            ->where('winner', 'Y')
            ->get();
    }

    /**
     * Cuenta ganadores por sala
     *
     * @param mixed $roomId ID de la sala
     */
    public function getWinnersCountByRoom($roomId) : int
    {
        return $this->model->where('room_id', $roomId)
            ->where('winner', 'Y')
            ->count();
    }

    /**
     * Actualiza automarcador
     *
     * @param mixed      $id         ID del jugador en sala
     * @param float|null $automarker Valor del automarcador
     */
    public function updateAutomarker($id, ?float $automarker) : bool
    {
        return $this->model->where('id', $id)
            ->update(['automarker' => $automarker]) > 0;
    }

    /**
     * Actualiza cantidad de cartones
     *
     * @param mixed $id        ID del jugador en sala
     * @param int   $cardCount Cantidad de cartones
     */
    public function updateCartonCount($id, int $cardCount) : bool
    {
        return $this->model->where('id', $id)
            ->update(['cards_purchased' => $cardCount]) > 0;
    }

    /**
     * Actualiza estado de ganador
     *
     * @param mixed  $id     ID del jugador en sala
     * @param string $winner Estado ganador ('Y' o 'N')
     */
    public function updateWinnerStatus($id, string $winner) : bool
    {
        return $this->model->where('id', $id)
            ->update(['winner' => $winner]) > 0;
    }
}
