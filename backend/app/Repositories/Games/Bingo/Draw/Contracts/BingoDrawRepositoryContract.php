<?php

namespace App\Repositories\Games\Bingo\Draw\Contracts;

use App\Contracts\BaseRepositoryInterface;
use App\Domains\Games\Bingo\Models\Draw\BingoDraw;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de sorteos de bingo
 *
 * Define los métodos que debe implementar el repositorio para gestionar
 * los sorteos de bolillas/chips en las salas de bingo.
 *
 * @package App\Repositories\Bingo\Draw\Contracts
 * @author Sistema PlayRKE
 * @version 1.0
 */
interface BingoDrawRepositoryContract extends BaseRepositoryInterface
{
    /**
     * Obtener el chip ganador de una sala
     *
     * @param string $roomId
     * @return BingoDraw|null
     */
    public function getWinningChip(string $roomId): ?BingoDraw;

    /**
     * Obtener todos los sorteos de una sala
     *
     * @param string $roomId
     * @return Collection<int, BingoDraw>
     */
    public function getRoomDraws(string $roomId): Collection;

    /**
     * Obtener el primer chip sorteado de una sala
     *
     * @param string $roomId
     * @return BingoDraw|null
     */
    public function getFirstDrawChip(string $roomId): ?BingoDraw;

    /**
     * Obtener sorteos por sala y estado
     *
     * @param string $roomId
     * @param int $status
     * @return Collection<int, BingoDraw>
     */
    public function getByRoomAndStatus(string $roomId, int $status): Collection;

    /**
     * Obtener sorteos por ronda
     *
     * @param string $roomId
     * @param int $round
     * @return Collection<int, BingoDraw>
     */
    public function getByRound(string $roomId, int $round): Collection;

    /**
     * Obtener sorteos por código específico
     *
     * @param string $code
     * @return Collection<int, BingoDraw>
     */
    public function getByCode(string $code): Collection;

    /**
     * Obtener sorteos por chip específico
     *
     * @param string $chip
     * @return Collection<int, BingoDraw>
     */
    public function getByChip(string $chip): Collection;

    /**
     * Obtener sorteos activos
     *
     * @return Collection<int, BingoDraw>
     */
    public function getActiveDraws(): Collection;

    /**
     * Obtener sorteos completados
     *
     * @return Collection<int, BingoDraw>
     */
    public function getCompletedDraws(): Collection;

    /**
     * Obtener sorteos pendientes
     *
     * @return Collection<int, BingoDraw>
     */
    public function getPendingDraws(): Collection;

    /**
     * Obtener último sorteo de una sala
     *
     * @param string $roomId
     * @return BingoDraw|null
     */
    public function getLastDraw(string $roomId): ?BingoDraw;

    /**
     * Obtener sorteos por rango de tiempo
     *
     * @param string $roomId
     * @param int $startTime
     * @param int $endTime
     * @return Collection<int, BingoDraw>
     */
    public function getByTimeRange(string $roomId, int $startTime, int $endTime): Collection;

    /**
     * Contar total de sorteos por sala
     *
     * @param string $roomId
     * @return int
     */
    public function countByRoom(string $roomId): int;

    /**
     * Contar sorteos por estado en una sala
     *
     * @param string $roomId
     * @param int $status
     * @return int
     */
    public function countByRoomAndStatus(string $roomId, int $status): int;

    /**
     * Verificar si existe un chip específico en una sala
     *
     * @param string $roomId
     * @param string $chip
     * @return bool
     */
    public function existsChipInRoom(string $roomId, string $chip): bool;

    /**
     * Verificar si una ronda está completa
     *
     * @param string $roomId
     * @param int $round
     * @return bool
     */
    public function isRoundComplete(string $roomId, int $round): bool;

    /**
     * Obtener chips no sorteados de una sala
     *
     * @param string $roomId
     * @param array $allChips Array con todos los chips posibles
     * @return array
     */
    public function getUndrawnChips(string $roomId, array $allChips): array;

    /**
     * Obtener progreso de sorteos por sala
     *
     * @param string $roomId
     * @return array{
     *     total_draws: int,
     *     completed_draws: int,
     *     pending_draws: int,
     *     winning_draws: int,
     *     progress_percentage: float
     * }
     */
    public function getDrawProgress(string $roomId): array;

    /**
     * Obtener estadísticas de sorteos por ronda
     *
     * @param string $roomId
     * @return array{
     *     rounds: array,
     *     total_rounds: int,
     *     avg_draws_per_round: float
     * }
     */
    public function getRoundStats(string $roomId): array;

    /**
     * Obtener historial de sorteos con paginación
     *
     * @param string $roomId
     * @param int $page
     * @param int $perPage
     * @return array{
     *     data: Collection,
     *     current_page: int,
     *     total: int,
     *     per_page: int
     * }
     */
    public function getDrawHistory(string $roomId, int $page = 1, int $perPage = 50): array;

    /**
     * Crear nuevo sorteo
     *
     * @param array $drawData
     * @return BingoDraw
     */
    public function createDraw(array $drawData): BingoDraw;

    /**
     * Actualizar estado de sorteo
     *
     * @param string $drawId
     * @param int $newStatus
     * @return bool
     */
    public function updateDrawStatus(string $drawId, int $newStatus): bool;

    /**
     * Marcar chip como ganador
     *
     * @param string $drawId
     * @return bool
     */
    public function markAsWinning(string $drawId): bool;

    /**
     * Obtener secuencia de sorteos por sala
     *
     * @param string $roomId
     * @return Collection<int, BingoDraw>
     */
    public function getDrawSequence(string $roomId): Collection;

    /**
     * Validar secuencia de sorteos
     *
     * @param string $roomId
     * @return bool
     */
    public function validateDrawSequence(string $roomId): bool;

    /**
     * Obtener tiempo promedio entre sorteos
     *
     * @param string $roomId
     * @return float
     */
    public function getAverageDrawTime(string $roomId): float;

    /**
     * Buscar sorteos por término de búsqueda
     *
     * @param string $searchTerm
     * @return Collection<int, BingoDraw>
     */
    public function searchDraws(string $searchTerm): Collection;

    /**
     * Obtener sorteos duplicados (mismo chip, sala, ronda)
     *
     * @param string $roomId
     * @return Collection<int, BingoDraw>
     */
    public function getDuplicateDraws(string $roomId): Collection;

    /**
     * Limpiar sorteos antiguos
     *
     * @param int $daysOld
     * @return int Número de registros eliminados
     */
    public function cleanOldDraws(int $daysOld = 30): int;

    /**
     * Obtener estadísticas generales de sorteos
     *
     * @return array{
     *     total_draws: int,
     *     total_rooms: int,
     *     avg_draws_per_room: float,
     *     most_active_room: string|null
     * }
     */
    public function getGeneralStats(): array;

    /**
     * Exportar sorteos de una sala para análisis
     *
     * @param string $roomId
     * @param string $format 'json'|'csv'|'array'
     * @return mixed
     */
    public function exportRoomDraws(string $roomId, string $format = 'array');
}
