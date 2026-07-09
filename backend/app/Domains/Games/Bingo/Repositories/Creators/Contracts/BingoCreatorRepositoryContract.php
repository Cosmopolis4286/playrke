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
 * @package App\Repositories\Bingo\Creators\Contracts
 * @author Sistema PlayRKE
 * @version 1.0
 */
interface BingoCreatorRepositoryContract extends BaseRepositoryInterface
{
    /**
     * Obtener creador por sala
     *
     * @param string $roomId
     * @return BingoCreator|null
     */
    public function findByRoom(string $roomId): ?BingoCreator;

    /**
     * Obtener creador por sala y usuario creador
     *
     * @param string $roomId
     * @param string $creatorUserId
     * @return BingoCreator|null
     */
    public function findByRoomAndCreatorUser(string $roomId, string $creatorUserId): ?BingoCreator;

    /**
     * Obtener creador por tipo y ID de creador específico en una sala
     *
     * @param string $roomId
     * @param string $creatorType
     * @param string $creatorId
     * @return BingoCreator|null
     */
    public function findByRoomAndCreator(string $roomId, string $creatorType, string $creatorId): ?BingoCreator;

    /**
     * Verificar si existe un creador para una sala y usuario específicos
     *
     * @param string $roomId
     * @param string $creatorUserId
     * @return bool
     */
    public function existsByRoomAndCreatorUser(string $roomId, string $creatorUserId): bool;

    /**
     * Obtener todos los creadores por tipo
     *
     * @param string $creatorType
     * @return Collection<int, BingoCreator>
     */
    public function getByCreatorType(string $creatorType): Collection;

    /**
     * Obtener creadores activos
     *
     * @return Collection<int, BingoCreator>
     */
    public function getActiveCreators(): Collection;

    /**
     * Obtener creadores inactivos
     *
     * @return Collection<int, BingoCreator>
     */
    public function getInactiveCreators(): Collection;

    /**
     * Obtener creadores por usuario específico
     *
     * @param string $creatorUserId
     * @return Collection<int, BingoCreator>
     */
    public function getByCreatorUser(string $creatorUserId): Collection;

    /**
     * Obtener creadores por ID de creador específico
     *
     * @param string $creatorId
     * @return Collection<int, BingoCreator>
     */
    public function getByCreatorId(string $creatorId): Collection;

    /**
     * Obtener creadores por rango de precios de cartas
     *
     * @param float $minPrice
     * @param float $maxPrice
     * @return Collection<int, BingoCreator>
     */
    public function getByCardPriceRange(float $minPrice, float $maxPrice): Collection;

    /**
     * Obtener creadores que soportan una moneda específica
     *
     * @param string $currencyCode
     * @return Collection<int, BingoCreator>
     */
    public function getBySupportedCurrency(string $currencyCode): Collection;

    /**
     * Actualizar estado de un creador
     *
     * @param string $creatorId
     * @param string $newStatus
     * @return bool
     */
    public function updateStatus(string $creatorId, string $newStatus): bool;

    /**
     * Actualizar precio de carta de un creador
     *
     * @param string $creatorId
     * @param float $newPrice
     * @return bool
     */
    public function updateCardPrice(string $creatorId, float $newPrice): bool;

    /**
     * Actualizar monedas permitidas de un creador
     *
     * @param string $creatorId
     * @param array $allowedCurrencies
     * @return bool
     */
    public function updateAllowedCurrencies(string $creatorId, array $allowedCurrencies): bool;

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
    public function getCreatorStats(): array;

    /**
     * Obtener estadísticas de comisiones por creador
     *
     * @param string $creatorId
     * @return array{
     *     total_commission: float,
     *     average_commission: float,
     *     room_count: int
     * }
     */
    public function getCommissionStats(string $creatorId): array;

    /**
     * Obtener creadores con mayor actividad
     *
     * @param int $limit
     * @return Collection<int, BingoCreator>
     */
    public function getTopCreatorsByActivity(int $limit = 10): Collection;

    /**
     * Buscar creadores por término de búsqueda
     *
     * @param string $searchTerm
     * @return Collection<int, BingoCreator>
     */
    public function searchCreators(string $searchTerm): Collection;

    /**
     * Obtener historial de cambios de precios de un creador
     *
     * @param string $creatorId
     * @return Collection
     */
    public function getPriceHistory(string $creatorId): Collection;

    /**
     * Verificar conflictos de creadores en una sala
     *
     * @param string $roomId
     * @return bool
     */
    public function hasCreatorConflicts(string $roomId): bool;

    /**
     * Obtener total de salas gestionadas por un creador
     *
     * @param string $creatorUserId
     * @return int
     */
    public function getTotalRoomsByCreator(string $creatorUserId): int;

    /**
     * Obtener valor promedio de comisiones
     *
     * @return float
     */
    public function getAverageCommission(): float;

    /**
     * Obtener precio promedio de cartas
     *
     * @return float
     */
    public function getAverageCardPrice(): float;
}
