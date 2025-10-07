<?php

namespace App\Repositories\Games\Bingo\Rounds\Contracts;

use App\Repositories\Base\Contracts\BaseRepositoryContract;
use App\Models\Games\Bingo\Rounds\BingoRoundDraft;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repository de drafts de rondas de bingo
 */
interface BingoRoundDraftRepositoryContract extends BaseRepositoryContract
{
    /**
     * Obtiene draft por sala de bingo
     *
     * @param mixed $bingoRoomId ID de la sala de bingo
     * @param array $fields Campos a seleccionar
     * @return BingoRoundDraft|null
     */
    public function getByRoom($bingoRoomId, array $fields = ['*']): ?BingoRoundDraft;

    /**
     * Obtiene draft por sala automática de bingo
     *
     * @param mixed $automaticBingoRoomId ID de la sala automática
     * @param array $fields Campos a seleccionar
     * @return BingoRoundDraft|null
     */
    public function getByAutomaticRoom($automaticBingoRoomId, array $fields = ['*']): ?BingoRoundDraft;

    /**
     * Obtiene drafts por estado
     *
     * @param string $status Estado del draft
     * @return Collection
     */
    public function getByStatus(string $status): Collection;

    /**
     * Obtiene drafts activos
     *
     * @return Collection
     */
    public function getActiveDrafts(): Collection;

    /**
     * Obtiene drafts ejecutados
     *
     * @return Collection
     */
    public function getExecutedDrafts(): Collection;

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