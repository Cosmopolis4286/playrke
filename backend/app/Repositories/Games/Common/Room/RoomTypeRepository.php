<?php

namespace App\Repositories\Games\Common\Room;

use App\Models\Games\Common\Room\RoomType;
use App\Repositories\BaseRepository;
use App\Contracts\Games\Common\Room\RoomTypeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Repositorio para la gestión de tipos de pool de premios.
 *
 * Implementa las operaciones específicas para el modelo RoomType,
 * incluyendo gestión de estados, búsquedas y estadísticas de uso.
 */
class RoomTypeRepository extends BaseRepository implements RoomTypeRepositoryInterface
{
    /**
     * Constructor del repositorio.
     *
     * @param RoomType $model Instancia del modelo RoomType
     */
    public function __construct(RoomType $model)
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
    public function getByDescription(string $description): ?RoomType
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
    public function createType(string $description, string $status = 'ACTIVE'): RoomType
    {
        return $this->model->create([
            'description' => $this->normalizeDescription($description),
            'status' => $status
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
    public function createBulkTypes(array $descriptions, string $status = 'ACTIVE'): Collection
    {
        $types = collect();

        foreach ($descriptions as $description) {
            $normalizedDescription = $this->normalizeDescription($description);

            // Verificar si ya existe antes de crear
            if (!$this->existsByDescription($normalizedDescription)) {
                $type = $this->createType($normalizedDescription, $status);
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
            $query->where('status', 'ACTIVE');
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
            ->where('status', 'ACTIVE')
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
        return $this->bulkUpdateStatus($typeIds, 'ACTIVE');
    }
}
