<?php

namespace App\Repositories\Games\Bingo\Creators;

use App\Models\Games\Bingo\Creators\BingoCreator;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Games\Bingo\Creators\Contracts\BingoCreatorRepositoryContract;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repositorio para gestionar creadores de salas de bingo
 *
 * Implementa todas las operaciones de consulta y manipulación de datos
 * para los creadores de salas de bingo siguiendo el patrón Repository.
 *
 * @package App\Repositories\Games\Bingo\Creators
 * @author Sistema PlayRKE
 * @version 1.0
 */
class BingoCreatorRepository extends BaseRepository implements BingoCreatorRepositoryContract
{
    /**
     * Constructor del repositorio
     *
     * @param BingoCreator $model
     */
    public function __construct(BingoCreator $model)
    {
        parent::__construct($model);
    }

    /**
     * Obtener creador por sala
     *
     * @param string $roomId
     * @return BingoCreator|null
     */
    public function findByRoom(string $roomId): ?BingoCreator
    {
        return $this->model->where('room', $roomId)->first();
    }

    /**
     * Obtener creador por sala y usuario creador
     *
     * @param string $roomId
     * @param string $creatorUserId
     * @return BingoCreator|null
     */
    public function findByRoomAndCreatorUser(string $roomId, string $creatorUserId): ?BingoCreator
    {
        return $this->model->where('room', $roomId)
            ->where('creator_user_id', $creatorUserId)
            ->first();
    }

    /**
     * Obtener creador por tipo y ID de creador específico en una sala
     *
     * @param string $roomId
     * @param string $creatorType
     * @param string $creatorId
     * @return BingoCreator|null
     */
    public function findByRoomAndCreator(string $roomId, string $creatorType, string $creatorId): ?BingoCreator
    {
        return $this->model->where('room', $roomId)
            ->where('creator_type', $creatorType)
            ->where('creator_id', $creatorId)
            ->first();
    }

    /**
     * Verificar si existe un creador para una sala y usuario específicos
     *
     * @param string $roomId
     * @param string $creatorUserId
     * @return bool
     */
    public function existsByRoomAndCreatorUser(string $roomId, string $creatorUserId): bool
    {
        return $this->model->where('room', $roomId)
            ->where('creator_user_id', $creatorUserId)
            ->exists();
    }

    /**
     * Obtener todos los creadores por tipo
     *
     * @param string $creatorType
     * @return Collection<int, BingoCreator>
     */
    public function getByCreatorType(string $creatorType): Collection
    {
        return $this->model->where('creator_type', $creatorType)->get();
    }

    /**
     * Obtener creadores activos
     *
     * @return Collection<int, BingoCreator>
     */
    public function getActiveCreators(): Collection
    {
        return $this->getByStatus('active');
    }

    /**
     * Obtener creadores inactivos
     *
     * @return Collection<int, BingoCreator>
     */
    public function getInactiveCreators(): Collection
    {
        return $this->getByStatus('inactive');
    }

    /**
     * Obtener creadores por usuario específico
     *
     * @param string $creatorUserId
     * @return Collection<int, BingoCreator>
     */
    public function getByCreatorUser(string $creatorUserId): Collection
    {
        return $this->model->where('creator_user_id', $creatorUserId)->get();
    }

    /**
     * Obtener creadores por ID de creador específico
     *
     * @param string $creatorId
     * @return Collection<int, BingoCreator>
     */
    public function getByCreatorId(string $creatorId): Collection
    {
        return $this->model->where('creator_id', $creatorId)->get();
    }

    /**
     * Obtener creadores por rango de precios de cartas
     *
     * @param float $minPrice
     * @param float $maxPrice
     * @return Collection<int, BingoCreator>
     */
    public function getByCardPriceRange(float $minPrice, float $maxPrice): Collection
    {
        return $this->model->whereBetween('card_price', [$minPrice, $maxPrice])->get();
    }

    /**
     * Obtener creadores que soportan una moneda específica
     *
     * @param string $currencyCode
     * @return Collection<int, BingoCreator>
     */
    public function getBySupportedCurrency(string $currencyCode): Collection
    {
        return $this->model->whereJsonContains('allowed_currencies', $currencyCode)->get();
    }

    /**
     * Actualizar estado de un creador
     *
     * @param string $creatorId
     * @param string $newStatus
     * @return bool
     */
    public function updateStatus(string $creatorId, string $newStatus): bool
    {
        return $this->model->where('id', $creatorId)
            ->update(['status' => $newStatus]) > 0;
    }

    /**
     * Actualizar precio de carta de un creador
     *
     * @param string $creatorId
     * @param float $newPrice
     * @return bool
     */
    public function updateCardPrice(string $creatorId, float $newPrice): bool
    {
        return $this->model->where('id', $creatorId)
            ->update(['card_price' => $newPrice]) > 0;
    }

    /**
     * Actualizar monedas permitidas de un creador
     *
     * @param string $creatorId
     * @param array $allowedCurrencies
     * @return bool
     */
    public function updateAllowedCurrencies(string $creatorId, array $allowedCurrencies): bool
    {
        return $this->model->where('id', $creatorId)
            ->update(['allowed_currencies' => $allowedCurrencies]) > 0;
    }

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
    public function getCreatorStats(): array
    {
        $totalCreators = $this->model->count();
        $activeCreators = $this->model->where('status', 'active')->count();
        $inactiveCreators = $this->model->where('status', 'inactive')->count();

        $byType = $this->model->selectRaw('creator_type, COUNT(*) as count')
            ->groupBy('creator_type')
            ->pluck('count', 'creator_type')
            ->toArray();

        return [
            'total_creators' => $totalCreators,
            'active_creators' => $activeCreators,
            'inactive_creators' => $inactiveCreators,
            'by_type' => $byType
        ];
    }

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
    public function getCommissionStats(string $creatorId): array
    {
        $creator = $this->model->where('creator_id', $creatorId)->first();

        if (!$creator) {
            return [
                'total_commission' => 0.0,
                'average_commission' => 0.0,
                'room_count' => 0
            ];
        }

        $roomCount = $this->model->where('creator_id', $creatorId)->count();
        $totalCommission = $this->model->where('creator_id', $creatorId)
            ->sum('creator_commn');
        $averageCommission = $roomCount > 0 ? $totalCommission / $roomCount : 0.0;

        return [
            'total_commission' => (float) $totalCommission,
            'average_commission' => (float) $averageCommission,
            'room_count' => $roomCount
        ];
    }

    /**
     * Obtener creadores con mayor actividad
     *
     * @param int $limit
     * @return Collection<int, BingoCreator>
     */
    public function getTopCreatorsByActivity(int $limit = 10): Collection
    {
        return $this->model->selectRaw('creator_user_id, COUNT(*) as room_count')
            ->groupBy('creator_user_id')
            ->orderByDesc('room_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Buscar creadores por término de búsqueda
     *
     * @param string $searchTerm
     * @return Collection<int, BingoCreator>
     */
    public function searchCreators(string $searchTerm): Collection
    {
        return $this->model->where(function ($query) use ($searchTerm) {
            $query->where('creator_user_id', 'like', "%{$searchTerm}%")
                ->orWhere('creator_id', 'like', "%{$searchTerm}%")
                ->orWhere('room', 'like', "%{$searchTerm}%");
        })->get();
    }

    /**
     * Obtener historial de cambios de precios de un creador
     *
     * @param string $creatorId
     * @return Collection
     */
    public function getPriceHistory(string $creatorId): Collection
    {
        return $this->model->where('creator_id', $creatorId)
            ->orderBy('updated_at', 'desc')
            ->get(['card_price', 'updated_at']);
    }

    /**
     * Verificar conflictos de creadores en una sala
     *
     * @param string $roomId
     * @return bool
     */
    public function hasCreatorConflicts(string $roomId): bool
    {
        return $this->model->where('room', $roomId)->count() > 1;
    }

    /**
     * Obtener total de salas gestionadas por un creador
     *
     * @param string $creatorUserId
     * @return int
     */
    public function getTotalRoomsByCreator(string $creatorUserId): int
    {
        return $this->model->where('creator_user_id', $creatorUserId)->count();
    }

    /**
     * Obtener valor promedio de comisiones
     *
     * @return float
     */
    public function getAverageCommission(): float
    {
        return (float) $this->model->avg('creator_commn') ?? 0.0;
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
}
