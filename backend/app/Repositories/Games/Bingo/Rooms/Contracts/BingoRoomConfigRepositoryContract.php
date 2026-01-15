<?php

namespace App\Repositories\Bingo\Rooms\Contracts;

use App\Contracts\BaseRepositoryInterface;
use App\Models\Bingo\Rooms\BingoRoomConfig;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repository de configuraciones de salas de bingo
 */
interface BingoRoomConfigRepositoryContract extends BaseRepositoryInterface
{
    /**
     * Obtiene la configuración por ID de sala
     *
     * @param mixed $roomId ID de la sala
     * @param array $fields Campos a seleccionar
     * @return BingoRoomConfig|null
     */
    public function getByRoomId($roomId, array $fields = ['*']): ?BingoRoomConfig;

    /**
     * Obtiene un valor específico por ID de sala
     *
     * @param mixed $roomId ID de la sala
     * @param string $field Campo a obtener
     * @return mixed
     */
    public function getValueByRoomId($roomId, string $field);

    /**
     * Obtiene configuraciones por modo de creación
     *
     * @param string $creation Modo de creación ('M' = manual, 'A' = automático)
     * @return Collection
     */
    public function getByCreationMode(string $creation): Collection;

    /**
     * Obtiene configuraciones en vivo
     *
     * @return Collection
     */
    public function getLiveConfigurations(): Collection;

    /**
     * Obtiene configuraciones por modo de juego
     *
     * @param mixed $gameModeId ID del modo de juego
     * @return Collection
     */
    public function getByGameMode($gameModeId): Collection;

    /**
     * Obtiene configuraciones por tipo de pool de premios
     *
     * @param mixed $prizePoolTypeId ID del tipo de pool de premios
     * @return Collection
     */
    public function getByPrizePoolType($prizePoolTypeId): Collection;

    /**
     * Obtiene configuraciones por fecha
     *
     * @param string $date Fecha en formato YYYY-MM-DD
     * @return Collection
     */
    public function getByDate(string $date): Collection;

    /**
     * Obtiene configuraciones en un rango de fechas
     *
     * @param string $startDate Fecha de inicio
     * @param string $endDate Fecha de fin
     * @return Collection
     */
    public function getByDateRange(string $startDate, string $endDate): Collection;

    /**
     * Obtiene configuraciones por usuario creador
     *
     * @param mixed $creatorUserId ID del usuario creador
     * @return Collection
     */
    public function getByCreatorUser($creatorUserId): Collection;

    /**
     * Obtiene configuraciones por precio de cartón
     *
     * @param float $minPrice Precio mínimo
     * @param float $maxPrice Precio máximo
     * @return Collection
     */
    public function getByCardPriceRange(float $minPrice, float $maxPrice): Collection;

    /**
     * Actualiza estado de transmisión en vivo
     *
     * @param mixed $id ID de la configuración
     * @param string $inLive Estado ('Y' o 'N')
     * @return bool
     */
    public function updateLiveStatus($id, string $inLive): bool;

    /**
     * Actualiza comisiones de una configuración
     *
     * @param mixed $id ID de la configuración
     * @param array $commissions Array con 'rke_commn' y/o 'room_commn'
     * @return bool
     */
    public function updateCommissions($id, array $commissions): bool;
}