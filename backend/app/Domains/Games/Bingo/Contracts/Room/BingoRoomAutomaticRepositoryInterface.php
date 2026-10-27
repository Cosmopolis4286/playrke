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
 * @author Sistema PlayRKE
 *
 * @version 1.0
 */
interface BingoRoomAutomaticRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Activar sala
     */
    public function activateRoom(string $roomId) : bool;

    /**
     * Limpiar salas vencidas
     *
     * @return int Número de registros eliminados
     */
    public function cleanExpiredRooms(int $daysOld = 30) : int;

    /**
     * Contar salas por creador
     */
    public function countByCreator(string $creatorType, string $creatorId) : int;

    /**
     * Contar salas por estado
     */
    public function countByStatus(string $status) : int;

    /**
     * Crear nueva sala automática
     */
    public function createAutomaticRoom(array $roomData) : BingoRoomAutomatic;

    /**
     * Desactivar sala
     */
    public function deactivateRoom(string $roomId) : bool;

    /**
     * Duplicar configuración de sala
     */
    public function duplicateRoom(string $roomId, array $overrideData = []) : BingoRoomAutomatic;

    /**
     * Verificar si existe una sala por creador
     */
    public function existsByCreator(string $creatorType, string $creatorId) : bool;

    /**
     * Exportar configuraciones de salas
     *
     * @param string $format 'json'|'csv'|'array'
     *
     * @return mixed
     */
    public function exportRoomConfigs(string $format = 'array');

    /**
     * Obtener salas activas
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getActiveRooms() : Collection;

    /**
     * Obtener precio promedio de cartas
     */
    public function getAverageCardPrice() : float;

    /**
     * Obtener rondas promedio
     */
    public function getAverageRounds() : float;

    /**
     * Obtener salas por patrón de bingo
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByBingoPattern(string $pattern) : Collection;

    /**
     * Obtener salas por rango de precio de carta
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByCardPriceRange(float $minPrice, float $maxPrice) : Collection;

    /**
     * Obtener salas por creador
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByCreator(string $creatorType, string $creatorId) : Collection;

    /**
     * Obtener salas por fecha específica
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByDate(string $date) : Collection;

    /**
     * Obtener salas por rango de fechas
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByDateRange(string $startDate, string $endDate) : Collection;

    /**
     * Obtener salas por día de la semana
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByDay(string $day) : Collection;

    /**
     * Obtener salas por modo de juego
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByGameMode(string $gameMode) : Collection;

    /**
     * Obtener salas por tipo de premio
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByPrizePoolType(string $prizePoolType) : Collection;

    /**
     * Obtener salas por tipo de sala
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByRoomType(string $roomType) : Collection;

    /**
     * Obtener salas por número de rondas
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByRounds(int $rounds) : Collection;

    /**
     * Obtener salas que soportan una moneda específica
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getBySupportedCurrency(string $currency) : Collection;

    /**
     * Obtener salas por hora específica
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByTime(string $time) : Collection;

    /**
     * Obtener salas agrupadas por día
     */
    public function getGroupedByDay() : Collection;

    /**
     * Obtener salas agrupadas por modo de juego
     */
    public function getGroupedByGameMode() : Collection;

    /**
     * Obtener salas agrupadas por tipo de sala
     */
    public function getGroupedByRoomType() : Collection;

    /**
     * Obtener salas inactivas
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getInactiveRooms() : Collection;

    /**
     * Obtener salas en vivo
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getLiveRooms() : Collection;

    /**
     * Obtener horarios más populares
     */
    public function getMostPopularTimes(int $limit = 10) : Collection;

    /**
     * Obtener configuración recomendada
     */
    public function getRecommendedConfig(string $gameMode) : array;

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
    public function getRoomStats() : array;

    /**
     * Obtener salas programadas para hoy
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getTodayScheduledRooms() : Collection;

    /**
     * Obtener salas próximas a ejecutarse
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getUpcomingRooms(int $minutesAhead = 30) : Collection;

    /**
     * Verificar conflictos de horarios
     */
    public function hasTimeConflict(string $date, string $time, ?string $excludeRoomId = null) : bool;

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
     *     connection_type_id?: string,
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
        array $fields,
    ) : LengthAwarePaginator;

    /**
     * Buscar salas por término de búsqueda
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function searchRooms(string $searchTerm) : Collection;

    /**
     * Actualizar configuración de sala
     */
    public function updateRoomConfig(string $roomId, array $updateData) : bool;

    /**
     * Actualizar estado de sala
     */
    public function updateRoomStatus(string $roomId, string $newStatus) : bool;

    /**
     * Validar configuración de sala
     */
    public function validateRoomConfig(string $roomId) : bool;
}
