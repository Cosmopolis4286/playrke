<?php

namespace App\Domains\Games\Bingo\Repositories\Room\Config;

use App\Domains\Games\Bingo\Contracts\Room\Config\BingoRoomConfigRepositoryInterface;
use App\Domains\Games\Bingo\Models\Room\BingoRoomConfig;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository para manejar operaciones de configuraciones de salas de bingo
 */
class BingoRoomConfigRepository extends BaseRepository implements BingoRoomConfigRepositoryInterface
{
    /**
     * Constructor del repository
     */
    public function __construct(BingoRoomConfig $model)
    {
        $this->model = $model;
    }

    /**
     * Buscar salas por tipo
     *
     * @param string $roomTypeId Tipo de sala (PRIVATE, PUBLIC)
     * @param array  $columns    Columnas a seleccionar
     *
     * @return Collection<BingoRoom>
     */
    public function findByType(string $roomTypeId, array $columns = ['*']) : Collection
    {
        return $this->model->where('room_type_id', $roomTypeId)->get($columns);
    }

    /**
     * Obtiene configuraciones por precio de cartón
     *
     * @param float $minPrice Precio mínimo
     * @param float $maxPrice Precio máximo
     */
    public function getByCardPriceRange(float $minPrice, float $maxPrice) : Collection
    {
        return $this->model->whereBetween('card_price', [$minPrice, $maxPrice])->get();
    }

    /**
     * Obtiene configuraciones por usuario creador
     *
     * @param mixed $creatorUserId ID del usuario creador
     */
    public function getByCreatorUser($creatorUserId) : Collection
    {
        return $this->model->where('creator_user_id', $creatorUserId)->get();
    }

    /**
     * Obtiene configuraciones por fecha
     *
     * @param string $date Fecha en formato YYYY-MM-DD
     */
    public function getByDate(string $date) : Collection
    {
        return $this->model->where('date', $date)->get();
    }

    /**
     * Obtiene configuraciones en un rango de fechas
     *
     * @param string $startDate Fecha de inicio
     * @param string $endDate   Fecha de fin
     */
    public function getByDateRange(string $startDate, string $endDate) : Collection
    {
        return $this->model->whereBetween('date', [$startDate, $endDate])->get();
    }

    /**
     * Obtiene configuraciones por modo de juego
     *
     * @param mixed $gameModeId ID del modo de juego
     */
    public function getByGameMode($gameModeId) : Collection
    {
        return $this->model->where('connection_type_id', $gameModeId)->get();
    }

    /**
     * Obtiene configuraciones por modo de creación
     *
     * @param string $isScheduled Modo de creación ('N' = manual, 'Y' = automático)
     */
    public function getByIsScheduled(string $isScheduled) : Collection
    {
        return $this->model->where('is_scheduled', $isScheduled)->get();
    }

    /**
     * Obtiene configuraciones por tipo de pool de premios
     *
     * @param mixed $prizePoolTypeId ID del tipo de pool de premios
     */
    public function getByPrizePoolType($prizePoolTypeId) : Collection
    {
        return $this->model->where('prize_pool_type_id', $prizePoolTypeId)->get();
    }

    /**
     * Obtiene la configuración por ID de sala
     *
     * @param mixed $roomId ID de la sala
     * @param array $fields Campos a seleccionar
     */
    public function getByRoomId($roomId, array $fields = ['*']) : ?BingoRoomConfig
    {
        return $this->model->select($fields)
            ->where('room_id', $roomId)
            ->first();
    }

    /**
     * Obtiene configuraciones en vivo
     */
    public function getLiveConfigurations() : Collection
    {
        return $this->model->where('in_live', 'Y')->get();
    }

    /**
     * Obtiene un valor específico por ID de sala
     *
     * @param mixed  $roomId ID de la sala
     * @param string $field  Campo a obtener
     *
     * @return mixed
     */
    public function getValueByRoomId($roomId, string $field)
    {
        return $this->model->where('room_id', $roomId)->value($field);
    }

    /**
     * Actualiza comisiones de una configuración
     *
     * @param mixed $id          ID de la configuración
     * @param array $commissions Array con 'rke_commission' y/o 'room_commission'
     */
    public function updateCommissions($id, array $commissions) : bool
    {
        $allowedFields = ['rke_commission', 'room_commission'];
        $updateData = array_intersect_key($commissions, array_flip($allowedFields));

        if (empty($updateData)) {
            return false;
        }

        return $this->model->where('id', $id)
            ->update($updateData) > 0;
    }

    /**
     * Actualiza estado de transmisión en vivo
     *
     * @param mixed  $id     ID de la configuración
     * @param string $inLive Estado ('Y' o 'N')
     */
    public function updateLiveStatus($id, string $inLive) : bool
    {
        return $this->model->where('id', $id)
            ->update(['in_live' => $inLive]) > 0;
    }
}
