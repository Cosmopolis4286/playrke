<?php

namespace App\Contracts\Games\Common\Room;

use App\Contracts\BaseRepositoryInterface;
use App\Models\Games\Common\Room\RoomType;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de tipos de sala (RoomType).
 *
 * Define los métodos específicos para gestionar los tipos de sala,
 * incluyendo operaciones de consulta, creación, actualización y activación.
 */
interface RoomTypeRepositoryInterface extends BaseRepositoryInterface
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
     * Buscar tipos por etiqueta exacta.
     *
     * @param string $label Etiqueta exacta
     * @return RoomType|null El tipo encontrado o null
     */
    public function getByLabel(string $label): ?RoomType;

    /**
     * Buscar tipos por etiqueta parcial.
     *
     * @param string $searchTerm Término de búsqueda parcial
     * @return Collection Tipos que contienen el término en la etiqueta
     */
    public function searchByLabel(string $searchTerm): Collection;

    /**
     * Verificar si existe un tipo con la etiqueta dada.
     *
     * @param string $label Etiqueta a verificar
     * @param string|null $excludeId ID a excluir de la verificación (para updates)
     * @return bool True si existe, false en caso contrario
     */
    public function existsByLabel(string $label, ?string $excludeId = null): bool;

    /**
     * Crear un nuevo tipo de sala.
     *
     * @param string $code Código del tipo
     * @param string $label Etiqueta del tipo
     * @param string $status Estado inicial (por defecto 'ACTIVE')
     * @return RoomType El tipo creado
     */
    public function createType(string $code, string $label, string $status = 'ACTIVE'): RoomType;

    /**
     * Actualizar la etiqueta de un tipo.
     *
     * @param string $typeId ID del tipo
     * @param string $label Nueva etiqueta
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
     * Activar un tipo de sala.
     *
     * @param string $typeId ID del tipo
     * @return bool True si se activó correctamente
     */
    public function activateType(string $typeId): bool;

    /**
     * Desactivar un tipo de sala.
     *
     * @param string $typeId ID del tipo
     * @return bool True si se desactivó correctamente
     */
    public function deactivateType(string $typeId): bool;

    /**
     * Eliminar un tipo de sala.
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
     * Obtener estadísticas de tipos de sala.
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
     * Obtener tipos ordenados alfabéticamente por etiqueta.
     *
     * @param bool $activeOnly Solo tipos activos (por defecto false)
     * @return Collection Tipos ordenados por etiqueta
     */
    public function getOrderedByLabel(bool $activeOnly = false): Collection;

    /**
     * Crear tipos por lotes.
     *
     * @param array $labels Array de etiquetas
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
     * Validar etiqueta de tipo.
     *
     * @param string $label Etiqueta a validar
     * @return bool True si es válida
     */
    public function isValidLabel(string $label): bool;

    /**
     * Normalizar etiqueta de tipo.
     *
     * @param string $label Etiqueta a normalizar
     * @return string Etiqueta normalizada
     */
    public function normalizeLabel(string $label): string;

    /**
     * Obtener sugerencias de etiqueta basadas en texto parcial.
     *
     * @param string $partialText Texto parcial
     * @param int $limit Límite de sugerencias (por defecto 5)
     * @return Collection Sugerencias de tipos existentes
     */
    public function getLabelSuggestions(string $partialText, int $limit = 5): Collection;
}
