<?php

namespace App\Repositories\Bingo\Cards\Patterns\Contracts;

use App\Contracts\BaseRepositoryInterface;
use App\Models\Games\Bingo\Cards\Patterns\BingoPattern;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de patrones de bingo
 *
 * Define los métodos que debe implementar el repositorio para gestionar
 * los patrones de cartones de bingo utilizados en el juego.
 *
 * @package App\Repositories\Bingo\Cards\Patterns\Contracts
 * @author Sistema PlayRKE
 * @version 1.0
 */
interface BingoPatternRepositoryContract extends BaseRepositoryInterface
{
    /**
     * Obtener patrones por usuario
     *
     * @param string $userId
     * @param string|null $status
     * @return Collection<int, BingoPattern>
     */
    public function getByUser(string $userId, ?string $status = null): Collection;

    /**
     * Obtener patrón por tipo de patrón
     *
     * @param string $typePattern
     * @return Collection<int, BingoPattern>
     */
    public function getByTypePattern(string $typePattern): Collection;

    /**
     * Obtener patrón por nombre
     *
     * @param string $name
     * @return BingoPattern|null
     */
    public function findByName(string $name): ?BingoPattern;

    /**
     * Obtener patrones activos
     *
     * @return Collection<int, BingoPattern>
     */
    public function getActivePatterns(): Collection;

    /**
     * Obtener patrones inactivos
     *
     * @return Collection<int, BingoPattern>
     */
    public function getInactivePatterns(): Collection;

    /**
     * Verificar si existe un patrón por nombre y usuario
     *
     * @param string $name
     * @param string $userId
     * @return bool
     */
    public function existsByNameAndUser(string $name, string $userId): bool;

    /**
     * Obtener columnas y cajas por ID de patrón
     *
     * @param string $patternId
     * @return array{type: string, columns: array}
     */
    public function getColumnsBoxesById(string $patternId): array;

    /**
     * Obtener tipos únicos de patrones
     *
     * @return Collection
     */
    public function getUniqueTypePatterns(): Collection;

    /**
     * Contar patrones por usuario
     *
     * @param string $userId
     * @return int
     */
    public function countByUser(string $userId): int;

    /**
     * Contar patrones por tipo
     *
     * @param string $typePattern
     * @return int
     */
    public function countByTypePattern(string $typePattern): int;

    /**
     * Contar patrones por estado
     *
     * @param string $status
     * @return int
     */
    public function countByStatus(string $status): int;

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
    public function getPatternStats(): array;

    /**
     * Buscar patrones por término de búsqueda
     *
     * @param string $searchTerm
     * @return Collection<int, BingoPattern>
     */
    public function searchPatterns(string $searchTerm): Collection;

    /**
     * Obtener patrones agrupados por tipo
     *
     * @return Collection
     */
    public function getGroupedByType(): Collection;

    /**
     * Obtener patrones agrupados por usuario
     *
     * @return Collection
     */
    public function getGroupedByUser(): Collection;

    /**
     * Crear nuevo patrón
     *
     * @param array $patternData
     * @return BingoPattern
     */
    public function createPattern(array $patternData): BingoPattern;

    /**
     * Actualizar patrón
     *
     * @param string $patternId
     * @param array $updateData
     * @return bool
     */
    public function updatePattern(string $patternId, array $updateData): bool;

    /**
     * Actualizar estado de patrón
     *
     * @param string $patternId
     * @param string $newStatus
     * @return bool
     */
    public function updatePatternStatus(string $patternId, string $newStatus): bool;

    /**
     * Activar patrón
     *
     * @param string $patternId
     * @return bool
     */
    public function activatePattern(string $patternId): bool;

    /**
     * Desactivar patrón
     *
     * @param string $patternId
     * @return bool
     */
    public function deactivatePattern(string $patternId): bool;

    /**
     * Duplicar patrón
     *
     * @param string $patternId
     * @param string $newUserId
     * @param string|null $newName
     * @return BingoPattern
     */
    public function duplicatePattern(string $patternId, string $newUserId, ?string $newName = null): BingoPattern;

    /**
     * Validar formato de cajas
     *
     * @param string $boxes
     * @return bool
     */
    public function validateBoxesFormat(string $boxes): bool;

    /**
     * Obtener patrones por rango de cajas
     *
     * @param int $minBoxes
     * @param int $maxBoxes
     * @return Collection<int, BingoPattern>
     */
    public function getByBoxesRange(int $minBoxes, int $maxBoxes): Collection;

    /**
     * Obtener patrones más utilizados
     *
     * @param int $limit
     * @return Collection<int, BingoPattern>
     */
    public function getMostUsedPatterns(int $limit = 10): Collection;

    /**
     * Obtener patrones por usuario y tipo
     *
     * @param string $userId
     * @param string $typePattern
     * @return Collection<int, BingoPattern>
     */
    public function getByUserAndType(string $userId, string $typePattern): Collection;

    /**
     * Validar integridad de patrón
     *
     * @param string $patternId
     * @return bool
     */
    public function validatePatternIntegrity(string $patternId): bool;

    /**
     * Limpiar patrones huérfanos (sin usuario válido)
     *
     * @return int Número de registros eliminados
     */
    public function cleanOrphanedPatterns(): int;

    /**
     * Exportar patrones
     *
     * @param string $format 'json'|'csv'|'array'
     * @return mixed
     */
    public function exportPatterns(string $format = 'array');

    /**
     * Obtener historial de patrones por usuario
     *
     * @param string $userId
     * @param int $limit
     * @return Collection<int, BingoPattern>
     */
    public function getUserPatternHistory(string $userId, int $limit = 20): Collection;

    /**
     * Verificar si un patrón puede ser eliminado
     *
     * @param string $patternId
     * @return bool
     */
    public function canBeDeleted(string $patternId): bool;

    /**
     * Obtener patrones compatibles con un tipo específico
     *
     * @param string $typePattern
     * @return Collection<int, BingoPattern>
     */
    public function getCompatiblePatterns(string $typePattern): Collection;
}
