<?php

namespace App\Contracts;

use App\Models\AppParameter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de parámetros de configuración de la app
 *
 * Define los métodos disponibles para gestionar parámetros de configuración,
 * incluyendo operaciones CRUD y consultas específicas del dominio.
 *
 * @version 1.2
 */
interface AppParameterRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Actualiza múltiples parámetros en lote
     *
     * @param array $settings Array de parámetros [param => [value, status]]
     *
     * @return int Número de parámetros actualizados
     *
     * @example
     * $updated = $repository->bulkUpdateSetting([
     *     'theme' => ['dark', 'ACTIVE'],
     *     'language' => ['es', 'ACTIVE']
     * ]);
     */
    public function bulkUpdateSetting(array $settings) : int;

    /**
     * Cuenta parámetros por estado
     *
     * @return array<string,int> Conteo por estado
     *
     * @example
     * $counts = $repository->countSettingByStatus();
     * // ['ACTIVE' => 25, 'INACTIVE' => 5]
     */
    public function countSettingByStatus() : array;

    /**
     * Elimina un parámetro de configuración
     *
     * @param string $param Nombre del parámetro
     *
     * @return bool True si se eliminó correctamente
     *
     * @example
     * $deleted = $repository->deleteSetting('deprecated_feature');
     */
    public function deleteSetting(string $param) : bool;

    /**
     * Obtiene todos los parámetros activos
     *
     * @return Collection<AppParameter> Parámetros activos
     *
     * @example
     * $activeSetting = $repository->getActiveSetting();
     */
    public function getActiveSetting() : Collection;

    /**
     * Obtiene parámetros de sistema críticos
     *
     * @return Collection<AppParameter> Parámetros críticos del sistema
     *
     * @example
     * $criticalSettings = $repository->getCriticalSystemSetting();
     */
    public function getCriticalSystemSetting() : Collection;

    /**
     * Obtiene estadísticas generales de parámetros
     *
     * @return array<string,int> Estadísticas generales
     *
     * @example
     * $stats = $repository->getGeneralSettingStats();
     */
    public function getGeneralSettingStats() : array;

    /**
     * Obtiene todos los parámetros inactivos
     *
     * @return Collection<AppParameter> Parámetros inactivos
     *
     * @example
     * $inactiveSetting = $repository->getInactiveSetting();
     */
    public function getInactiveSetting() : Collection;

    /**
     * Obtiene todos los parámetros con paginación
     *
     * @param int   $perPage Elementos por página
     * @param array $filters Filtros adicionales
     *
     * @return LengthAwarePaginator Parámetros paginados
     *
     * @example
     * $paginatedSetting = $repository->getPaginatedSetting(20, ['status' => 'ACTIVE']);
     */
    public function getPaginatedSetting(
        int $perPage = 15,
        array $filters = [],
    ) : LengthAwarePaginator;

    /**
     * Obtiene el estado de un parámetro específico
     *
     * @param string $param Nombre del parámetro
     *
     * @return string|null Estado del parámetro o null si no existe
     *
     * @example
     * $status = $repository->getParameterStatus('api_enabled');
     */
    public function getParameterStatus(string $param) : ?string;

    /**
     * Obtiene el valor de un parámetro específico
     *
     * @param string $param Nombre del parámetro
     *
     * @return string|null Valor del parámetro o null si no existe
     *
     * @example
     * $value = $repository->getParameterValue('maintenance_mode');
     */
    public function getParameterValue(string $param) : ?string;

    /**
     * Obtiene parámetros por rango de fechas de creación
     *
     * @param string $startDate Fecha de inicio
     * @param string $endDate   Fecha de fin
     * @param array  $filters   Filtros adicionales
     *
     * @return Collection<AppParameter> Parámetros en el rango
     *
     * @example
     * $settings = $repository->getSettingByDateRange('2024-01-01', '2024-01-31');
     */
    public function getSettingByDateRange(
        string $startDate,
        string $endDate,
        array $filters = [],
    ) : Collection;

    /**
     * Obtiene parámetros por estado
     *
     * @param string $status Estado ('ACTIVE', 'INACTIVE')
     *
     * @return Collection<AppParameter> Parámetros con el estado especificado
     *
     * @example
     * $settings = $repository->getSettingByStatus('ACTIVE');
     */
    public function getSettingByStatus(string $status) : Collection;

    /**
     * Verifica si un parámetro está activo
     *
     * @param string $param Nombre del parámetro
     *
     * @return bool True si el parámetro está activo
     *
     * @example
     * $isActive = $repository->isSettingActive('email_notifications');
     */
    public function isSettingActive(string $param) : bool;

    /**
     * Busca parámetros por nombre parcial
     *
     * @param string $searchTerm Término de búsqueda
     * @param array  $filters    Filtros adicionales
     *
     * @return Collection<AppParameter> Parámetros que coinciden
     *
     * @example
     * $settings = $repository->searchSettingByParam('mail', ['status' => 'ACTIVE']);
     */
    public function searchSettingByParam(string $searchTerm, array $filters = []) : Collection;

    /**
     * Verifica si un parámetro existe
     *
     * @param string $param Nombre del parámetro
     *
     * @return bool True si el parámetro existe
     *
     * @example
     * $exists = $repository->settingExists('app_name');
     */
    public function settingExists(string $param) : bool;

    /**
     * Actualiza o crea un parámetro de configuración
     *
     * @param string $param  Nombre del parámetro
     * @param string $value  Valor del parámetro
     * @param string $status Estado del parámetro
     *
     * @return AppParameter Parámetro actualizado o creado
     *
     * @example
     * $setting = $repository->updateOrCreateSetting('theme', 'dark');
     */
    public function updateOrCreateSetting(
        string $param,
        string $value,
        string $status = AppParameter::STATUS_ACTIVE,
    ) : AppParameter;

    /**
     * Actualiza el estado de un parámetro
     *
     * @param string $param     Nombre del parámetro
     * @param string $newStatus Nuevo estado
     *
     * @return bool True si se actualizó correctamente
     *
     * @example
     * $updated = $repository->updateSettingStatus('maintenance_mode', 'INACTIVE');
     */
    public function updateSettingStatus(string $param, string $newStatus) : bool;

    /**
     * Actualiza el valor de un parámetro
     *
     * @param string $param    Nombre del parámetro
     * @param string $newValue Nuevo valor
     *
     * @return bool True si se actualizó correctamente
     *
     * @example
     * $updated = $repository->updateSettingValue('max_users', '1000');
     */
    public function updateSettingValue(string $param, string $newValue) : bool;
}
