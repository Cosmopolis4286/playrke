<?php

declare(strict_types=1);

namespace App\Repositories\Settings;

use App\Models\Settings\Setting;
use App\Repositories\BaseRepository;
use App\Repositories\Contracts\Settings\SettingRepositoryContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Repositorio para la gestión de configuraciones del sistema
 *
 * Implementa todas las operaciones de base de datos relacionadas con
 * los parámetros de configuración del sistema.
 *
 * @package App\Repositories\Setting
 * @author Sistema de Gestión de Configuraciones
 * @version 1.0
 */
class SettingRepository extends BaseRepository implements SettingRepositoryContract
{
    /**
     * Crear instancia del modelo Setting
     *
     * @return Model
     */
    protected function makeModel(): Model
    {
        return new Setting();
    }

    /**
     * Obtiene el valor de un parámetro específico
     */
    public function getParameterValue(string $param): ?string
    {
        return $this->model->where('param', $param)->value('value');
    }

    /**
     * Obtiene el estado de un parámetro específico
     */
    public function getParameterStatus(string $param): ?string
    {
        return $this->model->where('param', $param)->value('status');
    }

    /**
     * Obtiene todos los parámetros activos
     */
    public function getActiveSetting(): Collection
    {
        return $this->model->where('status', 'ACTIVE')
                          ->orderBy('param')
                          ->get();
    }

    /**
     * Obtiene todos los parámetros inactivos
     */
    public function getInactiveSetting(): Collection
    {
        return $this->model->where('status', 'INACTIVE')
                          ->orderBy('param')
                          ->get();
    }

    /**
     * Obtiene configuraciones por estado
     */
    public function getSettingByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)
                          ->orderBy('param')
                          ->get();
    }

    /**
     * Busca parámetros por nombre parcial
     */
    public function searchSettingByParam(string $searchTerm, array $filters = []): Collection
    {
        $query = $this->model->where('param', 'LIKE', "%{$searchTerm}%");

        // Aplicar filtros
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('param')->get();
    }

    /**
     * Actualiza o crea un parámetro de configuración
     */
    public function updateOrCreateSetting(string $param, string $value, string $status = 'ACTIVE'): Setting
    {
        return $this->model->updateOrCreate(
            ['param' => $param],
            [
                'value' => $value,
                'status' => $status,
            ]
        );
    }

    /**
     * Actualiza el estado de un parámetro
     */
    public function updateSettingtatus(string $param, string $newStatus): bool
    {
        return $this->model->where('param', $param)
                          ->update(['status' => $newStatus]) > 0;
    }

    /**
     * Actualiza el valor de un parámetro
     */
    public function updateSettingValue(string $param, string $newValue): bool
    {
        return $this->model->where('param', $param)
                          ->update(['value' => $newValue]) > 0;
    }

    /**
     * Elimina un parámetro de configuración
     */
    public function deleteSetting(string $param): bool
    {
        return $this->model->where('param', $param)->delete() > 0;
    }

    /**
     * Verifica si un parámetro existe
     */
    public function settingExists(string $param): bool
    {
        return $this->model->where('param', $param)->exists();
    }

    /**
     * Verifica si un parámetro está activo
     */
    public function isSettingActive(string $param): bool
    {
        return $this->model->where('param', $param)
                          ->where('status', 'ACTIVE')
                          ->exists();
    }

    /**
     * Obtiene configuraciones por rango de fechas de creación
     */
    public function getSettingByDateRange(string $startDate, string $endDate, array $filters = []): Collection
    {
        $query = $this->model->whereBetween('created_at', [$startDate, $endDate]);

        // Aplicar filtros adicionales
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Cuenta configuraciones por estado
     */
    public function countSettingByStatus(): array
    {
        $counts = $this->model->select('status', DB::raw('count(*) as total'))
                             ->groupBy('status')
                             ->pluck('total', 'status')
                             ->toArray();

        // Asegurar que todos los estados estén presentes
        $validStatuses = ['ACTIVE', 'INACTIVE'];
        foreach ($validStatuses as $status) {
            if (!isset($counts[$status])) {
                $counts[$status] = 0;
            }
        }

        return $counts;
    }

    /**
     * Obtiene estadísticas generales de configuraciones
     */
    public function getGeneralSettingtats(): array
    {
        $stats = $this->model->selectRaw('
                                COUNT(*) as total_Setting,
                                SUM(CASE WHEN status = "ACTIVE" THEN 1 ELSE 0 END) as active_Setting,
                                SUM(CASE WHEN status = "INACTIVE" THEN 1 ELSE 0 END) as inactive_Setting
                            ')
                            ->first();

        return [
            'total_Setting' => $stats->total_Setting ?? 0,
            'active_Setting' => $stats->active_Setting ?? 0,
            'inactive_Setting' => $stats->inactive_Setting ?? 0,
        ];
    }

    /**
     * Obtiene todas las configuraciones con paginación
     */
    public function getPaginatedSetting(int $perPage = 15, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        // Aplicar filtros
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $query->where('param', 'LIKE', "%{$filters['search']}%");
        }

        return $query->orderBy('param')
                    ->paginate($perPage);
    }

    /**
     * Actualiza múltiples configuraciones en lote
     */
    public function bulkUpdateSetting(array $Setting): int
    {
        $updatedCount = 0;

        foreach ($Setting as $param => $config) {
            [$value, $status] = $config;

            $updated = $this->model->updateOrCreate(
                ['param' => $param],
                [
                    'value' => $value,
                    'status' => $status,
                ]
            );

            if ($updated->wasRecentlyCreated || $updated->wasChanged()) {
                $updatedCount++;
            }
        }

        return $updatedCount;
    }

    /**
     * Obtiene configuraciones de sistema críticas
     */
    public function getCriticalSystemSetting(): Collection
    {
        $criticalParams = [
            'app_env',
            'app_debug',
            'maintenance_mode',
            'api_enabled',
            'database_status',
            'cache_enabled',
            'queue_enabled',
            'security_level'
        ];

        return $this->model->whereIn('param', $criticalParams)
                          ->where('status', 'ACTIVE')
                          ->orderBy('param')
                          ->get();
    }

    /**
     * Obtiene configuraciones más recientes
     */
    public function getRecentSetting(int $limit = 10, array $filters = []): Collection
    {
        $query = $this->model->newQuery();

        // Aplicar filtros
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('updated_at', 'desc')
                    ->limit($limit)
                    ->get();
    }

    /**
     * Busca configuraciones por valor
     */
    public function searchSettingByValue(string $searchTerm, array $filters = []): Collection
    {
        $query = $this->model->where('value', 'LIKE', "%{$searchTerm}%");

        // Aplicar filtros
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('param')->get();
    }

    /**
     * Obtiene configuraciones duplicadas por valor
     */
    public function getDuplicatedValueSetting(): Collection
    {
        return $this->model->select('value', DB::raw('COUNT(*) as count'))
                          ->groupBy('value')
                          ->having('count', '>', 1)
                          ->with(['Setting' => function($query) {
                              $query->orderBy('param');
                          }])
                          ->get();
    }

    /**
     * Backup de configuraciones críticas
     */
    public function backupCriticalSetting(): array
    {
        return $this->getCriticalSystemSetting()
                   ->pluck('value', 'param')
                   ->toArray();
    }

    /**
     * Restaura configuraciones desde backup
     */
    public function restoreSettingFromBackup(array $backup): int
    {
        $restoredCount = 0;

        foreach ($backup as $param => $value) {
            $this->updateOrCreateSetting($param, $value, 'ACTIVE');
            $restoredCount++;
        }

        return $restoredCount;
    }
}