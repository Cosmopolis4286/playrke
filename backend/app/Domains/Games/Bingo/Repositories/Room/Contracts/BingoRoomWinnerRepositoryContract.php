<?php

namespace App\Domains\Games\Bingo\Repositories\Room\Contracts;

use App\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repository de ganadores de salas de bingo
 */
interface BingoRoomWinnerRepositoryContract extends BaseRepositoryInterface
{
    /**
     * Cuenta ganadores por sala
     *
     * @param mixed $roomId ID de la sala
     */
    public function countByRoom($roomId) : int;

    /**
     * Cuenta ganadores por ronda
     *
     * @param mixed $roundId ID de la ronda
     */
    public function countByRound($roundId) : int;

    /**
     * Cuenta ganadores por estado
     *
     * @param string $awardStatus Estado del premio
     */
    public function countByStatus(string $awardStatus) : int;

    /**
     * Obtiene ganadores por estado de pago
     *
     * @param string $awardStatus Estado del premio ('WAITING', 'PAID')
     */
    public function getByAwardStatus(string $awardStatus) : Collection;

    /**
     * Obtiene ganadores por sala
     *
     * @param mixed $roomId ID de la sala
     */
    public function getByRoom($roomId) : Collection;

    /**
     * Obtiene ganadores por sala y estado
     *
     * @param mixed  $roomId      ID de la sala
     * @param string $awardStatus Estado del premio
     */
    public function getByRoomAndStatus($roomId, string $awardStatus) : Collection;

    /**
     * Obtiene ganadores por jugador (room_user)
     *
     * @param mixed $roomUserId ID del jugador en sala
     */
    public function getByRoomUser($roomUserId) : Collection;

    /**
     * Obtiene ganadores por ronda
     *
     * @param mixed $roundId ID de la ronda
     */
    public function getByRound($roundId) : Collection;

    /**
     * Obtiene ganadores por ronda y estado
     *
     * @param mixed  $roundId     ID de la ronda
     * @param string $awardStatus Estado del premio
     */
    public function getByRoundAndStatus($roundId, string $awardStatus) : Collection;

    /**
     * Verifica si un jugador ya ganó en una ronda
     *
     * @param mixed $roomUserId ID del jugador en sala
     * @param mixed $roundId    ID de la ronda
     */
    public function hasWonInRound($roomUserId, $roundId) : bool;

    /**
     * Marca múltiples ganadores como pagados
     *
     * @param array $ids Array de IDs de ganadores
     *
     * @return int Número de registros actualizados
     */
    public function markAsPaid(array $ids) : int;

    /**
     * Actualiza estado de premio
     *
     * @param mixed  $id          ID del ganador
     * @param string $awardStatus Nuevo estado
     */
    public function updateAwardStatus($id, string $awardStatus) : bool;
}
