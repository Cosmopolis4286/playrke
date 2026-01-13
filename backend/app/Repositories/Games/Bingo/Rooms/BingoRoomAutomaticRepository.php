<?php

namespace App\Repositories\Games\Bingo\Rooms;

use App\Models\Games\Bingo\Rooms\BingoRoomAutomatic;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Games\Bingo\Rooms\Contracts\BingoRoomAutomaticRepositoryContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Repositorio para gestionar salas de bingo automáticas
 *
 * Implementa todas las operaciones de consulta y manipulación de datos
 * para las salas de bingo automáticas con configuración programada.
 *
 * @package App\Repositories\Games\Bingo\Rooms
 * @author Sistema PlayRKE
 * @version 1.0
 */
class BingoRoomAutomaticRepository extends BaseRepository implements BingoRoomAutomaticRepositoryContract
{
    /**
     * Estados de salas
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';

    /**
     * Constructor del repositorio
     *
     * @param BingoRoomAutomatic $model
     */
    public function __construct(BingoRoomAutomatic $model)
    {
        parent::__construct($model);
    }

    /**
     * Obtener salas por creador
     *
     * @param string $creatorType
     * @param string $creatorId
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByCreator(string $creatorType, string $creatorId): Collection
    {
        return $this->model->where('creator_type', $creatorType)
            ->where('creator_id', $creatorId)
            ->get();
    }

    /**
     * Obtener salas activas
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getActiveRooms(): Collection
    {
        return $this->getByStatus(self::STATUS_ACTIVE);
    }

    /**
     * Obtener salas inactivas
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getInactiveRooms(): Collection
    {
        return $this->getByStatus(self::STATUS_INACTIVE);
    }

    /**
     * Obtener salas por modo de juego
     *
     * @param string $gameMode
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByGameMode(string $gameMode): Collection
    {
        return $this->model->where('game_mode', $gameMode)->get();
    }

    /**
     * Obtener salas por tipo de premio
     *
     * @param string $prizePoolType
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByPrizePoolType(string $prizePoolType): Collection
    {
        return $this->model->where('prize_pool_type', $prizePoolType)->get();
    }

    /**
     * Obtener salas por fecha específica
     *
     * @param string $date
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByDate(string $date): Collection
    {
        return $this->model->whereDate('date', $date)->get();
    }

    /**
     * Obtener salas por rango de fechas
     *
     * @param string $startDate
     * @param string $endDate
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        return $this->model->whereBetween('date', [$startDate, $endDate])->get();
    }

    /**
     * Obtener salas programadas para hoy
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getTodayScheduledRooms(): Collection
    {
        return $this->getByDate(Carbon::today()->toDateString());
    }

    /**
     * Obtener salas por día de la semana
     *
     * @param string $day
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByDay(string $day): Collection
    {
        return $this->model->whereJsonContains('days', $day)->get();
    }

    /**
     * Obtener salas por hora específica
     *
     * @param string $time
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByTime(string $time): Collection
    {
        return $this->model->where('time', $time)->get();
    }

    /**
     * Obtener salas por tipo de sala
     *
     * @param string $roomType
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByRoomType(string $roomType): Collection
    {
        return $this->model->where('room_type', $roomType)->get();
    }

    /**
     * Obtener salas en vivo
     *
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getLiveRooms(): Collection
    {
        return $this->model->where('in_live', true)->get();
    }

    /**
     * Obtener salas por rango de precio de carta
     *
     * @param float $minPrice
     * @param float $maxPrice
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByCardPriceRange(float $minPrice, float $maxPrice): Collection
    {
        return $this->model->whereBetween('card_price', [$minPrice, $maxPrice])->get();
    }

    /**
     * Obtener salas que soportan una moneda específica
     *
     * @param string $currency
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getBySupportedCurrency(string $currency): Collection
    {
        return $this->model->whereJsonContains('allowed_currencies', $currency)->get();
    }

    /**
     * Obtener salas por número de rondas
     *
     * @param int $rounds
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByRounds(int $rounds): Collection
    {
        return $this->model->where('rounds', $rounds)->get();
    }

    /**
     * Obtener salas por patrón de bingo
     *
     * @param string $pattern
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getByBingoPattern(string $pattern): Collection
    {
        return $this->model->whereJsonContains('bingo_patterns', $pattern)->get();
    }

    /**
     * Obtener salas próximas a ejecutarse
     *
     * @param int $minutesAhead
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function getUpcomingRooms(int $minutesAhead = 30): Collection
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
     * Verificar si existe una sala por creador
     *
     * @param string $creatorType
     * @param string $creatorId
     * @return bool
     */
    public function existsByCreator(string $creatorType, string $creatorId): bool
    {
        return $this->model->where('creator_type', $creatorType)
            ->where('creator_id', $creatorId)
            ->exists();
    }

    /**
     * Contar salas por estado
     *
     * @param string $status
     * @return int
     */
    public function countByStatus(string $status): int
    {
        return $this->model->where('status', $status)->count();
    }

    /**
     * Contar salas por creador
     *
     * @param string $creatorType
     * @param string $creatorId
     * @return int
     */
    public function countByCreator(string $creatorType, string $creatorId): int
    {
        return $this->model->where('creator_type', $creatorType)
            ->where('creator_id', $creatorId)
            ->count();
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
    public function getRoomStats(): array
    {
        $totalRooms = $this->model->count();
        $activeRooms = $this->countByStatus(self::STATUS_ACTIVE);
        $inactiveRooms = $this->countByStatus(self::STATUS_INACTIVE);
        $liveRooms = $this->model->where('in_live', true)->count();
        $scheduledToday = $this->getTodayScheduledRooms()->count();

        $byGameMode = $this->model->selectRaw('game_mode, COUNT(*) as count')
            ->groupBy('game_mode')
            ->pluck('count', 'game_mode')
            ->toArray();

        $byRoomType = $this->model->selectRaw('room_type, COUNT(*) as count')
            ->groupBy('room_type')
            ->pluck('count', 'room_type')
            ->toArray();

        return [
            'total_rooms' => $totalRooms,
            'active_rooms' => $activeRooms,
            'inactive_rooms' => $inactiveRooms,
            'live_rooms' => $liveRooms,
            'scheduled_today' => $scheduledToday,
            'by_game_mode' => $byGameMode,
            'by_room_type' => $byRoomType
        ];
    }

    /**
     * Buscar salas por término de búsqueda
     *
     * @param string $searchTerm
     * @return Collection<int, BingoRoomAutomatic>
     */
    public function searchRooms(string $searchTerm): Collection
    {
        return $this->model->where(function ($query) use ($searchTerm) {
            $query->where('description', 'like', "%{$searchTerm}%")
                ->orWhere('room_type', 'like', "%{$searchTerm}%")
                ->orWhere('creator_user_id', 'like', "%{$searchTerm}%");
        })->get();
    }

    /**
     * Crear nueva sala automática
     *
     * @param array $roomData
     * @return BingoRoomAutomatic
     */
    public function createAutomaticRoom(array $roomData): BingoRoomAutomatic
    {
        return $this->create($roomData);
    }

    /**
     * Actualizar configuración de sala
     *
     * @param string $roomId
     * @param array $updateData
     * @return bool
     */
    public function updateRoomConfig(string $roomId, array $updateData): bool
    {
        return $this->model->where('id', $roomId)
            ->update($updateData) > 0;
    }

    /**
     * Actualizar estado de sala
     *
     * @param string $roomId
     * @param string $newStatus
     * @return bool
     */
    public function updateRoomStatus(string $roomId, string $newStatus): bool
    {
        return $this->updateRoomConfig($roomId, ['status' => $newStatus]);
    }

    /**
     * Activar sala
     *
     * @param string $roomId
     * @return bool
     */
    public function activateRoom(string $roomId): bool
    {
        return $this->updateRoomStatus($roomId, self::STATUS_ACTIVE);
    }

    /**
     * Desactivar sala
     *
     * @param string $roomId
     * @return bool
     */
    public function deactivateRoom(string $roomId): bool
    {
        return $this->updateRoomStatus($roomId, self::STATUS_INACTIVE);
    }

    /**
     * Duplicar configuración de sala
     *
     * @param string $roomId
     * @param array $overrideData
     * @return BingoRoomAutomatic
     */
    public function duplicateRoom(string $roomId, array $overrideData = []): BingoRoomAutomatic
    {
        $originalRoom = $this->find($roomId);

        if (!$originalRoom) {
            throw new \Exception("Room with ID {$roomId} not found");
        }

        $duplicateData = $originalRoom->toArray();
        unset($duplicateData['id'], $duplicateData['created_at'], $duplicateData['updated_at']);

        // Aplicar datos de sobrescritura
        $duplicateData = array_merge($duplicateData, $overrideData);

        return $this->createAutomaticRoom($duplicateData);
    }

    /**
     * Obtener salas agrupadas por modo de juego
     *
     * @return Collection
     */
    public function getGroupedByGameMode(): Collection
    {
        return $this->model->selectRaw('game_mode, COUNT(*) as count')
            ->groupBy('game_mode')
            ->orderBy('game_mode')
            ->get();
    }

    /**
     * Obtener salas agrupadas por tipo de sala
     *
     * @return Collection
     */
    public function getGroupedByRoomType(): Collection
    {
        return $this->model->selectRaw('room_type, COUNT(*) as count')
            ->groupBy('room_type')
            ->orderBy('room_type')
            ->get();
    }

    /**
     * Obtener salas agrupadas por día
     *
     * @return Collection
     */
    public function getGroupedByDay(): Collection
    {
        return $this->model->selectRaw('DATE(date) as day, COUNT(*) as count')
            ->groupBy('day')
            ->orderBy('day')
            ->get();
    }

    /**
     * Validar configuración de sala
     *
     * @param string $roomId
     * @return bool
     */
    public function validateRoomConfig(string $roomId): bool
    {
        $room = $this->find($roomId);

        if (!$room) {
            return false;
        }

        // Validaciones básicas
        if (empty($room->description) || empty($room->date) || empty($room->time)) {
            return false;
        }

        if ($room->card_price <= 0 || $room->rounds <= 0) {
            return false;
        }

        return true;
    }

    /**
     * Obtener precio promedio de cartas
     *
     * @return float
     */
    public function getAverageCardPrice(): float
    {
        return (float) $this->model->avg('card_price') ?? 0.0;
    }

    /**
     * Obtener rondas promedio
     *
     * @return float
     */
    public function getAverageRounds(): float
    {
        return (float) $this->model->avg('rounds') ?? 0.0;
    }

    /**
     * Limpiar salas vencidas
     *
     * @param int $daysOld
     * @return int Número de registros eliminados
     */
    public function cleanExpiredRooms(int $daysOld = 30): int
    {
        $cutoffDate = Carbon::now()->subDays($daysOld);

        return $this->model->where('date', '<', $cutoffDate->toDateString())
            ->where('status', self::STATUS_COMPLETED)
            ->delete();
    }

    /**
     * Exportar configuraciones de salas
     *
     * @param string $format 'json'|'csv'|'array'
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
     * Obtener horarios más populares
     *
     * @param int $limit
     * @return Collection
     */
    public function getMostPopularTimes(int $limit = 10): Collection
    {
        return $this->model->selectRaw('time, COUNT(*) as count')
            ->groupBy('time')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();
    }

    /**
     * Verificar conflictos de horarios
     *
     * @param string $date
     * @param string $time
     * @param string|null $excludeRoomId
     * @return bool
     */
    public function hasTimeConflict(string $date, string $time, ?string $excludeRoomId = null): bool
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
     * Obtener configuración recomendada
     *
     * @param string $gameMode
     * @return array
     */
    public function getRecommendedConfig(string $gameMode): array
    {
        $averages = $this->model->where('game_mode', $gameMode)
            ->selectRaw('
                AVG(card_price) as avg_card_price,
                AVG(rounds) as avg_rounds,
                AVG(time_chip) as avg_time_chip,
                AVG(rke_commn) as avg_rke_commn,
                AVG(room_commn) as avg_room_commn
            ')
            ->first();

        return [
            'recommended_card_price' => round($averages->avg_card_price ?? 1.0, 2),
            'recommended_rounds' => round($averages->avg_rounds ?? 10),
            'recommended_time_chip' => round($averages->avg_time_chip ?? 30),
            'recommended_rke_commn' => round($averages->avg_rke_commn ?? 5.0, 2),
            'recommended_room_commn' => round($averages->avg_room_commn ?? 10.0, 2)
        ];
    }
}
