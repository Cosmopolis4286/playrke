<?php

namespace App\Domains\Games\Bingo\Repositories\Creators\Contracts;

use App\Contracts\BaseRepositoryInterface;
use App\Domains\Games\Bingo\Models\Creator\BingoCreator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de creadores de salas de bingo
 *
 * Define los métodos que debe implementar el repositorio para gestionar
 * los creadores de salas de bingo de forma eficiente y organizada.
 *
 * @author Sistema PlayRKE
 *
 * @version 1.0
 */
interface BingoCreatorRepositoryContract extends BaseRepositoryInterface
{
    /**
     * Verificar si existe un creador para una sala y usuario específicos
     */
    public function existsByRoomAndCreatorUser(string $roomId, string $creatorUserId) : bool;

    /**
     * Obtener creador por sala
     */
    public function findByRoom(string $roomId) : ?BingoCreator;

    /**
     * Obtener creador por tipo y ID de creador específico en una sala
     */
    public function findByRoomAndCreator(string $roomId, string $creatorType, string $creatorId) : ?BingoCreator;

    /**
     * Obtener creador por sala y usuario creador
     */
    public function findByRoomAndCreatorUser(string $roomId, string $creatorUserId) : ?BingoCreator;

    /**
     * Obtener creadores activos
     *
     * @return Collection<int, BingoCreator>
     */
    public function getActiveCreators() : Collection;

    /**
     * Obtener precio promedio de cartas
     */
    public function getAverageCardPrice() : float;

    /**
     * Obtener valor promedio de comisiones
     */
    public function getAverageCommission() : float;

    /**
     * Obtener creadores por rango de precios de cartas
     *
     * @return Collection<int, BingoCreator>
     */
    public function getByCardPriceRange(float $minPrice, float $maxPrice) : Collection;

    /**
     * Obtener creadores por ID de creador específico
     *
     * @return Collection<int, BingoCreator>
     */
    public function getByCreatorId(string $creatorId) : Collection;

    /**
     * Obtener todos los creadores por tipo
     *
     * @return Collection<int, BingoCreator>
     */
    public function getByCreatorType(string $creatorType) : Collection;

    /**
     * Obtener creadores por usuario específico
     *
     * @return Collection<int, BingoCreator>
     */
    public function getByCreatorUser(string $creatorUserId) : Collection;

    /**
     * Obtener creadores que soportan una moneda específica
     *
     * @return Collection<int, BingoCreator>
     */
    public function getBySupportedCurrency(string $currencyCode) : Collection;

    /**
     * Obtener estadísticas de comisiones por creador
     *
     * @return array{
     *     total_commission: float,
     *     average_commission: float,
     *     room_count: int
     * }
     */
    public function getCommissionStats(string $creatorId) : array;

    /**
     * Obtener estadísticas de creadores por tipo
     *
     * @return array{
     *     total_creators: int,
     *     active_creators: int,
     *     inactive_creators: int,
     *     by_type: array
     * }
     */
    public function getCreatorStats() : array;

    /**
     * Obtener creadores inactivos
     *
     * @return Collection<int, BingoCreator>
     */
    public function getInactiveCreators() : Collection;

    /**
     * Obtener historial de cambios de precios de un creador
     */
    public function getPriceHistory(string $creatorId) : Collection;

    /**
     * Obtener creadores con mayor actividad
     *
     * @return Collection<int, BingoCreator>
     */
    public function getTopCreatorsByActivity(int $limit = 10) : Collection;

    /**
     * Obtener total de salas gestionadas por un creador
     */
    public function getTotalRoomsByCreator(string $creatorUserId) : int;

    /**
     * Verificar conflictos de creadores en una sala
     */
    public function hasCreatorConflicts(string $roomId) : bool;

    /**
     * Buscar creadores por término de búsqueda
     *
     * @return Collection<int, BingoCreator>
     */
    public function searchCreators(string $searchTerm) : Collection;

    /**
     * Actualizar monedas permitidas de un creador
     */
    public function updateAllowedCurrencies(string $creatorId, array $allowedCurrencies) : bool;

    /**
     * Actualizar precio de carta de un creador
     */
    public function updateCardPrice(string $creatorId, float $newPrice) : bool;

    /**
     * Actualizar estado de un creador
     */
    public function updateStatus(string $creatorId, string $newStatus) : bool;
}
