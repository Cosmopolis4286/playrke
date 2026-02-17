<?php

namespace App\Contracts\Games\Common\PrizePool;

use App\Contracts\BaseRepositoryInterface;
use App\Models\Games\Common\PrizePool\PrizePoolType;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de tipos de pool de premios.
 *
 * Define los métodos específicos para gestionar los tipos de pool de premios,
 * incluyendo operaciones de consulta, creación, actualización y activación.
 */
interface PrizePoolTypeRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Obtener todos los tipos activos.
     *
     * @return Collection Colección de tipos activos
     */
    public function getActiveTypes(): Collection;

    /**
     * Obtener todos los tipos inactivos.
     *
     * @return Collection Colección de tipos inactivos
     */
    public function getInactiveTypes(): Collection;

    /**
     * Buscar tipos por descripción exacta.
     *
     * @param string $label Descripción exacta
     * @return PrizePoolType|null El tipo encontrado o null
     */
    public function getByLabel(string $label): ?PrizePoolType;

    /**
     * Buscar tipos por descripción parcial.
     *
     * @param string $searchTerm Término de búsqueda parcial
     * @return Collection Tipos que contienen el término en la descripción
     */
    public function searchByLabel(string $searchTerm): Collection;

    /**
     * Verificar si existe un tipo con la descripción dada.
     *
     * @param string $label Descripción a verificar
     * @param string|null $excludeId ID a excluir de la verificación (para updates)
     * @return bool True si existe, false en caso contrario
     */
    public function existsByLabel(string $label, ?string $excludeId = null): bool;

    /**
     * Crear un nuevo tipo de pool de premios.
     *
     * @param string $label Descripción del tipo
     * @param string $status Estado inicial (por defecto 'ACTIVE')
     * @return PrizePoolType El tipo creado
     */
    public function createType(string $label, string $status = 'ACTIVE'): PrizePoolType;

    /**
     * Actualizar la descripción de un tipo.
     *
     * @param string $typeId ID del tipo
     * @param string $label Nueva descripción
     * @return bool True si se actualizó correctamente
     */
    public function updateLabel(string $typeId, string $label): bool;

    /**
     * Actualizar el estado de un tipo.
     *
     * @param string $typeId ID del tipo
     * @param string $status Nuevo estado ('ACTIVE' o 'INACTIVE')
     * @return bool True si se actualizó correctamente
     */
    public function updateStatus(string $typeId, string $status): bool;

    /**
     * Activar un tipo de pool de premios.
     *
     * @param string $typeId ID del tipo
     * @return bool True si se activó correctamente
     */
    public function activateType(string $typeId): bool;

    /**
     * Desactivar un tipo de pool de premios.
     *
     * @param string $typeId ID del tipo
     * @return bool True si se desactivó correctamente
     */
    public function deactivateType(string $typeId): bool;

    /**
     * Eliminar un tipo de pool de premios.
     *
     * @param string $typeId ID del tipo
     * @return bool True si se eliminó correctamente
     */
    public function deleteType(string $typeId): bool;

    /**
     * Actualización masiva de estado para múltiples tipos.
     *
     * @param array $typeIds IDs de los tipos
     * @param string $status Nuevo estado ('ACTIVE' o 'INACTIVE')
     * @return int Número de registros actualizados
     */
    public function bulkUpdateStatus(array $typeIds, string $status): int;

    /**
     * Obtener estadísticas de tipos de pool de premios.
     *
     * @return array<string, mixed> Array con estadísticas
     */
    public function getTypeStatistics(): array;

    /**
     * Obtener tipos creados recientemente.
     *
     * @param int $days Número de días hacia atrás (por defecto 30)
     * @return Collection Tipos creados en el período especificado
     */
    public function getRecentTypes(int $days = 30): Collection;

    /**
     * Obtener tipos ordenados alfabéticamente por descripción.
     *
     * @param bool $activeOnly Solo tipos activos (por defecto false)
     * @return Collection Tipos ordenados por descripción
     */
    public function getOrderedByLabel(bool $activeOnly = false): Collection;

    /**
     * Obtener tipos más utilizados en juegos.
     *
     * @param int $limit Límite de resultados (por defecto 10)
     * @return Collection Tipos más populares
     */
    public function getMostUsedTypes(int $limit = 10): Collection;

    /**
     * Obtener tipos sin uso en juegos.
     *
     * @return Collection Tipos que no están siendo utilizados
     */
    public function getUnusedTypes(): Collection;

    /**
     * Verificar si un tipo está siendo utilizado en algún juego.
     *
     * @param string $typeId ID del tipo
     * @return bool True si está en uso, false en caso contrario
     */
    public function isTypeInUse(string $typeId): bool;

    /**
     * Obtener conteo de juegos por tipo.
     *
     * @param string $typeId ID del tipo
     * @return int Número de juegos que usan este tipo
     */
    public function getGameCountByType(string $typeId): int;

    /**
     * Obtener tipos con sus estadísticas de uso.
     *
     * @param bool $activeOnly Solo tipos activos (por defecto true)
     * @return Collection Tipos con estadísticas de uso
     */
    public function getTypesWithUsageStats(bool $activeOnly = true): Collection;

    /**
     * Crear tipos por lotes.
     *
     * @param array $labels Array de descripciones
     * @param string $status Estado inicial para todos (por defecto 'ACTIVE')
     * @return Collection Tipos creados
     */
    public function createBulkTypes(array $labels, string $status = 'ACTIVE'): Collection;

    /**
     * Obtener tipos para uso en selects/formularios.
     *
     * @param bool $activeOnly Solo tipos activos (por defecto true)
     * @return array<string, string> Array asociativo [id => label]
     */
    public function getForSelect(bool $activeOnly = true): array;

    /**
     * Validar descripción de tipo.
     *
     * @param string $label Descripción a validar
     * @return bool True si es válida
     */
    public function isValidLabel(string $label): bool;

    /**
     * Normalizar descripción de tipo.
     *
     * @param string $label Descripción a normalizar
     * @return string Descripción normalizada
     */
    public function normalizeLabel(string $label): string;

    /**
     * Obtener sugerencias de descripción basadas en texto parcial.
     *
     * @param string $partialText Texto parcial
     * @param int $limit Límite de sugerencias (por defecto 5)
     * @return Collection Sugerencias de tipos existentes
     */
    public function getLabelSuggestions(string $partialText, int $limit = 5): Collection;

    /**
     * Archivar tipos antiguos (marcar como inactivos).
     *
     * @param int $daysOld Días de antigüedad sin uso
     * @return int Número de tipos archivados
     */
    public function archiveOldUnusedTypes(int $daysOld): int;

    /**
     * Restaurar tipos archivados (marcar como activos).
     *
     * @param array $typeIds IDs de los tipos a restaurar
     * @return int Número de tipos restaurados
     */
    public function restoreArchivedTypes(array $typeIds): int;
}
