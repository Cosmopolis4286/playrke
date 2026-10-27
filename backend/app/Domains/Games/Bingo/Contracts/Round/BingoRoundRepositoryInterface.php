<?php

namespace App\Domains\Games\Bingo\Contracts\Round;

use App\Contracts\BaseRepositoryInterface;
use App\Domains\Games\Bingo\Models\Round\BingoRound;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repository de rondas de bingo
 */
interface BingoRoundRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Cuenta rondas por sala
     *
     * @param mixed $roomId ID de la sala
     */
    public function countByRoom($roomId) : int;

    /**
     * Verifica si existen rondas para una sala
     *
     * @param mixed $roomId ID de la sala
     */
    public function existsRounds($roomId) : bool;

    /**
     * Obtiene rondas activas
     */
    public function getActiveRounds() : Collection;

    /**
     * Obtiene todas las rondas de una sala
     *
     * @param mixed $roomId ID de la sala
     */
    public function getAllByRoom($roomId) : Collection;

    /**
     * Obtiene rondas por patrón
     *
     * @param mixed $patternId ID del patrón
     */
    public function getByPattern($patternId) : Collection;

    /**
     * Obtiene rondas por tipo de premio
     *
     * @param string $prizeType Tipo de premio
     */
    public function getByPrizeType(string $prizeType) : Collection;

    /**
     * Obtiene una ronda por sala y número de ronda
     *
     * @param mixed    $roomId ID de la sala
     * @param int|null $round  Número de ronda (opcional)
     * @param array    $fields Campos a seleccionar
     */
    public function getByRoom($roomId, ?int $round = null, array $fields = ['*']) : ?BingoRound;

    /**
     * Obtiene rondas finalizadas
     */
    public function getFinishedRounds() : Collection;

    /**
     * Obtiene la ronda en progreso de una sala
     *
     * @param mixed $roomId ID de la sala
     * @param array $fields Campos a seleccionar
     */
    public function getRoundInProgress($roomId, array $fields = ['*']) : ?BingoRound;

    /**
     * Obtiene un valor específico por sala y ronda
     *
     * @param mixed  $roomId ID de la sala
     * @param int    $round  Número de ronda
     * @param string $field  Campo a obtener
     *
     * @return mixed
     */
    public function getValueByRoom($roomId, int $round, string $field);

    /**
     * Obtiene un valor de la ronda en progreso
     *
     * @param mixed  $roomId ID de la sala
     * @param string $field  Campo a obtener
     *
     * @return mixed
     */
    public function getValueByRoundInProgress($roomId, string $field);

    /**
     * Marca ronda como finalizada
     *
     * @param mixed $id ID de la ronda
     */
    public function markAsFinished($id) : bool;

    /**
     * Marca ronda como iniciada
     *
     * @param mixed $id ID de la ronda
     */
    public function markAsStarted($id) : bool;

    /**
     * Actualiza la última ficha jugada
     *
     * @param mixed  $id       ID de la ronda
     * @param string $lastChip Última ficha
     */
    public function updateLastChip($id, string $lastChip) : bool;

    /**
     * Actualiza estado de una ronda
     *
     * @param mixed  $id     ID de la ronda
     * @param string $status Nuevo estado
     */
    public function updateStatus($id, string $status) : bool;
}
