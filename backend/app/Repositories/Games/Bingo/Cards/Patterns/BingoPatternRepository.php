<?php

namespace App\Repositories\Games\Bingo\Cards\Patterns;

use App\Models\Bingo\Cards\Patterns\BingoPattern;
use App\Repositories\BaseRepository;
use App\Repositories\Games\Bingo\Cards\Patterns\Contracts\BingoPatternRepositoryContract;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repositorio para gestionar patrones de bingo
 *
 * Implementa todas las operaciones de consulta y manipulación de datos
 * para los patrones de cartones de bingo utilizados en el juego.
 *
 * @package App\Repositories\Games\Bingo\Cards\Patterns
 * @author Sistema PlayRKE
 * @version 1.0
 */
class BingoPatternRepository extends BaseRepository implements BingoPatternRepositoryContract
{
    /**
     * Estados de patrones
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    /**
     * Tipos de patrones
     */
    public const TYPE_UNIQUE = 'UNIQUE';
    public const TYPE_MULTIPLE = 'MULTIPLE';

    /**
     * Constructor del repositorio
     *
     * @param BingoPattern $model
     */
    public function __construct(BingoPattern $model)
    {
        parent::__construct($model);
    }

    /**
     * Obtener patrones por usuario
     *
     * @param string $userId
     * @param string|null $status
     * @return Collection<int, BingoPattern>
     */
    public function getByUser(string $userId, ?string $status = null): Collection
    {
        $query = $this->model->where('user', $userId);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get();
    }

    /**
     * Obtener patrón por tipo de patrón
     *
     * @param string $typePattern
     * @return Collection<int, BingoPattern>
     */
    public function getByTypePattern(string $typePattern): Collection
    {
        return $this->model->where('type_pattern', $typePattern)->get();
    }

    /**
     * Obtener patrón por nombre
     *
     * @param string $name
     * @return BingoPattern|null
     */
    public function findByName(string $name): ?BingoPattern
    {
        return $this->model->where('name', $name)->first();
    }

    /**
     * Obtener patrones activos
     *
     * @return Collection<int, BingoPattern>
     */
    public function getActivePatterns(): Collection
    {
        return $this->getByStatus(self::STATUS_ACTIVE);
    }

    /**
     * Obtener patrones inactivos
     *
     * @return Collection<int, BingoPattern>
     */
    public function getInactivePatterns(): Collection
    {
        return $this->getByStatus(self::STATUS_INACTIVE);
    }

    /**
     * Verificar si existe un patrón por nombre y usuario
     *
     * @param string $name
     * @param string $userId
     * @return bool
     */
    public function existsByNameAndUser(string $name, string $userId): bool
    {
        return $this->model->where('name', $name)
            ->where('user', $userId)
            ->exists();
    }

    /**
     * Obtener columnas y cajas por ID de patrón
     *
     * @param string $patternId
     * @return array{type: string, columns: array}
     */
    public function getColumnsBoxesById(string $patternId): array
    {
        $bingoPattern = $this->model->select('type_pattern', 'boxes')->find($patternId);

        if (empty($bingoPattern?->boxes)) {
            return ['type' => '', 'columns' => []];
        }

        $typePattern = $bingoPattern->type_pattern;

        if ($typePattern === self::TYPE_UNIQUE) {
            $columns = explode(';', $bingoPattern->boxes);
            return [
                'type' => $typePattern,
                'columns' => array_values(array_unique(array_filter($columns)))
            ];
        }

        $columns = [];

        foreach (explode(';', $bingoPattern->boxes) as $patternId) {
            $boxes = $this->model->where('id', $patternId)->value('boxes');

            if ($boxes) {
                $boxArray = array_values(array_unique(array_filter(explode(';', $boxes))));
                $columns[$patternId] = $boxArray;
            }
        }

        return ['type' => $typePattern, 'columns' => $columns];
    }

    /**
     * Obtener tipos únicos de patrones
     *
     * @return Collection
     */
    public function getUniqueTypePatterns(): Collection
    {
        return $this->model->distinct()->pluck('type_pattern');
    }

    /**
     * Contar patrones por usuario
     *
     * @param string $userId
     * @return int
     */
    public function countByUser(string $userId): int
    {
        return $this->model->where('user', $userId)->count();
    }

    /**
     * Contar patrones por tipo
     *
     * @param string $typePattern
     * @return int
     */
    public function countByTypePattern(string $typePattern): int
    {
        return $this->model->where('type_pattern', $typePattern)->count();
    }

    /**
     * Contar patrones por estado
     *
     * @param string $status
     * @return int
     */
    public function countByStatus(string $status): int
    {
        return $this->model->where('status', $status)->count();
    }

    /**
     * Obtener estadísticas de patrones
     *
     * @return array{
     *     total_patterns: int,
     *     active_patterns: int,
     *     inactive_patterns: int,
     *     unique_types: int,
     *     patterns_per_user: float,
     *     by_type: array
     * }
     */
    public function getPatternStats(): array
    {
        $totalPatterns = $this->model->count();
        $activePatterns = $this->countByStatus(self::STATUS_ACTIVE);
        $inactivePatterns = $this->countByStatus(self::STATUS_INACTIVE);
        $uniqueTypes = $this->getUniqueTypePatterns()->count();
        $uniqueUsers = $this->model->distinct('user')->count();
        $patternsPerUser = $uniqueUsers > 0 ? $totalPatterns / $uniqueUsers : 0;

        $byType = $this->model->selectRaw('type_pattern, COUNT(*) as count')
            ->groupBy('type_pattern')
            ->pluck('count', 'type_pattern')
            ->toArray();

        return [
            'total_patterns' => $totalPatterns,
            'active_patterns' => $activePatterns,
            'inactive_patterns' => $inactivePatterns,
            'unique_types' => $uniqueTypes,
            'patterns_per_user' => round($patternsPerUser, 2),
            'by_type' => $byType
        ];
    }

    /**
     * Buscar patrones por término de búsqueda
     *
     * @param string $searchTerm
     * @return Collection<int, BingoPattern>
     */
    public function searchPatterns(string $searchTerm): Collection
    {
        return $this->model->where(function ($query) use ($searchTerm) {
            $query->where('name', 'like', "%{$searchTerm}%")
                ->orWhere('type_pattern', 'like', "%{$searchTerm}%")
                ->orWhere('user', 'like', "%{$searchTerm}%")
                ->orWhere('boxes', 'like', "%{$searchTerm}%");
        })->get();
    }

    /**
     * Obtener patrones agrupados por tipo
     *
     * @return Collection
     */
    public function getGroupedByType(): Collection
    {
        return $this->model->selectRaw('type_pattern, COUNT(*) as count')
            ->groupBy('type_pattern')
            ->orderBy('type_pattern')
            ->get();
    }

    /**
     * Obtener patrones agrupados por usuario
     *
     * @return Collection
     */
    public function getGroupedByUser(): Collection
    {
        return $this->model->selectRaw('user, COUNT(*) as count')
            ->groupBy('user')
            ->orderByDesc('count')
            ->get();
    }

    /**
     * Crear nuevo patrón
     *
     * @param array $patternData
     * @return BingoPattern
     */
    public function createPattern(array $patternData): BingoPattern
    {
        return $this->create($patternData);
    }

    /**
     * Actualizar patrón
     *
     * @param string $patternId
     * @param array $updateData
     * @return bool
     */
    public function updatePattern(string $patternId, array $updateData): bool
    {
        return $this->model->where('id', $patternId)
            ->update($updateData) > 0;
    }

    /**
     * Actualizar estado de patrón
     *
     * @param string $patternId
     * @param string $newStatus
     * @return bool
     */
    public function updatePatternStatus(string $patternId, string $newStatus): bool
    {
        return $this->updatePattern($patternId, ['status' => $newStatus]);
    }

    /**
     * Activar patrón
     *
     * @param string $patternId
     * @return bool
     */
    public function activatePattern(string $patternId): bool
    {
        return $this->updatePatternStatus($patternId, self::STATUS_ACTIVE);
    }

    /**
     * Desactivar patrón
     *
     * @param string $patternId
     * @return bool
     */
    public function deactivatePattern(string $patternId): bool
    {
        return $this->updatePatternStatus($patternId, self::STATUS_INACTIVE);
    }

    /**
     * Duplicar patrón
     *
     * @param string $patternId
     * @param string $newUserId
     * @param string|null $newName
     * @return BingoPattern
     */
    public function duplicatePattern(string $patternId, string $newUserId, ?string $newName = null): BingoPattern
    {
        $originalPattern = $this->find($patternId);

        if (!$originalPattern) {
            throw new \Exception("Pattern with ID {$patternId} not found");
        }

        $duplicateData = [
            'type_pattern' => $originalPattern->type_pattern,
            'name' => $newName ?: $originalPattern->name . '_copy',
            'user' => $newUserId,
            'boxes' => $originalPattern->boxes,
            'status' => self::STATUS_ACTIVE
        ];

        return $this->createPattern($duplicateData);
    }

    /**
     * Validar formato de cajas
     *
     * @param string $boxes
     * @return bool
     */
    public function validateBoxesFormat(string $boxes): bool
    {
        if (empty($boxes)) {
            return false;
        }

        // Validar que las cajas estén separadas por ';'
        $boxArray = explode(';', $boxes);

        foreach ($boxArray as $box) {
            $box = trim($box);
            // Validar que cada caja sea un número válido
            if (!is_numeric($box) || $box < 1 || $box > 75) {
                return false;
            }
        }

        return true;
    }

    /**
     * Obtener patrones por rango de cajas
     *
     * @param int $minBoxes
     * @param int $maxBoxes
     * @return Collection<int, BingoPattern>
     */
    public function getByBoxesRange(int $minBoxes, int $maxBoxes): Collection
    {
        return $this->model->whereRaw('LENGTH(boxes) - LENGTH(REPLACE(boxes, ";", "")) + 1 BETWEEN ? AND ?', [$minBoxes, $maxBoxes])
            ->get();
    }

    /**
     * Obtener patrones más utilizados
     *
     * @param int $limit
     * @return Collection<int, BingoPattern>
     */
    public function getMostUsedPatterns(int $limit = 10): Collection
    {
        // Esta implementación asume que hay un campo usage_count
        // o se puede implementar con joins a otras tablas
        return $this->model->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener patrones por usuario y tipo
     *
     * @param string $userId
     * @param string $typePattern
     * @return Collection<int, BingoPattern>
     */
    public function getByUserAndType(string $userId, string $typePattern): Collection
    {
        return $this->model->where('user', $userId)
            ->where('type_pattern', $typePattern)
            ->get();
    }

    /**
     * Validar integridad de patrón
     *
     * @param string $patternId
     * @return bool
     */
    public function validatePatternIntegrity(string $patternId): bool
    {
        $pattern = $this->find($patternId);

        if (!$pattern) {
            return false;
        }

        // Validar campos requeridos
        if (empty($pattern->name) || empty($pattern->type_pattern) || empty($pattern->boxes)) {
            return false;
        }

        // Validar formato de cajas
        return $this->validateBoxesFormat($pattern->boxes);
    }

    /**
     * Limpiar patrones huérfanos (sin usuario válido)
     *
     * @return int Número de registros eliminados
     */
    public function cleanOrphanedPatterns(): int
    {
        return $this->model->whereNull('user')
            ->orWhere('user', '')
            ->delete();
    }

    /**
     * Exportar patrones
     *
     * @param string $format 'json'|'csv'|'array'
     * @return mixed
     */
    public function exportPatterns(string $format = 'array')
    {
        $patterns = $this->getAll();

        switch ($format) {
            case 'json':
                return $patterns->toJson();
            case 'csv':
                // Implementar exportación CSV si es necesario
                return $patterns->toArray();
            case 'array':
            default:
                return $patterns->toArray();
        }
    }

    /**
     * Obtener historial de patrones por usuario
     *
     * @param string $userId
     * @param int $limit
     * @return Collection<int, BingoPattern>
     */
    public function getUserPatternHistory(string $userId, int $limit = 20): Collection
    {
        return $this->model->where('user', $userId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Verificar si un patrón puede ser eliminado
     *
     * @param string $patternId
     * @return bool
     */
    public function canBeDeleted(string $patternId): bool
    {
        // Implementar lógica para verificar si el patrón está siendo usado
        // en algún juego activo o tiene dependencias
        return true; // Por ahora siempre permite eliminación
    }

    /**
     * Obtener patrones compatibles con un tipo específico
     *
     * @param string $typePattern
     * @return Collection<int, BingoPattern>
     */
    public function getCompatiblePatterns(string $typePattern): Collection
    {
        return $this->getByTypePattern($typePattern)
            ->where('status', self::STATUS_ACTIVE);
    }
}
