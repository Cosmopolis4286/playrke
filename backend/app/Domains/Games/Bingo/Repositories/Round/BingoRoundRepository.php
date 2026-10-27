<?php

namespace App\Domains\Games\Bingo\Repositories\Round;

use App\Domains\Games\Bingo\Contracts\Round\BingoRoundRepositoryInterface;
use App\Domains\Games\Bingo\Models\Round\BingoRound;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository para manejar operaciones de rondas de bingo
 */
class BingoRoundRepository extends BaseRepository implements BingoRoundRepositoryInterface
{
    /**
     * Constructor del repository
     */
    public function __construct(BingoRound $model)
    {
        $this->model = $model;
    }

    /**
     * Cuenta rondas por sala
     *
     * @param mixed $roomId ID de la sala
     */
    public function countByRoom($roomId) : int
    {
        return $this->model->where('room_id', $roomId)->count();
    }

    /**
     * Verifica si existen rondas para una sala
     *
     * @param mixed $roomId ID de la sala
     */
    public function existsRounds($roomId) : bool
    {
        return $this->model->where('room_id', $roomId)->exists();
    }

    /**
     * Obtiene rondas activas
     */
    public function getActiveRounds() : Collection
    {
        return $this->model->where('status', 'ACTIVE')->get();
    }

    /**
     * Obtiene todas las rondas de una sala
     *
     * @param mixed $roomId ID de la sala
     */
    public function getAllByRoom($roomId) : Collection
    {
        return $this->model->where('room_id', $roomId)->get();
    }

    /**
     * Obtiene rondas por patrón
     *
     * @param mixed $patternId ID del patrón
     */
    public function getByPattern($patternId) : Collection
    {
        return $this->model->where('bingo_pattern_id', $patternId)->get();
    }

    /**
     * Obtiene rondas por tipo de premio
     *
     * @param string $prizeType Tipo de premio
     */
    public function getByPrizeType(string $prizeType) : Collection
    {
        return $this->model->where('prize_type', $prizeType)->get();
    }

    /**
     * Obtiene una ronda por sala y número de ronda
     *
     * @param mixed    $roomId ID de la sala
     * @param int|null $round  Número de ronda (opcional)
     * @param array    $fields Campos a seleccionar
     */
    public function getByRoom($roomId, ?int $round = null, array $fields = ['*']) : ?BingoRound
    {
        $query = $this->model->select($fields)
            ->where('room_id', $roomId);

        if ($round !== null) {
            $query->where('round', $round);
        }

        return $query->first();
    }

    /**
     * Obtiene rondas finalizadas
     */
    public function getFinishedRounds() : Collection
    {
        return $this->model->where('status', 'FINISHED')->get();
    }

    /**
     * Obtiene la ronda en progreso de una sala
     *
     * @param mixed $roomId ID de la sala
     * @param array $fields Campos a seleccionar
     */
    public function getRoundInProgress($roomId, array $fields = ['*']) : ?BingoRound
    {
        return $this->model->select($fields)
            ->where('room_id', $roomId)
            ->whereIn('status', ['IN_PROGRESS', 'FINISHED'])
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Obtiene un valor específico por sala y ronda
     *
     * @param mixed  $roomId ID de la sala
     * @param int    $round  Número de ronda
     * @param string $field  Campo a obtener
     *
     * @return mixed
     */
    public function getValueByRoom($roomId, int $round, string $field)
    {
        return $this->model->where('room_id', $roomId)
            ->where('round', $round)
            ->value($field);
    }

    /**
     * Obtiene un valor de la ronda en progreso
     *
     * @param mixed  $roomId ID de la sala
     * @param string $field  Campo a obtener
     *
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
     * Marca ronda como finalizada
     *
     * @param mixed $id ID de la ronda
     */
    public function markAsFinished($id) : bool
    {
        return $this->model->where('id', $id)
            ->update(['status' => 'FINISHED']) > 0;
    }

    /**
     * Marca ronda como iniciada
     *
     * @param mixed $id ID de la ronda
     */
    public function markAsStarted($id) : bool
    {
        return $this->model->where('id', $id)
            ->update([
                'status' => 'IN_PROGRESS',
                'start_time' => now(),
            ]) > 0;
    }

    /**
     * Actualiza la última ficha jugada
     *
     * @param mixed  $id       ID de la ronda
     * @param string $lastChip Última ficha
     */
    public function updateLastChip($id, string $lastChip) : bool
    {
        return $this->model->where('id', $id)
            ->update(['last_chip' => $lastChip]) > 0;
    }

    /**
     * Actualiza estado de una ronda
     *
     * @param mixed  $id     ID de la ronda
     * @param string $status Nuevo estado
     */
    public function updateStatus($id, string $status) : bool
    {
        return $this->model->where('id', $id)
            ->update(['status' => $status]) > 0;
    }
}
