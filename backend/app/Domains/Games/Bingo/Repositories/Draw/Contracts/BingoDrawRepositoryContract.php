<?php

namespace App\Domains\Games\Bingo\Repositories\Draw\Contracts;

use App\Contracts\BaseRepositoryInterface;
use App\Domains\Games\Bingo\Models\Draw\BingoDraw;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de sorteos de bingo
 *
 * Define los métodos que debe implementar el repositorio para gestionar
 * los sorteos de bolillas/chips en las salas de bingo.
 *
 * @author Sistema PlayRKE
 *
 * @version 1.0
 */
interface BingoDrawRepositoryContract extends BaseRepositoryInterface
{
    /**
     * Limpiar sorteos antiguos
     *
     * @return int Número de registros eliminados
     */
    public function cleanOldDraws(int $daysOld = 30) : int;

    /**
     * Contar total de sorteos por sala
     */
    public function countByRoom(string $roomId) : int;

    /**
     * Contar sorteos por estado en una sala
     */
    public function countByRoomAndStatus(string $roomId, int $status) : int;

    /**
     * Crear nuevo sorteo
     */
    public function createDraw(array $drawData) : BingoDraw;

    /**
     * Verificar si existe un chip específico en una sala
     */
    public function existsChipInRoom(string $roomId, string $chip) : bool;

    /**
     * Exportar sorteos de una sala para análisis
     *
     * @param string $format 'json'|'csv'|'array'
     *
     * @return mixed
     */
    public function exportRoomDraws(string $roomId, string $format = 'array');

    /**
     * Obtener sorteos activos
     *
     * @return Collection<int, BingoDraw>
     */
    public function getActiveDraws() : Collection;

    /**
     * Obtener tiempo promedio entre sorteos
     */
    public function getAverageDrawTime(string $roomId) : float;

    /**
     * Obtener sorteos por chip específico
     *
     * @return Collection<int, BingoDraw>
     */
    public function getByChip(string $chip) : Collection;

    /**
     * Obtener sorteos por código específico
     *
     * @return Collection<int, BingoDraw>
     */
    public function getByCode(string $code) : Collection;

    /**
     * Obtener sorteos por sala y estado
     *
     * @return Collection<int, BingoDraw>
     */
    public function getByRoomAndStatus(string $roomId, int $status) : Collection;

    /**
     * Obtener sorteos por ronda
     *
     * @return Collection<int, BingoDraw>
     */
    public function getByRound(string $roomId, int $round) : Collection;

    /**
     * Obtener sorteos por rango de tiempo
     *
     * @return Collection<int, BingoDraw>
     */
    public function getByTimeRange(string $roomId, int $startTime, int $endTime) : Collection;

    /**
     * Obtener sorteos completados
     *
     * @return Collection<int, BingoDraw>
     */
    public function getCompletedDraws() : Collection;

    /**
     * Obtener historial de sorteos con paginación
     *
     * @return array{
     *     data: Collection,
     *     current_page: int,
     *     total: int,
     *     per_page: int
     * }
     */
    public function getDrawHistory(string $roomId, int $page = 1, int $perPage = 50) : array;

    /**
     * Obtener progreso de sorteos por sala
     *
     * @return array{
     *     total_draws: int,
     *     completed_draws: int,
     *     pending_draws: int,
     *     winning_draws: int,
     *     progress_percentage: float
     * }
     */
    public function getDrawProgress(string $roomId) : array;

    /**
     * Obtener secuencia de sorteos por sala
     *
     * @return Collection<int, BingoDraw>
     */
    public function getDrawSequence(string $roomId) : Collection;

    /**
     * Obtener sorteos duplicados (mismo chip, sala, ronda)
     *
     * @return Collection<int, BingoDraw>
     */
    public function getDuplicateDraws(string $roomId) : Collection;

    /**
     * Obtener el primer chip sorteado de una sala
     */
    public function getFirstDrawChip(string $roomId) : ?BingoDraw;

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
    public function getGeneralStats() : array;

    /**
     * Obtener último sorteo de una sala
     */
    public function getLastDraw(string $roomId) : ?BingoDraw;

    /**
     * Obtener sorteos pendientes
     *
     * @return Collection<int, BingoDraw>
     */
    public function getPendingDraws() : Collection;

    /**
     * Obtener todos los sorteos de una sala
     *
     * @return Collection<int, BingoDraw>
     */
    public function getRoomDraws(string $roomId) : Collection;

    /**
     * Obtener estadísticas de sorteos por ronda
     *
     * @return array{
     *     rounds: array,
     *     total_rounds: int,
     *     avg_draws_per_round: float
     * }
     */
    public function getRoundStats(string $roomId) : array;

    /**
     * Obtener chips no sorteados de una sala
     *
     * @param array $allChips Array con todos los chips posibles
     */
    public function getUndrawnChips(string $roomId, array $allChips) : array;

    /**
     * Obtener el chip ganador de una sala
     */
    public function getWinningChip(string $roomId) : ?BingoDraw;

    /**
     * Verificar si una ronda está completa
     */
    public function isRoundComplete(string $roomId, int $round) : bool;

    /**
     * Marcar chip como ganador
     */
    public function markAsWinning(string $drawId) : bool;

    /**
     * Buscar sorteos por término de búsqueda
     *
     * @return Collection<int, BingoDraw>
     */
    public function searchDraws(string $searchTerm) : Collection;

    /**
     * Actualizar estado de sorteo
     */
    public function updateDrawStatus(string $drawId, int $newStatus) : bool;

    /**
     * Validar secuencia de sorteos
     */
    public function validateDrawSequence(string $roomId) : bool;
}
