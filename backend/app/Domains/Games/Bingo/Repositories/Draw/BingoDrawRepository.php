<?php

namespace App\Domains\Games\Bingo\Repositories\Draw;

use App\Domains\Games\Bingo\Models\Draw\BingoDraw;
use App\Domains\Games\Bingo\Repositories\Draw\Contracts\BingoDrawRepositoryContract;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Repositorio para gestionar sorteos de bingo
 *
 * Implementa todas las operaciones de consulta y manipulación de datos
 * para los sorteos de bolillas/chips en las salas de bingo.
 *
 * @author Sistema PlayRKE
 *
 * @version 1.0
 */
class BingoDrawRepository extends BaseRepository implements BingoDrawRepositoryContract
{
    public const STATUS_ACTIVE = 2;
    public const STATUS_COMPLETED = 4;
    /**
     * Estados de sorteos
     */
    public const STATUS_PENDING = 1;
    public const STATUS_WINNING = 3;

    /**
     * Constructor del repositorio
     */
    public function __construct(BingoDraw $model)
    {
        $this->model = $model;
    }

    /**
     * Limpiar sorteos antiguos
     *
     * @return int Número de registros eliminados
     */
    public function cleanOldDraws(int $daysOld = 30) : int
    {
        $cutoffDate = now()->subDays($daysOld);

        return $this->model->where('created_at', '<', $cutoffDate)
            ->where('status', self::STATUS_COMPLETED)
            ->delete();
    }

    /**
     * Contar total de sorteos por sala
     */
    public function countByRoom(string $roomId) : int
    {
        return $this->model->where('room_id', $roomId)->count();
    }

    /**
     * Contar sorteos por estado en una sala
     */
    public function countByRoomAndStatus(string $roomId, int $status) : int
    {
        return $this->model->where('room_id', $roomId)
            ->where('status', $status)
            ->count();
    }

    /**
     * Crear nuevo sorteo
     */
    public function createDraw(array $drawData) : BingoDraw
    {
        return $this->create($drawData);
    }

    /**
     * Verificar si existe un chip específico en una sala
     */
    public function existsChipInRoom(string $roomId, string $chip) : bool
    {
        return $this->model->where('room_id', $roomId)
            ->where('chip', $chip)
            ->exists();
    }

    /**
     * Exportar sorteos de una sala para análisis
     *
     * @param string $format 'json'|'csv'|'array'
     *
     * @return mixed
     */
    public function exportRoomDraws(string $roomId, string $format = 'array')
    {
        $draws = $this->getRoomDraws($roomId);

        switch ($format) {
            case 'json':
                return $draws->toJson();
            case 'csv':
                // Implementar exportación CSV si es necesario
                return $draws->toArray();
            case 'array':
            default:
                return $draws->toArray();
        }
    }

    /**
     * Obtener sorteos activos
     *
     * @return Collection<int, BingoDraw>
     */
    public function getActiveDraws() : Collection
    {
        return $this->getByStatus(self::STATUS_ACTIVE);
    }

    /**
     * Obtener tiempo promedio entre sorteos
     */
    public function getAverageDrawTime(string $roomId) : float
    {
        $draws = $this->model->where('room_id', $roomId)
            ->orderBy('time')
            ->pluck('time')
            ->toArray();

        if (count($draws) < 2) {
            return 0.0;
        }

        $intervals = [];
        for ($i = 1; $i < count($draws); $i++) {
            $intervals[] = $draws[$i] - $draws[$i - 1];
        }

        return count($intervals) > 0 ? array_sum($intervals) / count($intervals) : 0.0;
    }

    /**
     * Obtener sorteos por chip específico
     *
     * @return Collection<int, BingoDraw>
     */
    public function getByChip(string $chip) : Collection
    {
        return $this->model->where('chip', $chip)->get();
    }

    /**
     * Obtener sorteos por código específico
     *
     * @return Collection<int, BingoDraw>
     */
    public function getByCode(string $code) : Collection
    {
        return $this->model->where('code', $code)->get();
    }

    /**
     * Obtener sorteos por sala y estado
     *
     * @return Collection<int, BingoDraw>
     */
    public function getByRoomAndStatus(string $roomId, int $status) : Collection
    {
        return $this->model->where('room_id', $roomId)
            ->where('status', $status)
            ->get();
    }

    /**
     * Obtener sorteos por ronda
     *
     * @return Collection<int, BingoDraw>
     */
    public function getByRound(string $roomId, int $round) : Collection
    {
        return $this->model->where('room_id', $roomId)
            ->where('round_id', $round)
            ->get();
    }

    /**
     * Obtener sorteos por rango de tiempo
     *
     * @return Collection<int, BingoDraw>
     */
    public function getByTimeRange(string $roomId, int $startTime, int $endTime) : Collection
    {
        return $this->model->where('room_id', $roomId)
            ->whereBetween('time', [$startTime, $endTime])
            ->get();
    }

    /**
     * Obtener sorteos completados
     *
     * @return Collection<int, BingoDraw>
     */
    public function getCompletedDraws() : Collection
    {
        return $this->getByStatus(self::STATUS_COMPLETED);
    }

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
    public function getDrawHistory(string $roomId, int $page = 1, int $perPage = 50) : array
    {
        $offset = ($page - 1) * $perPage;
        $total = $this->countByRoom($roomId);

        $data = $this->model->where('room_id', $roomId)
            ->orderByDesc('created_at')
            ->offset($offset)
            ->limit($perPage)
            ->get();

        return [
            'data' => $data,
            'current_page' => $page,
            'total' => $total,
            'per_page' => $perPage,
        ];
    }

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
    public function getDrawProgress(string $roomId) : array
    {
        $totalDraws = $this->countByRoom($roomId);
        $completedDraws = $this->countByRoomAndStatus($roomId, self::STATUS_COMPLETED);
        $pendingDraws = $this->countByRoomAndStatus($roomId, self::STATUS_PENDING);
        $winningDraws = $this->countByRoomAndStatus($roomId, self::STATUS_WINNING);

        $progressPercentage = $totalDraws > 0 ? ($completedDraws / $totalDraws) * 100 : 0;

        return [
            'total_draws' => $totalDraws,
            'completed_draws' => $completedDraws,
            'pending_draws' => $pendingDraws,
            'winning_draws' => $winningDraws,
            'progress_percentage' => round($progressPercentage, 2),
        ];
    }

    /**
     * Obtener secuencia de sorteos por sala
     *
     * @return Collection<int, BingoDraw>
     */
    public function getDrawSequence(string $roomId) : Collection
    {
        return $this->model->where('room_id', $roomId)
            ->orderBy('time')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Obtener sorteos duplicados (mismo chip, sala, ronda)
     *
     * @return Collection<int, BingoDraw>
     */
    public function getDuplicateDraws(string $roomId) : Collection
    {
        return $this->model->where('room_id', $roomId)
            ->select('chip', 'room_id', 'round_id', DB::raw('COUNT(*) as count'))
            ->groupBy('chip', 'room_id', 'round_id')
            ->having('count', '>', 1)
            ->get();
    }

    /**
     * Obtener el primer chip sorteado de una sala
     */
    public function getFirstDrawChip(string $roomId) : ?BingoDraw
    {
        return $this->model->where('room_id', $roomId)->first();
    }

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
    public function getGeneralStats() : array
    {
        $totalDraws = $this->model->count();
        $totalRooms = $this->model->distinct('room_id')->count();

        $avgDrawsPerRoom = $totalRooms > 0 ? $totalDraws / $totalRooms : 0;

        $mostActiveRoom = $this->model->selectRaw('room_id, COUNT(*) as draw_count')
            ->groupBy('room_id')
            ->orderByDesc('draw_count')
            ->first();

        return [
            'total_draws' => $totalDraws,
            'total_rooms' => $totalRooms,
            'avg_draws_per_room' => round($avgDrawsPerRoom, 2),
            'most_active_room' => $mostActiveRoom->room_id ?? null,
        ];
    }

    /**
     * Obtener último sorteo de una sala
     */
    public function getLastDraw(string $roomId) : ?BingoDraw
    {
        return $this->model->where('room_id', $roomId)
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * Obtener sorteos pendientes
     *
     * @return Collection<int, BingoDraw>
     */
    public function getPendingDraws() : Collection
    {
        return $this->getByStatus(self::STATUS_PENDING);
    }

    /**
     * Obtener todos los sorteos de una sala
     *
     * @return Collection<int, BingoDraw>
     */
    public function getRoomDraws(string $roomId) : Collection
    {
        return $this->model->where('room_id', $roomId)->get();
    }

    /**
     * Obtener estadísticas de sorteos por ronda
     *
     * @return array{
     *     rounds: array,
     *     total_rounds: int,
     *     avg_draws_per_round: float
     * }
     */
    public function getRoundStats(string $roomId) : array
    {
        $rounds = $this->model->where('room_id', $roomId)
            ->selectRaw('round_id, COUNT(*) as draw_count')
            ->groupBy('round_id')
            ->orderBy('round_id')
            ->get()
            ->toArray();

        $totalRounds = count($rounds);
        $totalDraws = array_sum(array_column($rounds, 'draw_count'));
        $avgDrawsPerRound = $totalRounds > 0 ? $totalDraws / $totalRounds : 0;

        return [
            'rounds' => $rounds,
            'total_rounds' => $totalRounds,
            'avg_draws_per_round' => round($avgDrawsPerRound, 2),
        ];
    }

    /**
     * Obtener chips no sorteados de una sala
     *
     * @param array $allChips Array con todos los chips posibles
     */
    public function getUndrawnChips(string $roomId, array $allChips) : array
    {
        $drawnChips = $this->model->where('room_id', $roomId)
            ->pluck('chip')
            ->toArray();

        return array_diff($allChips, $drawnChips);
    }

    /**
     * Obtener el chip ganador de una sala
     */
    public function getWinningChip(string $roomId) : ?BingoDraw
    {
        return $this->model->where('room_id', $roomId)
            ->where('status', self::STATUS_WINNING)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Verificar si una ronda está completa
     */
    public function isRoundComplete(string $roomId, int $round) : bool
    {
        return $this->model->where('room_id', $roomId)
            ->where('round_id', $round)
            ->where('status', self::STATUS_COMPLETED)
            ->exists();
    }

    /**
     * Marcar chip como ganador
     */
    public function markAsWinning(string $drawId) : bool
    {
        return $this->updateDrawStatus($drawId, self::STATUS_WINNING);
    }

    /**
     * Buscar sorteos por término de búsqueda
     *
     * @return Collection<int, BingoDraw>
     */
    public function searchDraws(string $searchTerm) : Collection
    {
        return $this->model->where(function($query) use ($searchTerm) {
            $query->where('chip', 'like', "%{$searchTerm}%")
                ->orWhere('code', 'like', "%{$searchTerm}%")
                ->orWhere('room_id', 'like', "%{$searchTerm}%");
        })->get();
    }

    /**
     * Actualizar estado de sorteo
     */
    public function updateDrawStatus(string $drawId, int $newStatus) : bool
    {
        return $this->model->where('id', $drawId)
            ->update(['status' => $newStatus]) > 0;
    }

    /**
     * Validar secuencia de sorteos
     */
    public function validateDrawSequence(string $roomId) : bool
    {
        $draws = $this->getDrawSequence($roomId);
        $chips = $draws->pluck('chip')->toArray();

        // Verificar que no hay chips duplicados
        return count($chips) === count(array_unique($chips));
    }
}
