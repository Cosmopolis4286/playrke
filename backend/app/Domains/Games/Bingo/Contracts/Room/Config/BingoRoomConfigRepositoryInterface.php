<?php

namespace App\Domains\Games\Bingo\Contracts\Room\Config;

use App\Contracts\BaseRepositoryInterface;
use App\Domains\Games\Bingo\Models\Room\BingoRoomConfig;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repository de configuraciones de salas de bingo
 */
interface BingoRoomConfigRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Buscar salas por tipo
     *
     * @param string $roomType Tipo de sala (PRIVATE, PUBLIC)
     * @param array  $columns  Columnas a seleccionar
     *
     * @return Collection<BingoRoom>
     */
    public function findByType(string $roomType, array $columns = ['*']) : Collection;

    /**
     * Obtiene configuraciones por precio de cartón
     *
     * @param float $minPrice Precio mínimo
     * @param float $maxPrice Precio máximo
     */
    public function getByCardPriceRange(float $minPrice, float $maxPrice) : Collection;

    /**
     * Obtiene configuraciones por usuario creador
     *
     * @param mixed $creatorUserId ID del usuario creador
     */
    public function getByCreatorUser($creatorUserId) : Collection;

    /**
     * Obtiene configuraciones por fecha
     *
     * @param string $date Fecha en formato YYYY-MM-DD
     */
    public function getByDate(string $date) : Collection;

    /**
     * Obtiene configuraciones en un rango de fechas
     *
     * @param string $startDate Fecha de inicio
     * @param string $endDate   Fecha de fin
     */
    public function getByDateRange(string $startDate, string $endDate) : Collection;

    /**
     * Obtiene configuraciones por modo de juego
     *
     * @param mixed $gameModeId ID del modo de juego
     */
    public function getByGameMode($gameModeId) : Collection;

    /**
     * Obtiene configuraciones por modo de creación
     *
     * @param string $isScheduled Modo de creación ('N' = manual, 'Y' = automático)
     */
    public function getByIsScheduled(string $isScheduled) : Collection;

    /**
     * Obtiene configuraciones por tipo de pool de premios
     *
     * @param mixed $prizePoolTypeId ID del tipo de pool de premios
     */
    public function getByPrizePoolType($prizePoolTypeId) : Collection;

    /**
     * Obtiene la configuración por ID de sala
     *
     * @param mixed $roomId ID de la sala
     * @param array $fields Campos a seleccionar
     */
    public function getByRoomId($roomId, array $fields = ['*']) : ?BingoRoomConfig;

    /**
     * Obtiene configuraciones en vivo
     */
    public function getLiveConfigurations() : Collection;

    /**
     * Obtiene un valor específico por ID de sala
     *
     * @param mixed  $roomId ID de la sala
     * @param string $field  Campo a obtener
     *
     * @return mixed
     */
    public function getValueByRoomId($roomId, string $field);

    /**
     * Actualiza comisiones de una configuración
     *
     * @param mixed $id          ID de la configuración
     * @param array $commissions Array con 'rke_commission' y/o 'room_commission'
     */
    public function updateCommissions($id, array $commissions) : bool;

    /**
     * Actualiza estado de transmisión en vivo
     *
     * @param mixed  $id     ID de la configuración
     * @param string $inLive Estado ('Y' o 'N')
     */
    public function updateLiveStatus($id, string $inLive) : bool;
}
