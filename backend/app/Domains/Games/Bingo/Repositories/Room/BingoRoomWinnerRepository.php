<?php

namespace App\Domains\Games\Bingo\Repositories\Room;

use App\Domains\Games\Bingo\Models\Room\User\BingoRoomWinner;
use App\Domains\Games\Bingo\Repositories\Room\Contracts\BingoRoomWinnerRepositoryContract;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository para manejar operaciones de ganadores de salas de bingo
 */
class BingoRoomWinnerRepository extends BaseRepository implements BingoRoomWinnerRepositoryContract
{
    /**
     * Constructor del repository
     */
    public function __construct(BingoRoomWinner $model)
    {
        $this->model = $model;
    }

    /**
     * Cuenta ganadores por sala
     *
     * @param mixed $roomId ID de la sala
     */
    public function countByRoom($roomId) : int
    {
        return $this->model->where('room_id', $roomId)->count();
    }

    /**
     * Cuenta ganadores por ronda
     *
     * @param mixed $roundId ID de la ronda
     */
    public function countByRound($roundId) : int
    {
        return $this->model->where('round_id', $roundId)->count();
    }

    /**
     * Cuenta ganadores por estado
     *
     * @param string $awardStatus Estado del premio
     */
    public function countByStatus(string $awardStatus) : int
    {
        return $this->model->where('award_status', $awardStatus)->count();
    }

    /**
     * Obtiene ganadores por estado de pago
     *
     * @param string $awardStatus Estado del premio ('WAITING', 'PAID')
     */
    public function getByAwardStatus(string $awardStatus) : Collection
    {
        return $this->model->where('award_status', $awardStatus)->get();
    }

    /**
     * Obtiene ganadores por sala
     *
     * @param mixed $roomId ID de la sala
     */
    public function getByRoom($roomId) : Collection
    {
        return $this->model->where('room_id', $roomId)->get();
    }

    /**
     * Obtiene ganadores por sala y estado
     *
     * @param mixed  $roomId      ID de la sala
     * @param string $awardStatus Estado del premio
     */
    public function getByRoomAndStatus($roomId, string $awardStatus) : Collection
    {
        return $this->model->where('room_id', $roomId)
            ->where('award_status', $awardStatus)
            ->get();
    }

    /**
     * Obtiene ganadores por jugador (room_user)
     *
     * @param mixed $roomUserId ID del jugador en sala
     */
    public function getByRoomUser($roomUserId) : Collection
    {
        return $this->model->where('room_user_id', $roomUserId)->get();
    }

    /**
     * Obtiene ganadores por ronda
     *
     * @param mixed $roundId ID de la ronda
     */
    public function getByRound($roundId) : Collection
    {
        return $this->model->where('round_id', $roundId)->get();
    }

    /**
     * Obtiene ganadores por ronda y estado
     *
     * @param mixed  $roundId     ID de la ronda
     * @param string $awardStatus Estado del premio
     */
    public function getByRoundAndStatus($roundId, string $awardStatus) : Collection
    {
        return $this->model->where('round_id', $roundId)
            ->where('award_status', $awardStatus)
            ->get();
    }

    /**
     * Verifica si un jugador ya ganó en una ronda
     *
     * @param mixed $roomUserId ID del jugador en sala
     * @param mixed $roundId    ID de la ronda
     */
    public function hasWonInRound($roomUserId, $roundId) : bool
    {
        return $this->model->where('room_user_id', $roomUserId)
            ->where('round_id', $roundId)
            ->exists();
    }

    /**
     * Marca múltiples ganadores como pagados
     *
     * @param array $ids Array de IDs de ganadores
     *
     * @return int Número de registros actualizados
     */
    public function markAsPaid(array $ids) : int
    {
        return $this->model->whereIn('id', $ids)
            ->update(['award_status' => 'PAID']);
    }

    /**
     * Actualiza estado de premio
     *
     * @param mixed  $id          ID del ganador
     * @param string $awardStatus Nuevo estado
     */
    public function updateAwardStatus($id, string $awardStatus) : bool
    {
        return $this->model->where('id', $id)
            ->update(['award_status' => $awardStatus]) > 0;
    }
}
