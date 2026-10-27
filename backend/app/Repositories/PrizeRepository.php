<?php

namespace App\Repositories;

use App\Contracts\PrizeRepositoryInterface;
use App\Models\Prize;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Repositorio para la gestión de recompensas
 *
 * Implementa todas las operaciones de base de datos relacionadas con
 * las recompensas del sistema.
 *
 * @author Sistema de Gestión de Recompensas
 *
 * @version 1.0
 */
class PrizeRepository extends BaseRepository implements PrizeRepositoryInterface
{
    public function __construct(Prize $model)
    {
        $this->model = $model;
    }

    /**
     * Actualiza múltiples recompensas en lote
     */
    public function bulkUpdatePrizes(array $rewardIds, array $updateData) : int
    {
        return $this->model->whereIn('id', $rewardIds)
            ->update($updateData);
    }

    /**
     * Verifica si un código existe para un usuario
     */
    public function codeExistsForUser(string $code, string $userId, ?string $excludeId = null) : bool
    {
        $query = $this->model->where('code', $code)
            ->where('user_id', $userId);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Cuenta recompensas por estado
     */
    public function countPrizesByStatus(array $filters = []) : array
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
            if (! isset($counts[$status])) {
                $counts[$status] = 0;
            }
        }

        return $counts;
    }

    /**
     * Cuenta recompensas por tipo
     */
    public function countPrizesByType(array $filters = []) : array
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
            if (! isset($counts[$type])) {
                $counts[$type] = 0;
            }
        }

        return $counts;
    }

    /**
     * Elimina recompensas antiguas inactivas
     */
    public function deleteOldInactivePrizes(int $daysOld = 90) : int
    {
        $cutoffDate = Carbon::now()->subDays($daysOld);

        return $this->model->where('status', 'INACTIVE')
            ->where('updated_at', '<', $cutoffDate)
            ->delete();
    }

    /**
     * Busca una recompensa por código único
     */
    public function findByCode(string $code, ?string $userId = null) : ?Prize
    {
        $query = $this->model->where('code', $code);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->with('user')->first();
    }

    /**
     * Obtiene recompensas más baratas
     */
    public function getCheapestPrizes(int $limit = 10, array $filters = []) : Collection
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
     * Obtiene estadísticas generales de recompensas
     */
    public function getGeneralPrizeStats() : array
    {
        $stats = $this->model->selectRaw('
                                COUNT(*) as total_prizes,
                                SUM(CASE WHEN status = "ACTIVE" THEN 1 ELSE 0 END) as active_prizes,
                                SUM(CASE WHEN status = "INACTIVE" THEN 1 ELSE 0 END) as inactive_prizes,
                                SUM(CASE WHEN reward_type = "PRODUCT" THEN 1 ELSE 0 END) as products,
                                SUM(CASE WHEN reward_type = "SERVICE" THEN 1 ELSE 0 END) as services,
                                SUM(CASE WHEN kit = "Y" THEN 1 ELSE 0 END) as kits,
                                COUNT(DISTINCT user_id) as total_creators,
                                AVG(price) as average_price,
                                SUM(price) as total_value
                            ')
            ->first();

        return [
            'total_prizes' => $stats->total_prizes ?? 0,
            'active_prizes' => $stats->active_prizes ?? 0,
            'inactive_prizes' => $stats->inactive_prizes ?? 0,
            'products' => $stats->products ?? 0,
            'services' => $stats->services ?? 0,
            'kits' => $stats->kits ?? 0,
            'total_creators' => $stats->total_creators ?? 0,
            'average_price' => floatval($stats->average_price ?? 0),
            'total_value' => floatval($stats->total_value ?? 0),
        ];
    }

    /**
     * Obtiene recompensas que no son kits
     */
    public function getIndividualPrizes(array $filters = []) : Collection
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
     * Obtiene recompensas más caras
     */
    public function getMostExpensivePrizes(int $limit = 10, array $filters = []) : Collection
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
     * Obtiene todas las recompensas con paginación
     */
    public function getPaginatedPrizes(int $perPage = 15, array $filters = []) : \Illuminate\Contracts\Pagination\LengthAwarePaginator
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
     * Obtiene todas las recompensas de un usuario creador
     */
    public function getPrizesByCreator(string $userId, array $filters = []) : Collection
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
     * Obtiene recompensas creadas en un rango de fechas
     */
    public function getPrizesByDateRange(string $startDate, string $endDate, array $filters = []) : Collection
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
     * Obtiene recompensas en un rango de precios
     */
    public function getPrizesByPriceRange(float $minPrice, float $maxPrice, array $filters = []) : Collection
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
     * Obtiene recompensas por estado
     */
    public function getPrizesByStatus(string $status, array $filters = []) : Collection
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
     * Obtiene recompensas por tipo
     */
    public function getPrizesByType(string $rewardType, array $filters = []) : Collection
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
     * Obtiene recompensas que son kits
     */
    public function getPrizesKits(array $filters = []) : Collection
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
     * Obtiene estadísticas de recompensas por usuario
     */
    public function getPrizeStatsByUser(string $userId) : array
    {
        $stats = $this->model->where('user_id', $userId)
            ->selectRaw('
                                COUNT(*) as total_prizes,
                                SUM(CASE WHEN status = "ACTIVE" THEN 1 ELSE 0 END) as active_prizes,
                                SUM(CASE WHEN status = "INACTIVE" THEN 1 ELSE 0 END) as inactive_prizes,
                                SUM(CASE WHEN reward_type = "PRODUCT" THEN 1 ELSE 0 END) as products,
                                SUM(CASE WHEN reward_type = "SERVICE" THEN 1 ELSE 0 END) as services,
                                SUM(CASE WHEN kit = "Y" THEN 1 ELSE 0 END) as kits,
                                SUM(price) as total_value
                            ')
            ->first();

        return [
            'total_prizes' => $stats->total_prizes ?? 0,
            'active_prizes' => $stats->active_prizes ?? 0,
            'inactive_prizes' => $stats->inactive_prizes ?? 0,
            'products' => $stats->products ?? 0,
            'services' => $stats->services ?? 0,
            'kits' => $stats->kits ?? 0,
            'total_value' => floatval($stats->total_value ?? 0),
        ];
    }

    /**
     * Obtiene recompensas recientes
     */
    public function getRecentPrizes(int $limit = 10, array $filters = []) : Collection
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
     * Obtiene el valor total de recompensas de un usuario
     */
    public function getTotalValueByUser(string $userId, array $filters = []) : float
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
     * Busca recompensas por descripción
     */
    public function searchPrizesByDescription(string $searchTerm, array $filters = []) : Collection
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

    /**
     * Busca recompensas por nombre
     */
    public function searchPrizesByName(string $searchTerm, array $filters = []) : Collection
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
     * Actualiza el estado de una recompensa
     */
    public function updatePrizeStatus(string $rewardId, string $newStatus) : bool
    {
        return $this->model->where('id', $rewardId)
            ->update(['status' => $newStatus]) > 0;
    }
}
