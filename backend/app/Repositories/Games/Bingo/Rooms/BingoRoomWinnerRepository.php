<?php

namespace App\Repositories\Games\Bingo\Rooms;

use App\Models\Bingo\Rooms\User\BingoRoomWinner;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Games\Bingo\Rooms\Contracts\BingoRoomWinnerRepositoryContract;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository para manejar operaciones de ganadores de salas de bingo
 */
class BingoRoomWinnerRepository extends BaseRepository implements BingoRoomWinnerRepositoryContract
{
    /**
     * Constructor del repository
     *
     * @param BingoRoomWinner $model
     */
    public function __construct(BingoRoomWinner $model)
    {
        parent::__construct($model);
    }

    /**
     * Obtiene ganadores por sala
     *
     * @param mixed $roomId ID de la sala
     * @return Collection
     */
    public function getByRoom($roomId): Collection
    {
        return $this->model->where('room_id', $roomId)->get();
    }

    /**
     * Obtiene ganadores por ronda
     *
     * @param mixed $roundId ID de la ronda
     * @return Collection
     */
    public function getByRound($roundId): Collection
    {
        return $this->model->where('round_id', $roundId)->get();
    }

    /**
     * Obtiene ganadores por jugador (room_user)
     *
     * @param mixed $roomUserId ID del jugador en sala
     * @return Collection
     */
    public function getByRoomUser($roomUserId): Collection
    {
        return $this->model->where('room_user_id', $roomUserId)->get();
    }

    /**
     * Obtiene ganadores por estado de pago
     *
     * @param string $awardStatus Estado del premio ('WAITING', 'PAID')
     * @return Collection
     */
    public function getByAwardStatus(string $awardStatus): Collection
    {
        return $this->model->where('award_status', $awardStatus)->get();
    }

    /**
     * Obtiene ganadores por sala y estado
     *
     * @param mixed $roomId ID de la sala
     * @param string $awardStatus Estado del premio
     * @return Collection
     */
    public function getByRoomAndStatus($roomId, string $awardStatus): Collection
    {
        return $this->model->where('room_id', $roomId)
            ->where('award_status', $awardStatus)
            ->get();
    }

    /**
     * Obtiene ganadores por ronda y estado
     *
     * @param mixed $roundId ID de la ronda
     * @param string $awardStatus Estado del premio
     * @return Collection
     */
    public function getByRoundAndStatus($roundId, string $awardStatus): Collection
    {
        return $this->model->where('round_id', $roundId)
            ->where('award_status', $awardStatus)
            ->get();
    }

    /**
     * Cuenta ganadores por sala
     *
     * @param mixed $roomId ID de la sala
     * @return int
     */
    public function countByRoom($roomId): int
    {
        return $this->model->where('room_id', $roomId)->count();
    }

    /**
     * Cuenta ganadores por ronda
     *
     * @param mixed $roundId ID de la ronda
     * @return int
     */
    public function countByRound($roundId): int
    {
        return $this->model->where('round_id', $roundId)->count();
    }

    /**
     * Cuenta ganadores por estado
     *
     * @param string $awardStatus Estado del premio
     * @return int
     */
    public function countByStatus(string $awardStatus): int
    {
        return $this->model->where('award_status', $awardStatus)->count();
    }

    /**
     * Actualiza estado de premio
     *
     * @param mixed $id ID del ganador
     * @param string $awardStatus Nuevo estado
     * @return bool
     */
    public function updateAwardStatus($id, string $awardStatus): bool
    {
        return $this->model->where('id', $id)
            ->update(['award_status' => $awardStatus]) > 0;
    }

    /**
     * Marca múltiples ganadores como pagados
     *
     * @param array $ids Array de IDs de ganadores
     * @return int Número de registros actualizados
     */
    public function markAsPaid(array $ids): int
    {
        return $this->model->whereIn('id', $ids)
            ->update(['award_status' => 'PAID']);
    }

    /**
     * Verifica si un jugador ya ganó en una ronda
     *
     * @param mixed $roomUserId ID del jugador en sala
     * @param mixed $roundId ID de la ronda
     * @return bool
     */
    public function hasWonInRound($roomUserId, $roundId): bool
    {
        return $this->model->where('room_user_id', $roomUserId)
            ->where('round_id', $roundId)
            ->exists();
    }
}