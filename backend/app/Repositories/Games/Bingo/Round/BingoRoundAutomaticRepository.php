<?php

namespace App\Repositories\Bingo\Round;

use App\Models\Bingo\Round\BingoRoundAutomatic;
use App\Repositories\BaseRepository;
use App\Repositories\Bingo\Round\Contracts\BingoRoundAutomaticRepositoryContract;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository para manejar operaciones de automatic de rondas de bingo
 */
class BingoRoundAutomaticRepository extends BaseRepository implements BingoRoundAutomaticRepositoryContract
{
    /**
     * Constructor del repository
     *
     * @param BingoRoundAutomatic $model
     */
    public function __construct(BingoRoundAutomatic $model)
    {
        $this->model = $model;
    }

    /**
     * Obtiene draft por sala de bingo
     *
     * @param mixed $bingoRoomId ID de la sala de bingo
     * @param array $fields Campos a seleccionar
     * @return BingoRoundAutomatic|null
     */
    public function getByRoom($bingoRoomId, array $fields = ['*']): ?BingoRoundAutomatic
    {
        return $this->model->select($fields)
            ->where('bingo_room_id', $bingoRoomId)
            ->first();
    }

    /**
     * Obtiene draft por sala automática de bingo
     *
     * @param mixed $automaticBingoRoomId ID de la sala automática
     * @param array $fields Campos a seleccionar
     * @return BingoRoundAutomatic|null
     */
    public function getByAutomaticRoom($automaticBingoRoomId, array $fields = ['*']): ?BingoRoundAutomatic
    {
        return $this->model->select($fields)
            ->where('automatic_bingo_room_id', $automaticBingoRoomId)
            ->first();
    }

    /**
     * Obtiene automatic activos
     *
     * @return Collection
     */
    public function getActiveAutomatics(): Collection
    {
        return $this->model->where('status', 'ACTIVE')->get();
    }

    /**
     * Obtiene automatic ejecutados
     *
     * @return Collection
     */
    public function getExecutedAutomatics(): Collection
    {
        return $this->model->where('status', 'EXECUTED')->get();
    }

    /**
     * Actualiza estado del draft
     *
     * @param mixed $id ID del draft
     * @param string $status Nuevo estado
     * @return bool
     */
    public function updateStatus($id, string $status): bool
    {
        return $this->model->where('id', $id)
            ->update(['status' => $status]) > 0;
    }

    /**
     * Marca draft como ejecutado
     *
     * @param mixed $id ID del draft
     * @return bool
     */
    public function markAsExecuted($id): bool
    {
        return $this->updateStatus($id, 'EXECUTED');
    }

    /**
     * Actualiza configuración de rondas
     *
     * @param mixed $id ID del draft
     * @param array $data Datos a actualizar
     * @return bool
     */
    public function updateRoundsConfig($id, array $data): bool
    {
        $allowedFields = ['rounds', 'prize_types', 'bingo_patterns', 'prize_values'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            return false;
        }

        return $this->model->where('id', $id)
            ->update($updateData) > 0;
    }

    /**
     * Verifica si existe draft para una sala
     *
     * @param mixed $bingoRoomId ID de la sala
     * @return bool
     */
    public function existsForRoom($bingoRoomId): bool
    {
        return $this->model->where('bingo_room_id', $bingoRoomId)->exists();
    }

    /**
     * Verifica si existe draft para una sala automática
     *
     * @param mixed $automaticBingoRoomId ID de la sala automática
     * @return bool
     */
    public function existsForAutomaticRoom($automaticBingoRoomId): bool
    {
        return $this->model->where('automatic_bingo_room_id', $automaticBingoRoomId)->exists();
    }
}
