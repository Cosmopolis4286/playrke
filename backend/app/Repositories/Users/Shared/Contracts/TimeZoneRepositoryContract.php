<?php

namespace App\Repositories\Users\Shared\Contracts;

use App\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de zonas horarias.
 *
 * Define las operaciones específicas para gestionar las zonas horarias
 * del sistema, incluyendo consultas por país, estado, UTC y búsquedas
 * por nombre y configuraciones de zona horaria.
 */
interface TimeZoneRepositoryContract extends BaseRepositoryInterface
{
    /**
     * Obtiene una zona horaria por ID con campos específicos.
     *
     * @param string $id ID de la zona horaria
     * @param array $fields Campos a obtener
     * @return mixed Zona horaria encontrada o null
     */
    public function getByIdWithFields(string $id, array $fields = ['*']): mixed;

    /**
     * Obtiene todas las zonas horarias por país.
     *
     * @param string $countryId ID del país
     * @return Collection<TimeZone> Colección de zonas horarias del país
     */
    public function getByCountry(string $countryId): Collection;

    /**
     * Obtiene las zonas horarias activas.
     *
     * @return Collection<TimeZone> Colección de zonas horarias activas
     */
    public function getActive(): Collection;

    /**
     * Obtiene las zonas horarias inactivas.
     *
     * @return Collection<TimeZone> Colección de zonas horarias inactivas
     */
    public function getInactive(): Collection;

    /**
     * Obtiene zonas horarias por nombre (búsqueda parcial).
     *
     * @param string $name Nombre a buscar
     * @return Collection<TimeZone> Zonas horarias que coinciden
     */
    public function getByName(string $name): Collection;

    /**
     * Obtiene zona horaria por nombre exacto.
     *
     * @param string $name Nombre exacto
     * @return mixed Zona horaria encontrada o null
     */
    public function getByExactName(string $name): mixed;

    /**
     * Obtiene zonas horarias por offset UTC.
     *
     * @param string $utcOffset Offset UTC (ej: "+05:00", "-03:00")
     * @return Collection<TimeZone> Zonas horarias con el offset
     */
    public function getByUtcOffset(string $utcOffset): Collection;

    /**
     * Obtiene zonas horarias en un rango de offset UTC.
     *
     * @param string $minOffset Offset mínimo
     * @param string $maxOffset Offset máximo
     * @return Collection<TimeZone> Zonas horarias en el rango
     */
    public function getByUtcRange(string $minOffset, string $maxOffset): Collection;

    /**
     * Crea una nueva zona horaria.
     *
     * @param array $data Datos de la zona horaria
     * @return mixed Zona horaria creada
     */
    public function createTimeZone(array $data): mixed;

    /**
     * Actualiza una zona horaria.
     *
     * @param string $timeZoneId ID de la zona horaria
     * @param array $data Datos a actualizar
     * @return bool True si se actualizó correctamente
     */
    public function updateTimeZone(string $timeZoneId, array $data): bool;

    /**
     * Actualiza el estado de una zona horaria.
     *
     * @param string $timeZoneId ID de la zona horaria
     * @param bool $status Nuevo estado
     * @return bool True si se actualizó correctamente
     */
    public function updateStatus(string $timeZoneId, bool $status): bool;

    /**
     * Activa una zona horaria específica.
     *
     * @param string $timeZoneId ID de la zona horaria
     * @return bool True si se activó correctamente
     */
    public function activateTimeZone(string $timeZoneId): bool;

    /**
     * Desactiva una zona horaria específica.
     *
     * @param string $timeZoneId ID de la zona horaria
     * @return bool True si se desactivó correctamente
     */
    public function deactivateTimeZone(string $timeZoneId): bool;

    /**
     * Elimina una zona horaria específica.
     *
     * @param string $timeZoneId ID de la zona horaria
     * @return bool True si se eliminó correctamente
     */
    public function deleteTimeZone(string $timeZoneId): bool;

    /**
     * Obtiene estadísticas de zonas horarias.
     *
     * @return array Estadísticas de zonas horarias
     */
    public function getTimeZoneStats(): array;

    /**
     * Obtiene estadísticas de zonas horarias por país.
     *
     * @param string $countryId ID del país
     * @return array Estadísticas del país específico
     */
    public function getCountryTimeZoneStats(string $countryId): array;

    /**
     * Obtiene la distribución de zonas horarias por país.
     *
     * @return array Distribución por país
     */
    public function getDistributionByCountry(): array;

    /**
     * Obtiene la distribución de zonas horarias por offset UTC.
     *
     * @return array Distribución por offset UTC
     */
    public function getDistributionByUtc(): array;

    /**
     * Valida los datos de una zona horaria antes de crear/actualizar.
     *
     * @param array $data Datos a validar
     * @param string|null $excludeId ID a excluir de validaciones de unicidad
     * @return array Array de errores de validación
     */
    public function validateTimeZoneData(array $data, ?string $excludeId = null): array;

    /**
     * Obtiene zonas horarias ordenadas por nombre.
     *
     * @param string $direction Dirección del ordenamiento ('asc' o 'desc')
     * @param bool $activeOnly Solo zonas horarias activas
     * @return Collection<TimeZone> Zonas horarias ordenadas
     */
    public function getOrderedByName(string $direction = 'asc', bool $activeOnly = false): Collection;

    /**
     * Obtiene zonas horarias ordenadas por offset UTC.
     *
     * @param string $direction Dirección del ordenamiento ('asc' o 'desc')
     * @param bool $activeOnly Solo zonas horarias activas
     * @return Collection<TimeZone> Zonas horarias ordenadas
     */
    public function getOrderedByUtc(string $direction = 'asc', bool $activeOnly = false): Collection;

    /**
     * Busca zonas horarias por múltiples criterios.
     *
     * @param array $criteria Criterios de búsqueda
     * @return Collection<TimeZone> Zonas horarias que coinciden
     */
    public function searchByCriteria(array $criteria): Collection;

    /**
     * Obtiene zonas horarias para uso en select/dropdown.
     *
     * @param bool $activeOnly Solo zonas horarias activas
     * @param string|null $countryFilter Filtrar por país específico
     * @return array Array para select [id => nombre]
     */
    public function getForSelect(bool $activeOnly = true, ?string $countryFilter = null): array;

    /**
     * Verifica si una zona horaria está en uso.
     *
     * @param string $timeZoneId ID de la zona horaria
     * @return bool True si está en uso
     */
    public function isTimeZoneInUse(string $timeZoneId): bool;

    /**
     * Obtiene la zona horaria más común.
     *
     * @return mixed Zona horaria más utilizada
     */
    public function getMostUsedTimeZone(): mixed;

    /**
     * Obtiene zonas horarias sin uso.
     *
     * @return Collection<TimeZone> Zonas horarias no utilizadas
     */
    public function getUnusedTimeZones(): Collection;

    /**
     * Crea zonas horarias en lote.
     *
     * @param array $timeZonesData Array de datos de zonas horarias
     * @return Collection<TimeZone> Zonas horarias creadas
     */
    public function createBulkTimeZones(array $timeZonesData): Collection;
}
