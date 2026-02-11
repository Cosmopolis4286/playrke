<?php

namespace App\Repositories\Shared\PrizePool;

use App\Models\Games\Shared\PrizePool\PrizePoolType;
use App\Repositories\BaseRepository;
use App\Contracts\Games\Common\PrizePool\PrizePoolTypeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
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
        return $this->getByStatus(true);
    }

    /**
     * {@inheritDoc}
     */
    public function getInactiveTypes(): Collection
    {
        return $this->getByStatus(false);
    }

    /**
     * {@inheritDoc}
     */
    public function getByDescription(string $description): ?PrizePoolType
    {
        return $this->model->where('description', $description)->first();
    }

    /**
     * {@inheritDoc}
     */
    public function searchByDescription(string $searchTerm): Collection
    {
        return $this->model->where('description', 'LIKE', "%{$searchTerm}%")->get();
    }

    /**
     * {@inheritDoc}
     */
    public function existsByDescription(string $description, ?string $excludeId = null): bool
    {
        $query = $this->model->where('description', $description);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function createType(string $description, bool $isActive = true): PrizePoolType
    {
        return $this->model->create([
            'description' => $this->normalizeDescription($description),
            'status' => $isActive
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function updateDescription(string $typeId, string $description): bool
    {
        return $this->model->where('id', $typeId)
            ->update(['description' => $this->normalizeDescription($description)]) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function updateStatus(string $typeId, bool $isActive): bool
    {
        return $this->model->where('id', $typeId)
            ->update(['status' => $isActive]) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function activateType(string $typeId): bool
    {
        return $this->updateStatus($typeId, true);
    }

    /**
     * {@inheritDoc}
     */
    public function deactivateType(string $typeId): bool
    {
        return $this->updateStatus($typeId, false);
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
    public function bulkUpdateStatus(array $typeIds, bool $isActive): int
    {
        return $this->model->whereIn('id', $typeIds)
            ->update(['status' => $isActive]);
    }

    /**
     * {@inheritDoc}
     */
    public function getTypeStatistics(): array
    {
        $total = $this->model->count();
        $active = $this->model->where('status', true)->count();
        $inactive = $this->model->where('status', false)->count();
        $averageDescriptionLength = $this->model->avg(DB::raw('LENGTH(description)'));
        $longestDescription = $this->model->orderByRaw('LENGTH(description) DESC')->first();
        $shortestDescription = $this->model->orderByRaw('LENGTH(description) ASC')->first();

        return [
            'total_types' => $total,
            'active_types' => $active,
            'inactive_types' => $inactive,
            'active_percentage' => $total > 0 ? round(($active / $total) * 100, 2) : 0,
            'average_description_length' => round($averageDescriptionLength ?? 0, 2),
            'longest_description' => $longestDescription?->description ?? '',
            'shortest_description' => $shortestDescription?->description ?? '',
            'longest_description_length' => $longestDescription ? strlen($longestDescription->description) : 0,
            'shortest_description_length' => $shortestDescription ? strlen($shortestDescription->description) : 0
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
    public function getOrderedByDescription(bool $activeOnly = false): Collection
    {
        $query = $this->model->orderBy('description');

        if ($activeOnly) {
            $query->where('status', true);
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
    public function createBulkTypes(array $descriptions, bool $isActive = true): Collection
    {
        $types = collect();

        foreach ($descriptions as $description) {
            $normalizedDescription = $this->normalizeDescription($description);

            // Verificar si ya existe antes de crear
            if (!$this->existsByDescription($normalizedDescription)) {
                $type = $this->createType($normalizedDescription, $isActive);
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
        $query = $this->model->orderBy('description');

        if ($activeOnly) {
            $query->where('status', true);
        }

        return $query->pluck('description', 'id')->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function isValidDescription(string $description): bool
    {
        $normalized = $this->normalizeDescription($description);

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
    public function normalizeDescription(string $description): string
    {
        // Limpiar espacios extra
        $normalized = trim($description);

        // Convertir a título (primera letra de cada palabra en mayúscula)
        $normalized = Str::title($normalized);

        // Reemplazar múltiples espacios con uno solo
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return $normalized;
    }

    /**
     * {@inheritDoc}
     */
    public function getDescriptionSuggestions(string $partialText, int $limit = 5): Collection
    {
        return $this->model->where('description', 'LIKE', "%{$partialText}%")
            ->where('status', true)
            ->orderBy('description')
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
        return $this->bulkUpdateStatus($typeIds, true);
    }
}
