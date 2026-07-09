<?php

namespace App\Domains\Games\Bingo\Repositories\Round;

use App\Domains\Games\Bingo\Models\Round\BingoRound;
use App\Repositories\BaseRepository;
use App\Domains\Games\Bingo\Contracts\Round\BingoRoundRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository para manejar operaciones de rondas de bingo
 */
class BingoRoundRepository extends BaseRepository implements BingoRoundRepositoryInterface
{
    /**
     * Constructor del repository
     *
     * @param BingoRound $model
     */
    public function __construct(BingoRound $model)
    {
        $this->model = $model;
    }

    /**
     * Obtiene una ronda por sala y número de ronda
     *
     * @param mixed $roomId ID de la sala
     * @param int|null $round Número de ronda (opcional)
     * @param array $fields Campos a seleccionar
     * @return BingoRound|null
     */
    public function getByRoom($roomId, ?int $round = null, array $fields = ['*']): ?BingoRound
    {
        $query = $this->model->select($fields)
            ->where('room_id', $roomId);

        if ($round !== null) {
            $query->where('round', $round);
        }

        return $query->first();
    }

    /**
     * Obtiene un valor específico por sala y ronda
     *
     * @param mixed $roomId ID de la sala
     * @param int $round Número de ronda
     * @param string $field Campo a obtener
     * @return mixed
     */
    public function getValueByRoom($roomId, int $round, string $field)
    {
        return $this->model->where('room_id', $roomId)
            ->where('round', $round)
            ->value($field);
    }

    /**
     * Obtiene la ronda en progreso de una sala
     *
     * @param mixed $roomId ID de la sala
     * @param array $fields Campos a seleccionar
     * @return BingoRound|null
     */
    public function getRoundInProgress($roomId, array $fields = ['*']): ?BingoRound
    {
        return $this->model->select($fields)
            ->where('room_id', $roomId)
            ->whereIn('status', ['IN_PROGRESS', 'FINISHED'])
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Obtiene un valor de la ronda en progreso
     *
     * @param mixed $roomId ID de la sala
     * @param string $field Campo a obtener
     * @return mixed
     */
    public function getValueByRoundInProgress($roomId, string $field)
    {
        return $this->model->where('room_id', $roomId)
            ->whereIn('status', ['IN_PROGRESS', 'FINISHED'])
            ->orderByDesc('id')
            ->value($field);
    }

    /**
     * Verifica si existen rondas para una sala
     *
     * @param mixed $roomId ID de la sala
     * @return bool
     */
    public function existsRounds($roomId): bool
    {
        return $this->model->where('room_id', $roomId)->exists();
    }

    /**
     * Obtiene rondas por tipo de premio
     *
     * @param string $prizeType Tipo de premio
     * @return Collection
     */
    public function getByPrizeType(string $prizeType): Collection
    {
        return $this->model->where('prize_type', $prizeType)->get();
    }

    /**
     * Obtiene rondas por patrón
     *
     * @param mixed $patternId ID del patrón
     * @return Collection
     */
    public function getByPattern($patternId): Collection
    {
        return $this->model->where('bingo_pattern_id', $patternId)->get();
    }

    /**
     * Obtiene todas las rondas de una sala
     *
     * @param mixed $roomId ID de la sala
     * @return Collection
     */
    public function getAllByRoom($roomId): Collection
    {
        return $this->model->where('room_id', $roomId)->get();
    }

    /**
     * Cuenta rondas por sala
     *
     * @param mixed $roomId ID de la sala
     * @return int
     */
    public function countByRoom($roomId): int
    {
        return $this->model->where('room_id', $roomId)->count();
    }

    /**
     * Obtiene rondas activas
     *
     * @return Collection
     */
    public function getActiveRounds(): Collection
    {
        return $this->model->where('status', 'ACTIVE')->get();
    }

    /**
     * Obtiene rondas finalizadas
     *
     * @return Collection
     */
    public function getFinishedRounds(): Collection
    {
        return $this->model->where('status', 'FINISHED')->get();
    }

    /**
     * Actualiza estado de una ronda
     *
     * @param mixed $id ID de la ronda
     * @param string $status Nuevo estado
     * @return bool
     */
    public function updateStatus($id, string $status): bool
    {
        return $this->model->where('id', $id)
            ->update(['status' => $status]) > 0;
    }

    /**
     * Actualiza la última ficha jugada
     *
     * @param mixed $id ID de la ronda
     * @param string $lastChip Última ficha
     * @return bool
     */
    public function updateLastChip($id, string $lastChip): bool
    {
        return $this->model->where('id', $id)
            ->update(['last_chip' => $lastChip]) > 0;
    }

    /**
     * Marca ronda como iniciada
     *
     * @param mixed $id ID de la ronda
     * @return bool
     */
    public function markAsStarted($id): bool
    {
        return $this->model->where('id', $id)
            ->update([
                'status' => 'IN_PROGRESS',
                'start_time' => now()
            ]) > 0;
    }

    /**
     * Marca ronda como finalizada
     *
     * @param mixed $id ID de la ronda
     * @return bool
     */
    public function markAsFinished($id): bool
    {
        return $this->model->where('id', $id)
            ->update(['status' => 'FINISHED']) > 0;
    }
}
