<?php

declare(strict_types=1);

namespace App\Repositories\Contracts\Settings;

use App\Models\Settings\Settings;
use App\Repositories\Contracts\BaseRepositoryContract;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de configuraciones del sistema
 *
 * Define los métodos disponibles para gestionar parámetros de configuración,
 * incluyendo operaciones CRUD y consultas específicas del dominio.
 *
 * @package App\Repositories\Contracts\Settings
 * @author Sistema de Gestión de Configuraciones
 * @version 1.0
 */
interface SettingRepositoryContract extends BaseRepositoryContract
{
    /**
     * Obtiene el valor de un parámetro específico
     *
     * @param string $param Nombre del parámetro
     * @return string|null Valor del parámetro o null si no existe
     *
     * @example
     * $value = $repository->getParameterValue('maintenance_mode');
     */
    public function getParameterValue(string $param): ?string;

    /**
     * Obtiene el estado de un parámetro específico
     *
     * @param string $param Nombre del parámetro
     * @return string|null Estado del parámetro o null si no existe
     *
     * @example
     * $status = $repository->getParameterStatus('api_enabled');
     */
    public function getParameterStatus(string $param): ?string;

    /**
     * Obtiene todos los parámetros activos
     *
     * @return Collection<Settings> Configuraciones activas
     *
     * @example
     * $activeSettings = $repository->getActiveSettings();
     */
    public function getActiveSettings(): Collection;

    /**
     * Obtiene todos los parámetros inactivos
     *
     * @return Collection<Settings> Configuraciones inactivas
     *
     * @example
     * $inactiveSettings = $repository->getInactiveSettings();
     */
    public function getInactiveSettings(): Collection;

    /**
     * Obtiene configuraciones por estado
     *
     * @param string $status Estado ('ACTIVE', 'INACTIVE')
     * @return Collection<Settings> Configuraciones con el estado especificado
     *
     * @example
     * $settings = $repository->getSettingsByStatus('ACTIVE');
     */
    public function getSettingsByStatus(string $status): Collection;

    /**
     * Busca parámetros por nombre parcial
     *
     * @param string $searchTerm Término de búsqueda
     * @param array $filters Filtros adicionales
     * @return Collection<Settings> Configuraciones que coinciden
     *
     * @example
     * $settings = $repository->searchSettingsByParam('mail', ['status' => 'ACTIVE']);
     */
    public function searchSettingsByParam(string $searchTerm, array $filters = []): Collection;

    /**
     * Actualiza o crea un parámetro de configuración
     *
     * @param string $param Nombre del parámetro
     * @param string $value Valor del parámetro
     * @param string $status Estado del parámetro
     * @return Settings Configuración actualizada o creada
     *
     * @example
     * $setting = $repository->updateOrCreateSetting('theme', 'dark', 'ACTIVE');
     */
    public function updateOrCreateSetting(string $param, string $value, string $status = 'ACTIVE'): Settings;

    /**
     * Actualiza el estado de un parámetro
     *
     * @param string $param Nombre del parámetro
     * @param string $newStatus Nuevo estado
     * @return bool True si se actualizó correctamente
     *
     * @example
     * $updated = $repository->updateSettingStatus('maintenance_mode', 'INACTIVE');
     */
    public function updateSettingStatus(string $param, string $newStatus): bool;

    /**
     * Actualiza el valor de un parámetro
     *
     * @param string $param Nombre del parámetro
     * @param string $newValue Nuevo valor
     * @return bool True si se actualizó correctamente
     *
     * @example
     * $updated = $repository->updateSettingValue('max_users', '1000');
     */
    public function updateSettingValue(string $param, string $newValue): bool;

    /**
     * Elimina un parámetro de configuración
     *
     * @param string $param Nombre del parámetro
     * @return bool True si se eliminó correctamente
     *
     * @example
     * $deleted = $repository->deleteSetting('deprecated_feature');
     */
    public function deleteSetting(string $param): bool;

    /**
     * Verifica si un parámetro existe
     *
     * @param string $param Nombre del parámetro
     * @return bool True si el parámetro existe
     *
     * @example
     * $exists = $repository->settingExists('app_name');
     */
    public function settingExists(string $param): bool;

    /**
     * Verifica si un parámetro está activo
     *
     * @param string $param Nombre del parámetro
     * @return bool True si el parámetro está activo
     *
     * @example
     * $isActive = $repository->isSettingActive('email_notifications');
     */
    public function isSettingActive(string $param): bool;

    /**
     * Obtiene configuraciones por rango de fechas de creación
     *
     * @param string $startDate Fecha de inicio
     * @param string $endDate Fecha de fin
     * @param array $filters Filtros adicionales
     * @return Collection<Settings> Configuraciones en el rango
     *
     * @example
     * $settings = $repository->getSettingsByDateRange('2024-01-01', '2024-01-31');
     */
    public function getSettingsByDateRange(string $startDate, string $endDate, array $filters = []): Collection;

    /**
     * Cuenta configuraciones por estado
     *
     * @return array Conteo por estado
     *
     * @example
     * $counts = $repository->countSettingsByStatus();
     * // ['ACTIVE' => 25, 'INACTIVE' => 5]
     */
    public function countSettingsByStatus(): array;

    /**
     * Obtiene estadísticas generales de configuraciones
     *
     * @return array Estadísticas generales
     *
     * @example
     * $stats = $repository->getGeneralSettingStats();
     */
    public function getGeneralSettingStats(): array;

    /**
     * Obtiene todas las configuraciones con paginación
     *
     * @param int $perPage Elementos por página
     * @param array $filters Filtros adicionales
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Configuraciones paginadas
     *
     * @example
     * $paginatedSettings = $repository->getPaginatedSettings(20, ['status' => 'ACTIVE']);
     */
    public function getPaginatedSettings(int $perPage = 15, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    /**
     * Actualiza múltiples configuraciones en lote
     *
     * @param array $settings Array de configuraciones [param => [value, status]]
     * @return int Número de configuraciones actualizadas
     *
     * @example
     * $updated = $repository->bulkUpdateSettings([
     *     'theme' => ['dark', 'ACTIVE'],
     *     'language' => ['es', 'ACTIVE']
     * ]);
     */
    public function bulkUpdateSettings(array $settings): int;

    /**
     * Obtiene configuraciones de sistema críticas
     *
     * @return Collection<Settings> Configuraciones críticas del sistema
     *
     * @example
     * $criticalSettings = $repository->getCriticalSystemSettings();
     */
    public function getCriticalSystemSettings(): Collection;
}