<?php

namespace App\Domains\Games\Bingo\Repositories\Room;

use App\Domains\Games\Bingo\Contracts\Room\BingoRoomAutomaticRepositoryInterface;
use App\Domains\Games\Bingo\Models\Room\BingoRoomAutomatic;
use App\Repositories\BaseRepository;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Repositorio para gestionar salas de bingo automáticas
 *
 * Implementa todas las operaciones de consulta y manipulación de datos
 * para las salas de bingo automáticas con configuración programada.
 *
 * @author Sistema PlayRKE
 *
 * @version 1.0
 */
class BingoRoomAutomaticRepository extends BaseRepository implements BingoRoomAutomaticRepositoryInterface
{
    /**
     * Estados de salas
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_RUNNING = 'running';
    public const STATUS_SCHEDULED = 'scheduled';

    /**
     * Constructor del repositorio
     */
    public function __construct(BingoRoomAutomatic $model)
    {
        $this->model = $model;
    }

    /**
     * Activar sala
     */
    public function activateRoom(string $roomId) : bool
    {
        return $this->updateRoomStatus($roomId, self::STATUS_ACTIVE);
    }

    /**
     * Limpiar salas vencidas
     *
     * @return int Número de registros eliminados
     */
    public function cleanExpiredRooms(int $daysOld = 30) : int
    {
        $cutoffDate = Carbon::now()->subDays($daysOld);

        return $this->model->where('date', '<', $cutoffDate->toDateString())
            ->where('status', self::STATUS_COMPLETED)
            ->delete();
    }

    /**
     * Contar salas por creador
     */
    public function countByCreator(string $creatorType, string $creatorId) : int
    {
        return $this->model->where('creator_user_id', $creatorId)
            ->count();
    }

    /**
     * Contar salas por estado
     */
    public function countByStatus(string $status) : int
    {
        return $this->model->where('status', $status)->count();
    }

    /**
     * Crear nueva sala automática
     */
    public function createAutomaticRoom(array $roomData) : BingoRoomAutomatic
    {
        return $this->create($roomData);
    }

    /**
     * Desactivar sala
     */
    public function deactivateRoom(string $roomId) : bool
    {
        return $this->updateRoomStatus($roomId, self::STATUS_INACTIVE);
    }

    /**
     * Duplicar configuración de sala
     */
    public function duplicateRoom(string $roomId, array $overrideData = []) : BingoRoomAutomatic
    {
        $originalRoom = $this->find($roomId);

        if (! $originalRoom) {
            throw new Exception("Room with ID {$roomId} not found");
        }

        $duplicateData = $originalRoom->toArray();
        unset($duplicateData['id'], $duplicateData['created_at'], $duplicateData['updated_at']);

        // Aplicar datos de sobrescritura
        $duplicateData = array_merge($duplicateData, $overrideData);

        return $this->createAutomaticRoom($duplicateData);
    }

    /**
     * Verificar si existe una sala por creador
     */
    public function existsByCreator(string $creatorType, string $creatorId) : bool
    {
        return $this->model->where('creator_user_id', $creatorId)
            ->exists();
    }

    /**
     * Exportar configuraciones de salas
     *
     * @param string $format 'json'|'csv'|'array'
     *
     * @return mixed
     */
    public function exportRoomConfigs(string $format = 'array')
    {
        $rooms = $this->getAll();

        switch ($format) {
            case 'json':
                return $rooms->toJson();
            case 'csv':
                // Implementar exportación CSV si es necesario
                return $rooms->toArray();
            case 'array':
            default:
                return $rooms->toArray();
        }
    }

    /**
     * Obtener salas activas
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getActiveRooms() : Collection
    {
        return $this->getByStatus(self::STATUS_ACTIVE);
    }

    /**
     * Obtener precio promedio de cartas
     */
    public function getAverageCardPrice() : float
    {
        return (float) $this->model->avg('card_price') ?? 0.0;
    }

    /**
     * Obtener rondas promedio
     */
    public function getAverageRounds() : float
    {
        return (float) $this->model->avg('rounds') ?? 0.0;
    }

    /**
     * Obtener salas por patrón de bingo
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByBingoPattern(string $pattern) : Collection
    {
        return $this->model->whereJsonContains('bingo_patterns', $pattern)->get();
    }

    /**
     * Obtener salas por rango de precio de carta
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByCardPriceRange(float $minPrice, float $maxPrice) : Collection
    {
        return $this->model->whereBetween('card_price', [$minPrice, $maxPrice])->get();
    }

    /**
     * Obtener salas por creador.
     *
     * bingo_rooms_automatic no tiene columnas creator_type/creator_id,
     * solo creator_user_id; se conserva $creatorType en la firma por
     * compatibilidad con el contrato, sin usarlo como filtro.
     *
     * @param string $creatorType sin uso (ver nota arriba)
     * @param string $creatorId   ID del usuario creador (creator_user_id)
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByCreator(string $creatorType, string $creatorId) : Collection
    {
        return $this->model->where('creator_user_id', $creatorId)
            ->get();
    }

    /**
     * Obtener salas por fecha específica
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByDate(string $date) : Collection
    {
        return $this->model->whereDate('date', $date)->get();
    }

    /**
     * Obtener salas por rango de fechas
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByDateRange(string $startDate, string $endDate) : Collection
    {
        return $this->model->whereBetween('date', [$startDate, $endDate])->get();
    }

    /**
     * Obtener salas por día de la semana
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByDay(string $day) : Collection
    {
        return $this->model->whereJsonContains('days', $day)->get();
    }

    /**
     * Obtener salas por modo de juego
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByGameMode(string $gameMode) : Collection
    {
        return $this->model->where('connection_type_id', $gameMode)->get();
    }

    /**
     * Obtener salas por tipo de premio
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByPrizePoolType(string $prizePoolType) : Collection
    {
        return $this->model->where('prize_pool_type_id', $prizePoolType)->get();
    }

    /**
     * Obtener salas por tipo de sala
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByRoomType(string $roomType) : Collection
    {
        return $this->model->where('room_type_id', $roomType)->get();
    }

    /**
     * Obtener salas por número de rondas
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByRounds(int $rounds) : Collection
    {
        return $this->model->where('rounds', $rounds)->get();
    }

    /**
     * Obtener salas que soportan una moneda específica
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getBySupportedCurrency(string $currency) : Collection
    {
        return $this->model->whereJsonContains('allowed_currencies', $currency)->get();
    }

    /**
     * Obtener salas por hora específica
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByTime(string $time) : Collection
    {
        return $this->model->where('time', $time)->get();
    }

    /**
     * Obtener salas agrupadas por día
     */
    public function getGroupedByDay() : Collection
    {
        return $this->model->selectRaw('DATE(date) as day, COUNT(*) as count')
            ->groupBy('day')
            ->orderBy('day')
            ->get();
    }

    /**
     * Obtener salas agrupadas por modo de juego
     */
    public function getGroupedByGameMode() : Collection
    {
        return $this->model->selectRaw('connection_type_id, COUNT(*) as count')
            ->groupBy('connection_type_id')
            ->orderBy('connection_type_id')
            ->get();
    }

    /**
     * Obtener salas agrupadas por tipo de sala
     */
    public function getGroupedByRoomType() : Collection
    {
        return $this->model->selectRaw('room_type_id, COUNT(*) as count')
            ->groupBy('room_type_id')
            ->orderBy('room_type_id')
            ->get();
    }

    /**
     * Obtener salas inactivas
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getInactiveRooms() : Collection
    {
        return $this->getByStatus(self::STATUS_INACTIVE);
    }

    /**
     * Obtener salas en vivo
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getLiveRooms() : Collection
    {
        return $this->model->where('in_live', 'Y')->get();
    }

    /**
     * Obtener horarios más populares
     */
    public function getMostPopularTimes(int $limit = 10) : Collection
    {
        return $this->model->selectRaw('time, COUNT(*) as count')
            ->groupBy('time')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener configuración recomendada
     */
    public function getRecommendedConfig(string $gameMode) : array
    {
        $averages = $this->model->where('connection_type_id', $gameMode)
            ->selectRaw('
                AVG(card_price) as avg_card_price,
                AVG(rounds) as avg_rounds,
                AVG(time_chip) as avg_time_chip,
                AVG(rke_commission) as avg_rke_commission,
                AVG(room_commission) as avg_room_commission
            ')
            ->first();

        return [
            'recommended_card_price' => round($averages->avg_card_price ?? 1.0, 2),
            'recommended_rounds' => round($averages->avg_rounds ?? 10),
            'recommended_time_chip' => round($averages->avg_time_chip ?? 30),
            'recommended_rke_commission' => round($averages->avg_rke_commission ?? 5.0, 2),
            'recommended_room_commission' => round($averages->avg_room_commission ?? 10.0, 2),
        ];
    }

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
    public function getRoomStats() : array
    {
        $totalRooms = $this->model->count();
        $activeRooms = $this->countByStatus(self::STATUS_ACTIVE);
        $inactiveRooms = $this->countByStatus(self::STATUS_INACTIVE);
        $liveRooms = $this->model->where('in_live', 'Y')->count();
        $scheduledToday = $this->getTodayScheduledRooms()->count();

        $byGameMode = $this->model->selectRaw('connection_type_id, COUNT(*) as count')
            ->groupBy('connection_type_id')
            ->pluck('count', 'connection_type_id')
            ->toArray();

        $byRoomType = $this->model->selectRaw('room_type_id, COUNT(*) as count')
            ->groupBy('room_type_id')
            ->pluck('count', 'room_type_id')
            ->toArray();

        return [
            'total_rooms' => $totalRooms,
            'active_rooms' => $activeRooms,
            'inactive_rooms' => $inactiveRooms,
            'live_rooms' => $liveRooms,
            'scheduled_today' => $scheduledToday,
            'by_game_mode' => $byGameMode,
            'by_room_type' => $byRoomType,
        ];
    }

    /**
     * Obtener salas programadas para hoy
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getTodayScheduledRooms() : Collection
    {
        return $this->getByDate(Carbon::today()->toDateString());
    }

    /**
     * Obtener salas próximas a ejecutarse
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getUpcomingRooms(int $minutesAhead = 30) : Collection
    {
        $now = Carbon::now();
        $futureTime = $now->copy()->addMinutes($minutesAhead);

        return $this->model->where('status', self::STATUS_ACTIVE)
            ->where('date', '>=', $now->toDateString())
            ->where('time', '>=', $now->toTimeString())
            ->where('time', '<=', $futureTime->toTimeString())
            ->get();
    }

    /**
     * Verificar conflictos de horarios
     */
    public function hasTimeConflict(string $date, string $time, ?string $excludeRoomId = null) : bool
    {
        $query = $this->model->where('date', $date)
            ->where('time', $time)
            ->where('status', self::STATUS_ACTIVE);

        if ($excludeRoomId) {
            $query->where('id', '!=', $excludeRoomId);
        }

        return $query->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function paginateSummaryGameRooms(
        int $perPage,
        int $page,
        array $conditions,
        array $filters,
        array $relations,
        array $fields,
    ) : \Illuminate\Pagination\LengthAwarePaginator {
        $query = $this->model->newQuery()
            ->select($fields)
            ->with($relations);

        foreach ($conditions as $field => $value) {
            $query->where($field, $value);
        }

        // room_type_id/connection_type_id/prize_pool_type_id/status son columnas propias
        // de bingo_rooms_automatic (no requieren whereHas sobre una relación).
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['room_type_id'])) {
            $query->where('room_type_id', $filters['room_type_id']);
        }

        if (! empty($filters['connection_type_id'])) {
            $query->where('connection_type_id', $filters['connection_type_id']);
        }

        if (! empty($filters['prize_pool_type_id'])) {
            $query->where('prize_pool_type_id', $filters['prize_pool_type_id']);
        }

        if (! empty($filters['currency_id'])) {
            $query->whereJsonContains('allowed_currencies', (string) $filters['currency_id']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Buscar salas por término de búsqueda
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function searchRooms(string $searchTerm) : Collection
    {
        return $this->model->where(function($query) use ($searchTerm) {
            $query->where('description', 'like', "%{$searchTerm}%")
                ->orWhere('room_type', 'like', "%{$searchTerm}%")
                ->orWhere('creator_user_id', 'like', "%{$searchTerm}%");
        })->get();
    }

    /**
     * Actualizar configuración de sala
     */
    public function updateRoomConfig(string $roomId, array $updateData) : bool
    {
        return $this->model->where('id', $roomId)
            ->update($updateData) > 0;
    }

    /**
     * Actualizar estado de sala
     */
    public function updateRoomStatus(string $roomId, string $newStatus) : bool
    {
        return $this->updateRoomConfig($roomId, ['status' => $newStatus]);
    }

    /**
     * Validar configuración de sala
     */
    public function validateRoomConfig(string $roomId) : bool
    {
        $room = $this->find($roomId);

        if (! $room) {
            return false;
        }

        // Validaciones básicas
        if (empty($room->description) || empty($room->date) || empty($room->time)) {
            return false;
        }

        return ! ($room->card_price <= 0 || $room->rounds <= 0);
    }
}
