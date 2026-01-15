<?php

namespace App\Repositories\Games\Bingo\Rooms;

use App\Models\Bingo\Rooms\User\BingoRoomUser;
use App\Repositories\BaseRepository;
use App\Repositories\Games\Bingo\Rooms\Contracts\BingoRoomUserRepositoryContract;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository para manejar operaciones de usuarios de salas de bingo
 */
class BingoRoomUserRepository extends BaseRepository implements BingoRoomUserRepositoryContract
{
    /**
     * Constructor del repository
     *
     * @param BingoRoomUser $model
     */
    public function __construct(BingoRoomUser $model)
    {
        parent::__construct($model);
    }

    /**
     * Obtiene un jugador por sala, usuario creador y jugador
     *
     * @param mixed $roomId ID de la sala
     * @param mixed $creatorUserId ID del usuario creador
     * @param mixed $playerUserId ID del jugador
     * @param array $fields Campos a seleccionar
     * @return BingoRoomUser|null
     */
    public function getByPlayerUser($roomId, $creatorUserId, $playerUserId, array $fields = ['*']): ?BingoRoomUser
    {
        return $this->model->select($fields)
            ->where('room_id', $roomId)
            ->where('creator_user_id', $creatorUserId)
            ->where('player_user_id', $playerUserId)
            ->first();
    }

    /**
     * Obtiene un valor específico por jugador
     *
     * @param mixed $roomId ID de la sala
     * @param mixed $creatorUserId ID del usuario creador
     * @param mixed $playerUserId ID del jugador
     * @param string $field Campo a obtener
     * @return mixed
     */
    public function getValueByPlayerUser($roomId, $creatorUserId, $playerUserId, string $field = 'id')
    {
        return $this->model->where('room_id', $roomId)
            ->where('creator_user_id', $creatorUserId)
            ->where('player_user_id', $playerUserId)
            ->value($field);
    }

    /**
     * Cuenta jugadores por sala con condición
     *
     * @param mixed $roomId ID de la sala
     * @param array $condition Condición [operador, valor]
     * @return int
     */
    public function getPlayerCountByRoom($roomId, array $condition = ['!=', '']): int
    {
        if (!is_array($condition) || count($condition) !== 2) {
            throw new \InvalidArgumentException("La condición debe ser un arreglo con dos elementos: [operador, valor].");
        }

        [$operator, $value] = $condition;

        return $this->model->where('room_id', $roomId)
            ->where('winner', $operator, $value)
            ->count();
    }

    /**
     * Cuenta ganadores por sala
     *
     * @param mixed $roomId ID de la sala
     * @return int
     */
    public function getWinnersCountByRoom($roomId): int
    {
        return $this->model->where('room_id', $roomId)
            ->where('winner', 'Y')
            ->count();
    }

    /**
     * Cuenta no ganadores por sala
     *
     * @param mixed $roomId ID de la sala
     * @return int
     */
    public function getNonWinnersCountByRoom($roomId): int
    {
        return $this->model->where('room_id', $roomId)
            ->where('winner', 'N')
            ->count();
    }

    /**
     * Verifica si un jugador existe en una sala
     *
     * @param mixed $roomId ID de la sala
     * @param array $creator Array [campo, valor] del creador
     * @param array $player Array [campo, valor] del jugador
     * @return bool
     */
    public function existsPlayerInRoom($roomId, array $creator, array $player): bool
    {
        return $this->model->where('room_id', $roomId)
            ->where($creator[0], $creator[1])
            ->where($player[0], $player[1])
            ->exists();
    }

    /**
     * Obtiene jugadores por sala
     *
     * @param mixed $roomId ID de la sala
     * @return Collection
     */
    public function getByRoom($roomId): Collection
    {
        return $this->model->where('room_id', $roomId)->get();
    }

    /**
     * Obtiene ganadores por sala
     *
     * @param mixed $roomId ID de la sala
     * @return Collection
     */
    public function getWinnersByRoom($roomId): Collection
    {
        return $this->model->where('room_id', $roomId)
            ->where('winner', 'Y')
            ->get();
    }

    /**
     * Obtiene no ganadores por sala
     *
     * @param mixed $roomId ID de la sala
     * @return Collection
     */
    public function getNonWinnersByRoom($roomId): Collection
    {
        return $this->model->where('room_id', $roomId)
            ->where('winner', 'N')
            ->get();
    }

    /**
     * Obtiene jugadores por usuario creador
     *
     * @param mixed $creatorUserId ID del usuario creador
     * @return Collection
     */
    public function getByCreatorUser($creatorUserId): Collection
    {
        return $this->model->where('creator_user_id', $creatorUserId)->get();
    }

    /**
     * Actualiza estado de ganador
     *
     * @param mixed $id ID del jugador en sala
     * @param string $winner Estado ganador ('Y' o 'N')
     * @return bool
     */
    public function updateWinnerStatus($id, string $winner): bool
    {
        return $this->model->where('id', $id)
            ->update(['winner' => $winner]) > 0;
    }

    /**
     * Actualiza cantidad de cartones
     *
     * @param mixed $id ID del jugador en sala
     * @param int $ctns Cantidad de cartones
     * @return bool
     */
    public function updateCartonCount($id, int $ctns): bool
    {
        return $this->model->where('id', $id)
            ->update(['ctns' => $ctns]) > 0;
    }

    /**
     * Actualiza automarcador
     *
     * @param mixed $id ID del jugador en sala
     * @param float|null $automarker Valor del automarcador
     * @return bool
     */
    public function updateAutomarker($id, ?float $automarker): bool
    {
        return $this->model->where('id', $id)
            ->update(['automarker' => $automarker]) > 0;
    }
}