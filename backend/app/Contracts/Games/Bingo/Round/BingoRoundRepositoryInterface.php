<?php

namespace App\Repositories\Bingo\Round\Contracts;

use App\Contracts\BaseRepositoryInterface;
use App\Domains\Games\Bingo\Models\Round\BingoRound;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repository de rondas de bingo
 */
interface BingoRoundRepositoryContract extends BaseRepositoryInterface
{
    /**
     * Obtiene una ronda por sala y número de ronda
     *
     * @param mixed $roomId ID de la sala
     * @param int|null $round Número de ronda (opcional)
     * @param array $fields Campos a seleccionar
     * @return BingoRound|null
     */
    public function getByRoom($roomId, ?int $round = null, array $fields = ['*']): ?BingoRound;

    /**
     * Obtiene un valor específico por sala y ronda
     *
     * @param mixed $roomId ID de la sala
     * @param int $round Número de ronda
     * @param string $field Campo a obtener
     * @return mixed
     */
    public function getValueByRoom($roomId, int $round, string $field);

    /**
     * Obtiene la ronda en progreso de una sala
     *
     * @param mixed $roomId ID de la sala
     * @param array $fields Campos a seleccionar
     * @return BingoRound|null
     */
    public function getRoundInProgress($roomId, array $fields = ['*']): ?BingoRound;

    /**
     * Obtiene un valor de la ronda en progreso
     *
     * @param mixed $roomId ID de la sala
     * @param string $field Campo a obtener
     * @return mixed
     */
    public function getValueByRoundInProgress($roomId, string $field);

    /**
     * Verifica si existen rondas para una sala
     *
     * @param mixed $roomId ID de la sala
     * @return bool
     */
    public function existsRounds($roomId): bool;

    /**
     * Obtiene rondas por tipo de premio
     *
     * @param string $prizeType Tipo de premio
     * @return Collection
     */
    public function getByPrizeType(string $prizeType): Collection;

    /**
     * Obtiene rondas por patrón
     *
     * @param mixed $patternId ID del patrón
     * @return Collection
     */
    public function getByPattern($patternId): Collection;

    /**
     * Obtiene todas las rondas de una sala
     *
     * @param mixed $roomId ID de la sala
     * @return Collection
     */
    public function getAllByRoom($roomId): Collection;

    /**
     * Cuenta rondas por sala
     *
     * @param mixed $roomId ID de la sala
     * @return int
     */
    public function countByRoom($roomId): int;

    /**
     * Obtiene rondas activas
     *
     * @return Collection
     */
    public function getActiveRounds(): Collection;

    /**
     * Obtiene rondas finalizadas
     *
     * @return Collection
     */
    public function getFinishedRounds(): Collection;

    /**
     * Actualiza estado de una ronda
     *
     * @param mixed $id ID de la ronda
     * @param string $status Nuevo estado
     * @return bool
     */
    public function updateStatus($id, string $status): bool;

    /**
     * Actualiza la última ficha jugada
     *
     * @param mixed $id ID de la ronda
     * @param string $lastChip Última ficha
     * @return bool
     */
    public function updateLastChip($id, string $lastChip): bool;

    /**
     * Marca ronda como iniciada
     *
     * @param mixed $id ID de la ronda
     * @return bool
     */
    public function markAsStarted($id): bool;

    /**
     * Marca ronda como finalizada
     *
     * @param mixed $id ID de la ronda
     * @return bool
     */
    public function markAsFinished($id): bool;
}
