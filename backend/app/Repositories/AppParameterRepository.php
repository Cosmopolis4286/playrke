<?php

namespace App\Repositories;

use App\Models\AppParameter;
use App\Repositories\BaseRepository;
use App\Contracts\AppParameterRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Repositorio para la gestión de parámetros de configuración de la app
 *
 * Implementa todas las operaciones de base de datos relacionadas con
 * los parámetros de configuración del sistema.
 *
 * @package App\Repositories
 * @version 1.2
 */
class AppParameterRepository extends BaseRepository implements AppParameterRepositoryInterface
{
    public function __construct(AppParameter $model)
    {
        $this->model = $model;
    }

    /**
     * Obtiene el valor de un parámetro específico
     */
    public function getParameterValue(string $param): ?string
    {
        return $this->model
            ->select('value')
            ->where('param', $param)
            ->first()
            ?->value;
    }

    /**
     * Obtiene el estado de un parámetro específico
     */
    public function getParameterStatus(string $param): ?string
    {
        return $this->model
            ->select('status')
            ->where('param', $param)
            ->first()
            ?->status;
    }

    /**
     * Obtiene todos los parámetros activos
     */
    public function getActiveSetting(): Collection
    {
        return $this->model
            ->where('status', AppParameter::STATUS_ACTIVE)
            ->orderBy('param')
            ->get();
    }

    /**
     * Obtiene todos los parámetros inactivos
     */
    public function getInactiveSetting(): Collection
    {
        return $this->model
            ->where('status', AppParameter::STATUS_INACTIVE)
            ->orderBy('param')
            ->get();
    }

    /**
     * Obtiene parámetros por estado
     */
    public function getSettingByStatus(string $status): Collection
    {
        return $this->model
            ->where('status', $status)
            ->orderBy('param')
            ->get();
    }

    /**
     * Busca parámetros por nombre parcial
     */
    public function searchSettingByParam(string $searchTerm, array $filters = []): Collection
    {
        $query = $this->model
            ->where('param', 'LIKE', "%{$searchTerm}%");

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('param')->get();
    }

    /**
     * Actualiza o crea un parámetro de configuración
     */
    public function updateOrCreateSetting(
        string $param,
        string $value,
        string $status = AppParameter::STATUS_ACTIVE
    ): AppParameter {
        return $this->model->updateOrCreate(
            ['param' => $param],
            [
                'value'  => $value,
                'status' => $status,
            ]
        );
    }

    /**
     * Actualiza el estado de un parámetro
     */
    public function updateSettingStatus(string $param, string $newStatus): bool
    {
        return $this->model
            ->where('param', $param)
            ->update(['status' => $newStatus]) > 0;
    }

    /**
     * Actualiza el valor de un parámetro
     */
    public function updateSettingValue(string $param, string $newValue): bool
    {
        return $this->model
            ->where('param', $param)
            ->update(['value' => $newValue]) > 0;
    }

    /**
     * Elimina un parámetro de configuración
     */
    public function deleteSetting(string $param): bool
    {
        return $this->model
            ->where('param', $param)
            ->delete() > 0;
    }

    /**
     * Verifica si un parámetro existe
     */
    public function settingExists(string $param): bool
    {
        return $this->model
            ->where('param', $param)
            ->exists();
    }

    /**
     * Verifica si un parámetro está activo
     */
    public function isSettingActive(string $param): bool
    {
        return $this->model
            ->where('param', $param)
            ->where('status', AppParameter::STATUS_ACTIVE)
            ->exists();
    }

    /**
     * Obtiene parámetros por rango de fechas de creación
     */
    public function getSettingByDateRange(
        string $startDate,
        string $endDate,
        array $filters = []
    ): Collection {
        $query = $this->model
            ->whereBetween('created_at', [$startDate, $endDate]);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Cuenta parámetros por estado
     */
    public function countSettingByStatus(): array
    {
        $counts = $this->model
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        foreach ([AppParameter::STATUS_ACTIVE, AppParameter::STATUS_INACTIVE] as $status) {
            $counts[$status] = $counts[$status] ?? 0;
        }

        return $counts;
    }

    /**
     * Obtiene estadísticas generales de parámetros
     */
    public function getGeneralSettingStats(): array
    {
        $stats = $this->model
            ->selectRaw(
                'COUNT(*) as total,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as active,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as inactive',
                [AppParameter::STATUS_ACTIVE, AppParameter::STATUS_INACTIVE]
            )
            ->first();

        return [
            'total'    => (int) ($stats->total ?? 0),
            'active'   => (int) ($stats->active ?? 0),
            'inactive' => (int) ($stats->inactive ?? 0),
        ];
    }

    /**
     * Obtiene todos los parámetros con paginación
     */
    public function getPaginatedSetting(
        int $perPage = 15,
        array $filters = []
    ): LengthAwarePaginator {
        $query = $this->model->newQuery();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $query->where('param', 'LIKE', "%{$filters['search']}%");
        }

        return $query
            ->orderBy('param')
            ->paginate($perPage);
    }

    /**
     * Actualiza múltiples parámetros en lote
     */
    public function bulkUpdateSetting(array $settings): int
    {
        $updatedCount = 0;

        DB::transaction(function () use ($settings, &$updatedCount) {
            foreach ($settings as $param => $config) {
                $value  = $config[0] ?? null;
                $status = $config[1] ?? AppParameter::STATUS_ACTIVE;

                if ($value === null) {
                    continue;
                }

                $setting = $this->model->updateOrCreate(
                    ['param' => $param],
                    [
                        'value'  => $value,
                        'status' => $status,
                    ]
                );

                if ($setting->wasRecentlyCreated || $setting->wasChanged()) {
                    $updatedCount++;
                }
            }
        });

        return $updatedCount;
    }

    /**
     * Obtiene parámetros de sistema críticos
     */
    public function getCriticalSystemSetting(): Collection
    {
        return $this->model
            ->whereIn('param', AppParameter::CRITICAL_PARAMS)
            ->where('status', AppParameter::STATUS_ACTIVE)
            ->orderBy('param')
            ->get();
    }

    /**
     * Obtiene parámetros más recientes
     */
    public function getRecentSetting(
        int $limit = 10,
        array $filters = []
    ): Collection {
        $query = $this->model->newQuery();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Busca parámetros por valor
     */
    public function searchSettingByValue(string $searchTerm, array $filters = []): Collection
    {
        $query = $this->model
            ->where('value', 'LIKE', "%{$searchTerm}%");

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query
            ->orderBy('param')
            ->get();
    }

    /**
     * Obtiene parámetros duplicados por valor
     */
    public function getDuplicatedValueSetting(): Collection
    {
        $duplicates = $this->model
            ->select('value')
            ->groupBy('value')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('value');

        return $this->model
            ->whereIn('value', $duplicates)
            ->orderBy('param')
            ->get();
    }

    /**
     * Backup de parámetros críticos
     */
    public function backupCriticalSetting(): array
    {
        return $this->getCriticalSystemSetting()
            ->pluck('value', 'param')
            ->toArray();
    }

    /**
     * Restaura parámetros desde backup
     */
    public function restoreSettingFromBackup(array $backup): int
    {
        $restoredCount = 0;

        DB::transaction(function () use ($backup, &$restoredCount) {
            foreach ($backup as $param => $value) {
                $this->updateOrCreateSetting($param, (string) $value);
                $restoredCount++;
            }
        });

        return $restoredCount;
    }
}
