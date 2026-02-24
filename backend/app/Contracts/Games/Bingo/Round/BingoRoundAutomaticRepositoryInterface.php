<?php

namespace App\Repositories\Bingo\Round\Contracts;

use App\Contracts\BaseRepositoryInterface;
use App\Models\Bingo\Round\BingoRoundAutomatic;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repository de automatic de rondas de bingo
 */
interface BingoRoundAutomaticRepositoryContract extends BaseRepositoryInterface
{
    /**
     * Obtiene draft por sala de bingo
     *
     * @param mixed $bingoRoomId ID de la sala de bingo
     * @param array $fields Campos a seleccionar
     * @return BingoRoundAutomatic|null
     */
    public function getByRoom($bingoRoomId, array $fields = ['*']): ?BingoRoundAutomatic;

    /**
     * Obtiene draft por sala automática de bingo
     *
     * @param mixed $automaticBingoRoomId ID de la sala automática
     * @param array $fields Campos a seleccionar
     * @return BingoRoundAutomatic|null
     */
    public function getByAutomaticRoom($automaticBingoRoomId, array $fields = ['*']): ?BingoRoundAutomatic;

    /**
     * Obtiene automatic activos
     *
     * @return Collection
     */
    public function getActiveAutomatics(): Collection;

    /**
     * Obtiene automatic ejecutados
     *
     * @return Collection
     */
    public function getExecutedAutomatics(): Collection;

    /**
     * Actualiza estado del draft
     *
     * @param mixed $id ID del draft
     * @param string $status Nuevo estado
     * @return bool
     */
    public function updateStatus($id, string $status): bool;

    /**
     * Marca draft como ejecutado
     *
     * @param mixed $id ID del draft
     * @return bool
     */
    public function markAsExecuted($id): bool;

    /**
     * Actualiza configuración de rondas
     *
     * @param mixed $id ID del draft
     * @param array $data Datos a actualizar
     * @return bool
     */
    public function updateRoundsConfig($id, array $data): bool;

    /**
     * Verifica si existe draft para una sala
     *
     * @param mixed $bingoRoomId ID de la sala
     * @return bool
     */
    public function existsForRoom($bingoRoomId): bool;

    /**
     * Verifica si existe draft para una sala automática
     *
     * @param mixed $automaticBingoRoomId ID de la sala automática
     * @return bool
     */
    public function existsForAutomaticRoom($automaticBingoRoomId): bool;
}
