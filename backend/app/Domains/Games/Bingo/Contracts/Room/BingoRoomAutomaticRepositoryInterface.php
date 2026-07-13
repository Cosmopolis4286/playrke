<?php

namespace App\Domains\Games\Bingo\Contracts\Room;

use App\Contracts\BaseRepositoryInterface;
use App\Domains\Games\Bingo\Models\Room\BingoRoomAutomatic;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Contrato para el repositorio de salas de bingo automáticas
 *
 * Define los métodos que debe implementar el repositorio para gestionar
 * las salas de bingo automáticas con configuración programada.
 *
 * @package App\Domains\Games\Bingo\Contracts\Room
 * @author Sistema PlayRKE
 * @version 1.0
 */
interface BingoRoomAutomaticRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Obtener salas por creador
     *
     * @param string $creatorType
     * @param string $creatorId
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByCreator(string $creatorType, string $creatorId): Collection;

    /**
     * Obtener salas activas
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getActiveRooms(): Collection;

    /**
     * Obtener salas inactivas
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getInactiveRooms(): Collection;

    /**
     * Obtener salas por modo de juego
     *
     * @param string $gameMode
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByGameMode(string $gameMode): Collection;

    /**
     * Obtener salas por tipo de premio
     *
     * @param string $prizePoolType
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByPrizePoolType(string $prizePoolType): Collection;

    /**
     * Obtener salas por fecha específica
     *
     * @param string $date
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByDate(string $date): Collection;

    /**
     * Obtener salas por rango de fechas
     *
     * @param string $startDate
     * @param string $endDate
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByDateRange(string $startDate, string $endDate): Collection;

    /**
     * Obtener salas programadas para hoy
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getTodayScheduledRooms(): Collection;

    /**
     * Obtener salas por día de la semana
     *
     * @param string $day
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByDay(string $day): Collection;

    /**
     * Obtener salas por hora específica
     *
     * @param string $time
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByTime(string $time): Collection;

    /**
     * Obtener salas por tipo de sala
     *
     * @param string $roomType
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByRoomType(string $roomType): Collection;

    /**
     * Obtener salas en vivo
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getLiveRooms(): Collection;

    /**
     * Obtener salas por rango de precio de carta
     *
     * @param float $minPrice
     * @param float $maxPrice
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByCardPriceRange(float $minPrice, float $maxPrice): Collection;

    /**
     * Obtener salas que soportan una moneda específica
     *
     * @param string $currency
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getBySupportedCurrency(string $currency): Collection;

    /**
     * Obtener salas por número de rondas
     *
     * @param int $rounds
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByRounds(int $rounds): Collection;

    /**
     * Obtener salas por patrón de bingo
     *
     * @param string $pattern
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByBingoPattern(string $pattern): Collection;

    /**
     * Obtener salas próximas a ejecutarse
     *
     * @param int $minutesAhead
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getUpcomingRooms(int $minutesAhead = 30): Collection;

    /**
     * Verificar si existe una sala por creador
     *
     * @param string $creatorType
     * @param string $creatorId
     * @return bool
     */
    public function existsByCreator(string $creatorType, string $creatorId): bool;

    /**
     * Contar salas por estado
     *
     * @param string $status
     * @return int
     */
    public function countByStatus(string $status): int;

    /**
     * Contar salas por creador
     *
     * @param string $creatorType
     * @param string $creatorId
     * @return int
     */
    public function countByCreator(string $creatorType, string $creatorId): int;

    /**
     * Obtener estadísticas de salas automáticas
     *
     * @return array{
     *     total_rooms: int,
     *     active_rooms: int,
     *     inactive_rooms: int,
     *     live_rooms: int,
     *     scheduled_today: int,
     *     by_game_mode: array,
     *     by_room_type: array
     * }
     */
    public function getRoomStats(): array;

    /**
     * Buscar salas por término de búsqueda
     *
     * @param string $searchTerm
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function searchRooms(string $searchTerm): Collection;

    /**
     * Crear nueva sala automática
     *
     * @param array $roomData
     * @return BingoRoomAutomatic
     */
    public function createAutomaticRoom(array $roomData): BingoRoomAutomatic;

    /**
     * Actualizar configuración de sala
     *
     * @param string $roomId
     * @param array $updateData
     * @return bool
     */
    public function updateRoomConfig(string $roomId, array $updateData): bool;

    /**
     * Actualizar estado de sala
     *
     * @param string $roomId
     * @param string $newStatus
     * @return bool
     */
    public function updateRoomStatus(string $roomId, string $newStatus): bool;

    /**
     * Activar sala
     *
     * @param string $roomId
     * @return bool
     */
    public function activateRoom(string $roomId): bool;

    /**
     * Desactivar sala
     *
     * @param string $roomId
     * @return bool
     */
    public function deactivateRoom(string $roomId): bool;

    /**
     * Duplicar configuración de sala
     *
     * @param string $roomId
     * @param array $overrideData
     * @return BingoRoomAutomatic
     */
    public function duplicateRoom(string $roomId, array $overrideData = []): BingoRoomAutomatic;

    /**
     * Obtener salas agrupadas por modo de juego
     *
     * @return Collection
     */
    public function getGroupedByGameMode(): Collection;

    /**
     * Obtener salas agrupadas por tipo de sala
     *
     * @return Collection
     */
    public function getGroupedByRoomType(): Collection;

    /**
     * Obtener salas agrupadas por día
     *
     * @return Collection
     */
    public function getGroupedByDay(): Collection;

    /**
     * Validar configuración de sala
     *
     * @param string $roomId
     * @return bool
     */
    public function validateRoomConfig(string $roomId): bool;

    /**
     * Obtener precio promedio de cartas
     *
     * @return float
     */
    public function getAverageCardPrice(): float;

    /**
     * Obtener rondas promedio
     *
     * @return float
     */
    public function getAverageRounds(): float;

    /**
     * Limpiar salas vencidas
     *
     * @param int $daysOld
     * @return int Número de registros eliminados
     */
    public function cleanExpiredRooms(int $daysOld = 30): int;

    /**
     * Exportar configuraciones de salas
     *
     * @param string $format 'json'|'csv'|'array'
     * @return mixed
     */
    public function exportRoomConfigs(string $format = 'array');

    /**
     * Obtener horarios más populares
     *
     * @param int $limit
     * @return Collection
     */
    public function getMostPopularTimes(int $limit = 10): Collection;

    /**
     * Verificar conflictos de horarios
     *
     * @param string $date
     * @param string $time
     * @param string|null $excludeRoomId
     * @return bool
     */
    public function hasTimeConflict(string $date, string $time, ?string $excludeRoomId = null): bool;

    /**
     * Obtener configuración recomendada
     *
     * @param string $gameMode
     * @return array
     */
    public function getRecommendedConfig(string $gameMode): array;

    /**
     * Paginar el listado admin/commerce de salas automáticas
     * (Room/Automatic/Summary) con filtros reales aplicados en SQL, en vez
     * de traer una sola página fija y filtrar en el cliente.
     *
     * @param array<string,mixed> $conditions Condiciones directas sobre bingo_rooms_automatic (campo => valor)
     * @param array{
     *     search?: string,
     *     status?: string,
     *     room_type_id?: string,
     *     game_mode_id?: string,
     *     prize_pool_type_id?: string,
     *     currency_id?: string,
     * } $filters Filtros directos (todas son columnas propias de la tabla) + búsqueda por id
     * @param array<int,string> $relations
     * @param array<int,string> $fields
     */
    public function paginateSummaryGameRooms(
        int $perPage,
        int $page,
        array $conditions,
        array $filters,
        array $relations,
        array $fields
    ): LengthAwarePaginator;
}
