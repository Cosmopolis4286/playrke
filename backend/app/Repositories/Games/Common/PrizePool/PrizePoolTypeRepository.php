<?php

namespace App\Repositories\Games\Common\PrizePool;

use App\Models\Games\Common\PrizePool\PrizePoolType;
use App\Repositories\BaseRepository;
use App\Contracts\Games\Common\PrizePool\PrizePoolTypeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Repositorio para la gestión de tipos de pool de premios.
 *
 * Implementa las operaciones específicas para el modelo PrizePoolType,
 * incluyendo gestión de estados, búsquedas y estadísticas de uso.
 */
class PrizePoolTypeRepository extends BaseRepository implements PrizePoolTypeRepositoryInterface
{
    /**
     * Constructor del repositorio.
     *
     * @param PrizePoolType $model Instancia del modelo PrizePoolType
     */
    public function __construct(PrizePoolType $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritDoc}
     */
    public function getActiveTypes(): Collection
    {
        return $this->getByStatus('ACTIVE');
    }

    /**
     * {@inheritDoc}
     */
    public function getInactiveTypes(): Collection
    {
        return $this->getByStatus('INACTIVE');
    }

    /**
     * {@inheritDoc}
     */
    public function getByLabel(string $label): ?PrizePoolType
    {
        return $this->model->where('label', $label)->first();
    }

    /**
     * {@inheritDoc}
     */
    public function searchByLabel(string $searchTerm): Collection
    {
        return $this->model->where('label', 'LIKE', "%{$searchTerm}%")->get();
    }

    /**
     * {@inheritDoc}
     */
    public function existsByLabel(string $label, ?string $excludeId = null): bool
    {
        $query = $this->model->where('label', $label);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function createType(string $label, string $status = 'ACTIVE'): PrizePoolType
    {
        return $this->model->create([
            'label' => $this->normalizeLabel($label),
            'status' => $status
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function updateLabel(string $typeId, string $label): bool
    {
        return $this->model->where('id', $typeId)
            ->update(['label' => $this->normalizeLabel($label)]) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function updateStatus(string $typeId, string $status): bool
    {
        return $this->model->where('id', $typeId)
            ->update(['status' => $status]) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function activateType(string $typeId): bool
    {
        return $this->updateStatus($typeId, 'ACTIVE');
    }

    /**
     * {@inheritDoc}
     */
    public function deactivateType(string $typeId): bool
    {
        return $this->updateStatus($typeId, 'INACTIVE');
    }

    /**
     * {@inheritDoc}
     */
    public function deleteType(string $typeId): bool
    {
        return $this->model->where('id', $typeId)->delete() > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function bulkUpdateStatus(array $typeIds, string $status): int
    {
        return $this->model->whereIn('id', $typeIds)
            ->update(['status' => $status]);
    }

    /**
     * {@inheritDoc}
     */
    public function getTypeStatistics(): array
    {
        $total = $this->model->count();
        $active = $this->model->where('status', 'ACTIVE')->count();
        $inactive = $this->model->where('status', 'INACTIVE')->count();
        $averageLabelLength = $this->model->avg(DB::raw('LENGTH(label)'));
        $longestLabel = $this->model->orderByRaw('LENGTH(label) DESC')->first();
        $shortestLabel = $this->model->orderByRaw('LENGTH(label) ASC')->first();

        return [
            'total_types' => $total,
            'active_types' => $active,
            'inactive_types' => $inactive,
            'active_percentage' => $total > 0 ? round(($active / $total) * 100, 2) : 0,
            'average_label_length' => round($averageLabelLength ?? 0, 2),
            'longest_label' => $longestLabel?->label ?? '',
            'shortest_label' => $shortestLabel?->label ?? '',
            'longest_label_length' => $longestLabel ? strlen($longestLabel->label) : 0,
            'shortest_label_length' => $shortestLabel ? strlen($shortestLabel->label) : 0
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getRecentTypes(int $days = 30): Collection
    {
        return $this->model->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrderedByLabel(bool $activeOnly = false): Collection
    {
        $query = $this->model->orderBy('label');

        if ($activeOnly) {
            $query->where('status', 'ACTIVE');
        }

        return $query->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getMostUsedTypes(int $limit = 10): Collection
    {
        return $this->model->select('prize_pool_types.*')
            ->selectRaw('COUNT(game_prize_pool_types.id) as usage_count')
            ->leftJoin('game_prize_pool_types', 'prize_pool_types.id', '=', 'game_prize_pool_types.prize_pool_type_id')
            ->groupBy('prize_pool_types.id')
            ->orderBy('usage_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getUnusedTypes(): Collection
    {
        return $this->model->select('prize_pool_types.*')
            ->leftJoin('game_prize_pool_types', 'prize_pool_types.id', '=', 'game_prize_pool_types.prize_pool_type_id')
            ->whereNull('game_prize_pool_types.id')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function isTypeInUse(string $typeId): bool
    {
        return DB::table('game_prize_pool_types')
            ->where('prize_pool_type_id', $typeId)
            ->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function getGameCountByType(string $typeId): int
    {
        return DB::table('game_prize_pool_types')
            ->where('prize_pool_type_id', $typeId)
            ->count();
    }

    /**
     * {@inheritDoc}
     */
    public function getTypesWithUsageStats(bool $activeOnly = true): Collection
    {
        $query = $this->model->select('prize_pool_types.*')
            ->selectRaw('COUNT(game_prize_pool_types.id) as usage_count')
            ->selectRaw('COUNT(DISTINCT game_prize_pool_types.game_id) as unique_games')
            ->leftJoin('game_prize_pool_types', 'prize_pool_types.id', '=', 'game_prize_pool_types.prize_pool_type_id')
            ->groupBy('prize_pool_types.id')
            ->orderBy('usage_count', 'desc');

        if ($activeOnly) {
            $query->where('prize_pool_types.is_active', true);
        }

        return $query->get();
    }

    /**
     * {@inheritDoc}
     */
    public function createBulkTypes(array $labels, string $status = 'ACTIVE'): Collection
    {
        $types = collect();

        foreach ($labels as $label) {
            $normalizedLabel = $this->normalizeLabel($label);

            // Verificar si ya existe antes de crear
            if (!$this->existsByLabel($normalizedLabel)) {
                $type = $this->createType($normalizedLabel, $status);
                $types->push($type);
            }
        }

        return $types;
    }

    /**
     * {@inheritDoc}
     */
    public function getForSelect(bool $activeOnly = true): array
    {
        $query = $this->model->orderBy('label');

        if ($activeOnly) {
            $query->where('status', 'ACTIVE');
        }

        return $query->pluck('label', 'id')->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function isValidLabel(string $label): bool
    {
        $normalized = $this->normalizeLabel($label);

        // Verificar longitud
        if (strlen($normalized) < 2 || strlen($normalized) > 50) {
            return false;
        }

        // Verificar caracteres válidos (letras, números, espacios, guiones)
        if (!preg_match('/^[\p{L}\p{N}\s\-_]+$/u', $normalized)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function normalizeLabel(string $label): string
    {
        // Limpiar espacios extra
        $normalized = trim($label);

        // Convertir a título (primera letra de cada palabra en mayúscula)
        $normalized = Str::title($normalized);

        // Reemplazar múltiples espacios con uno solo
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return $normalized;
    }

    /**
     * {@inheritDoc}
     */
    public function getLabelSuggestions(string $partialText, int $limit = 5): Collection
    {
        return $this->model->where('label', 'LIKE', "%{$partialText}%")
            ->where('status', 'ACTIVE')
            ->orderBy('label')
            ->limit($limit)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function archiveOldUnusedTypes(int $daysOld): int
    {
        $cutoffDate = now()->subDays($daysOld);

        $unusedTypeIds = $this->model->select('id')
            ->leftJoin('game_prize_pool_types', 'prize_pool_types.id', '=', 'game_prize_pool_types.prize_pool_type_id')
            ->whereNull('game_prize_pool_types.id')
            ->where('prize_pool_types.created_at', '<', $cutoffDate)
            ->where('prize_pool_types.is_active', true)
            ->pluck('id');

        if ($unusedTypeIds->isNotEmpty()) {
            return $this->bulkUpdateStatus($unusedTypeIds->toArray(), false);
        }

        return 0;
    }

    /**
     * {@inheritDoc}
     */
    public function restoreArchivedTypes(array $typeIds): int
    {
        return $this->bulkUpdateStatus($typeIds, 'ACTIVE');
    }
}
