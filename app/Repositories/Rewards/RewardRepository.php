<?php

declare(strict_types=1);

namespace App\Repositories\Rewards;

use App\Models\Rewards\Reward;
use App\Repositories\Contracts\Rewards\RewardRepositoryContract;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Repositorio para la gestión de recompensas
 *
 * Implementa todas las operaciones de base de datos relacionadas con
 * las recompensas del sistema.
 *
 * @package App\Repositories\Rewards
 * @author Sistema de Gestión de Recompensas
 * @version 1.0
 */
class RewardRepository extends BaseRepository implements RewardRepositoryContract
{
    public function __construct(Reward $model)
    {
        parent::__construct($model);
    }

    /**
     * Obtiene todas las recompensas de un usuario creador
     */
    public function getRewardsByCreator(string $userId, array $filters = []): Collection
    {
        $query = $this->model->where('user_id', $userId);

        // Aplicar filtros
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['reward_type'])) {
            $query->where('reward_type', $filters['reward_type']);
        }

        if (isset($filters['kit'])) {
            $query->where('kit', $filters['kit']);
        }

        return $query->with('user')
                    ->orderBy('name')
                    ->get();
    }

    /**
     * Obtiene recompensas por tipo
     */
    public function getRewardsByType(string $rewardType, array $filters = []): Collection
    {
        $query = $this->model->where('reward_type', $rewardType);

        // Aplicar filtros
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->with('user')
                    ->orderBy('name')
                    ->get();
    }

    /**
     * Obtiene recompensas por estado
     */
    public function getRewardsByStatus(string $status, array $filters = []): Collection
    {
        $query = $this->model->where('status', $status);

        // Aplicar filtros adicionales
        if (isset($filters['reward_type'])) {
            $query->where('reward_type', $filters['reward_type']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->with('user')
                    ->orderBy('name')
                    ->get();
    }

    /**
     * Busca una recompensa por código único
     */
    public function findByCode(string $code, ?string $userId = null): ?Reward
    {
        $query = $this->model->where('code', $code);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->with('user')->first();
    }

    /**
     * Obtiene recompensas que son kits
     */
    public function getRewardsKits(array $filters = []): Collection
    {
        $query = $this->model->where('kit', 'Y');

        // Aplicar filtros
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->with('user')
                    ->orderBy('name')
                    ->get();
    }

    /**
     * Obtiene recompensas que no son kits
     */
    public function getIndividualRewards(array $filters = []): Collection
    {
        $query = $this->model->where('kit', 'N');

        // Aplicar filtros
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->with('user')
                    ->orderBy('name')
                    ->get();
    }

    /**
     * Busca recompensas por nombre
     */
    public function searchRewardsByName(string $searchTerm, array $filters = []): Collection
    {
        $query = $this->model->where('name', 'LIKE', "%{$searchTerm}%");

        // Aplicar filtros
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->with('user')
                    ->orderBy('name')
                    ->get();
    }

    /**
     * Obtiene recompensas en un rango de precios
     */
    public function getRewardsByPriceRange(float $minPrice, float $maxPrice, array $filters = []): Collection
    {
        $query = $this->model->whereBetween('price', [$minPrice, $maxPrice]);

        // Aplicar filtros adicionales
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['reward_type'])) {
            $query->where('reward_type', $filters['reward_type']);
        }

        return $query->with('user')
                    ->orderBy('price')
                    ->get();
    }

    /**
     * Actualiza el estado de una recompensa
     */
    public function updateRewardStatus(string $rewardId, string $newStatus): bool
    {
        return $this->model->where('id', $rewardId)
                          ->update(['status' => $newStatus]) > 0;
    }

    /**
     * Verifica si un código existe para un usuario
     */
    public function codeExistsForUser(string $code, string $userId, ?string $excludeId = null): bool
    {
        $query = $this->model->where('code', $code)
                            ->where('user_id', $userId);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Obtiene estadísticas de recompensas por usuario
     */
    public function getRewardStatsByUser(string $userId): array
    {
        $stats = $this->model->where('user_id', $userId)
                            ->selectRaw('
                                COUNT(*) as total_rewards,
                                SUM(CASE WHEN status = "ACTIVE" THEN 1 ELSE 0 END) as active_rewards,
                                SUM(CASE WHEN status = "INACTIVE" THEN 1 ELSE 0 END) as inactive_rewards,
                                SUM(CASE WHEN reward_type = "PRODUCT" THEN 1 ELSE 0 END) as products,
                                SUM(CASE WHEN reward_type = "SERVICE" THEN 1 ELSE 0 END) as services,
                                SUM(CASE WHEN kit = "Y" THEN 1 ELSE 0 END) as kits,
                                SUM(price) as total_value
                            ')
                            ->first();

        return [
            'total_rewards' => $stats->total_rewards ?? 0,
            'active_rewards' => $stats->active_rewards ?? 0,
            'inactive_rewards' => $stats->inactive_rewards ?? 0,
            'products' => $stats->products ?? 0,
            'services' => $stats->services ?? 0,
            'kits' => $stats->kits ?? 0,
            'total_value' => floatval($stats->total_value ?? 0),
        ];
    }

    /**
     * Obtiene estadísticas generales de recompensas
     */
    public function getGeneralRewardStats(): array
    {
        $stats = $this->model->selectRaw('
                                COUNT(*) as total_rewards,
                                SUM(CASE WHEN status = "ACTIVE" THEN 1 ELSE 0 END) as active_rewards,
                                SUM(CASE WHEN status = "INACTIVE" THEN 1 ELSE 0 END) as inactive_rewards,
                                SUM(CASE WHEN reward_type = "PRODUCT" THEN 1 ELSE 0 END) as products,
                                SUM(CASE WHEN reward_type = "SERVICE" THEN 1 ELSE 0 END) as services,
                                SUM(CASE WHEN kit = "Y" THEN 1 ELSE 0 END) as kits,
                                COUNT(DISTINCT user_id) as total_creators,
                                AVG(price) as average_price,
                                SUM(price) as total_value
                            ')
                            ->first();

        return [
            'total_rewards' => $stats->total_rewards ?? 0,
            'active_rewards' => $stats->active_rewards ?? 0,
            'inactive_rewards' => $stats->inactive_rewards ?? 0,
            'products' => $stats->products ?? 0,
            'services' => $stats->services ?? 0,
            'kits' => $stats->kits ?? 0,
            'total_creators' => $stats->total_creators ?? 0,
            'average_price' => floatval($stats->average_price ?? 0),
            'total_value' => floatval($stats->total_value ?? 0),
        ];
    }

    /**
     * Cuenta recompensas por tipo
     */
    public function countRewardsByType(array $filters = []): array
    {
        $query = $this->model->newQuery();

        // Aplicar filtros
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        $counts = $query->select('reward_type', DB::raw('count(*) as total'))
                       ->groupBy('reward_type')
                       ->pluck('total', 'reward_type')
                       ->toArray();

        // Asegurar que todos los tipos estén presentes
        $validTypes = ['PRODUCT', 'SERVICE'];
        foreach ($validTypes as $type) {
            if (!isset($counts[$type])) {
                $counts[$type] = 0;
            }
        }

        return $counts;
    }

    /**
     * Cuenta recompensas por estado
     */
    public function countRewardsByStatus(array $filters = []): array
    {
        $query = $this->model->newQuery();

        // Aplicar filtros
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['reward_type'])) {
            $query->where('reward_type', $filters['reward_type']);
        }

        $counts = $query->select('status', DB::raw('count(*) as total'))
                       ->groupBy('status')
                       ->pluck('total', 'status')
                       ->toArray();

        // Asegurar que todos los estados estén presentes
        $validStatuses = ['ACTIVE', 'INACTIVE'];
        foreach ($validStatuses as $status) {
            if (!isset($counts[$status])) {
                $counts[$status] = 0;
            }
        }

        return $counts;
    }

    /**
     * Obtiene recompensas más caras
     */
    public function getMostExpensiveRewards(int $limit = 10, array $filters = []): Collection
    {
        $query = $this->model->newQuery();

        // Aplicar filtros
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->with('user')
                    ->orderBy('price', 'desc')
                    ->limit($limit)
                    ->get();
    }

    /**
     * Obtiene recompensas más baratas
     */
    public function getCheapestRewards(int $limit = 10, array $filters = []): Collection
    {
        $query = $this->model->newQuery();

        // Aplicar filtros
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->with('user')
                    ->where('price', '>', 0)
                    ->orderBy('price', 'asc')
                    ->limit($limit)
                    ->get();
    }

    /**
     * Obtiene recompensas creadas en un rango de fechas
     */
    public function getRewardsByDateRange(string $startDate, string $endDate, array $filters = []): Collection
    {
        $query = $this->model->whereBetween('created_at', [$startDate, $endDate]);

        // Aplicar filtros adicionales
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->with('user')
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Obtiene todas las recompensas con paginación
     */
    public function getPaginatedRewards(int $perPage = 15, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        // Aplicar filtros
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['reward_type'])) {
            $query->where('reward_type', $filters['reward_type']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['search'])) {
            $query->where('name', 'LIKE', "%{$filters['search']}%");
        }

        return $query->with('user')
                    ->orderBy('name')
                    ->paginate($perPage);
    }

    /**
     * Actualiza múltiples recompensas en lote
     */
    public function bulkUpdateRewards(array $rewardIds, array $updateData): int
    {
        return $this->model->whereIn('id', $rewardIds)
                          ->update($updateData);
    }

    /**
     * Elimina recompensas antiguas inactivas
     */
    public function deleteOldInactiveRewards(int $daysOld = 90): int
    {
        $cutoffDate = Carbon::now()->subDays($daysOld);

        return $this->model->where('status', 'INACTIVE')
                          ->where('updated_at', '<', $cutoffDate)
                          ->delete();
    }

    /**
     * Obtiene el valor total de recompensas de un usuario
     */
    public function getTotalValueByUser(string $userId, array $filters = []): float
    {
        $query = $this->model->where('user_id', $userId);

        // Aplicar filtros
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['reward_type'])) {
            $query->where('reward_type', $filters['reward_type']);
        }

        return floatval($query->sum('price'));
    }

    /**
     * Obtiene recompensas recientes
     */
    public function getRecentRewards(int $limit = 10, array $filters = []): Collection
    {
        $query = $this->model->newQuery();

        // Aplicar filtros
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->with('user')
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get();
    }

    /**
     * Busca recompensas por descripción
     */
    public function searchRewardsByDescription(string $searchTerm, array $filters = []): Collection
    {
        $query = $this->model->where('description', 'LIKE', "%{$searchTerm}%");

        // Aplicar filtros
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->with('user')
                    ->orderBy('name')
                    ->get();
    }
}