<?php

namespace App\Repositories\Users\Shared;

use App\Models\Users\Shared\TimeZone;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Users\Shared\Contracts\TimeZoneRepositoryContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Repositorio para la gestión de zonas horarias.
 *
 * Implementa las operaciones específicas para el modelo TimeZone,
 * incluyendo consultas por país, estado, UTC y gestión de
 * configuraciones de zona horaria.
 */
class TimeZoneRepository extends BaseRepository implements TimeZoneRepositoryContract
{
    /**
     * Constructor del repositorio.
     *
     * @param TimeZone $model Instancia del modelo TimeZone
     */
    public function __construct(TimeZone $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritDoc}
     */
    public function getByIdWithFields(string $id, array $fields = ['*']): mixed
    {
        return $this->model->select($fields)->where('id', $id)->first();
    }

    /**
     * {@inheritDoc}
     */
    public function getByCountry(string $countryId): Collection
    {
        return $this->model->where('country_id', $countryId)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getActive(): Collection
    {
        return $this->model->where('status', true)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getInactive(): Collection
    {
        return $this->model->where('status', false)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByStatus(bool $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByName(string $name): Collection
    {
        return $this->model->where('name', 'LIKE', "%{$name}%")->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByExactName(string $name): mixed
    {
        return $this->model->where('name', $name)->first();
    }

    /**
     * {@inheritDoc}
     */
    public function getByUtcOffset(string $utcOffset): Collection
    {
        return $this->model->where('utc', $utcOffset)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByUtcRange(string $minOffset, string $maxOffset): Collection
    {
        return $this->model->whereBetween('utc', [$minOffset, $maxOffset])->get();
    }

    /**
     * {@inheritDoc}
     */
    public function createTimeZone(array $data): mixed
    {
        // Validar datos antes de crear
        $errors = $this->validateTimeZoneData($data);
        if (!empty($errors)) {
            throw new \InvalidArgumentException('Datos de zona horaria inválidos: ' . json_encode($errors));
        }

        return $this->model->create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function updateTimeZone(string $timeZoneId, array $data): bool
    {
        // Validar datos antes de actualizar
        $errors = $this->validateTimeZoneData($data, $timeZoneId);
        if (!empty($errors)) {
            throw new \InvalidArgumentException('Datos de zona horaria inválidos: ' . json_encode($errors));
        }

        return $this->model->where('id', $timeZoneId)->update($data) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function updateStatus(string $timeZoneId, bool $status): bool
    {
        return $this->model->where('id', $timeZoneId)
            ->update(['status' => $status]) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function activateTimeZone(string $timeZoneId): bool
    {
        return $this->updateStatus($timeZoneId, true);
    }

    /**
     * {@inheritDoc}
     */
    public function deactivateTimeZone(string $timeZoneId): bool
    {
        return $this->updateStatus($timeZoneId, false);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteTimeZone(string $timeZoneId): bool
    {
        return $this->model->where('id', $timeZoneId)->delete() > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function getTimeZoneStats(): array
    {
        $total = $this->model->count();
        $active = $this->model->where('status', true)->count();
        $inactive = $this->model->where('status', false)->count();
        $withCountry = $this->model->whereNotNull('country_id')->count();
        $uniqueUtc = $this->model->distinct('utc')->count();

        return [
            'total_time_zones' => $total,
            'active_time_zones' => $active,
            'inactive_time_zones' => $inactive,
            'with_country' => $withCountry,
            'unique_utc_offsets' => $uniqueUtc,
            'active_percentage' => $total > 0 ? round(($active / $total) * 100, 2) : 0,
            'country_percentage' => $total > 0 ? round(($withCountry / $total) * 100, 2) : 0
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getCountryTimeZoneStats(string $countryId): array
    {
        $timeZones = $this->getByCountry($countryId);
        $active = $timeZones->where('status', true);

        return [
            'total_time_zones' => $timeZones->count(),
            'active_time_zones' => $active->count(),
            'inactive_time_zones' => $timeZones->count() - $active->count(),
            'unique_utc_offsets' => $timeZones->unique('utc')->count(),
            'active_percentage' => $timeZones->count() > 0 ? round(($active->count() / $timeZones->count()) * 100, 2) : 0
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getDistributionByCountry(): array
    {
        return $this->model->select('countries.name', DB::raw('COUNT(time_zones.id) as count'))
            ->leftJoin('countries', 'time_zones.country_id', '=', 'countries.id')
            ->groupBy('countries.id', 'countries.name')
            ->pluck('count', 'countries.name')
            ->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function getDistributionByUtc(): array
    {
        return $this->model->select('utc', DB::raw('COUNT(*) as count'))
            ->groupBy('utc')
            ->orderBy('utc')
            ->pluck('count', 'utc')
            ->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function validateTimeZoneData(array $data, ?string $excludeId = null): array
    {
        $errors = [];

        // Validar nombre requerido
        if (isset($data['name']) && empty(trim($data['name']))) {
            $errors['name'] = 'El nombre de la zona horaria es requerido';
        }

        // Validar nombre único
        if (isset($data['name'])) {
            $query = $this->model->where('name', $data['name']);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
            if ($query->exists()) {
                $errors['name'] = 'Ya existe una zona horaria con este nombre';
            }
        }

        // Validar formato UTC
        if (isset($data['utc']) && !empty($data['utc'])) {
            if (!preg_match('/^[+-]\d{2}:\d{2}$/', $data['utc'])) {
                $errors['utc'] = 'El formato UTC debe ser ±HH:MM (ej: +05:00, -03:00)';
            }
        }

        // Validar que el país existe si está presente
        if (isset($data['country_id']) && !empty($data['country_id'])) {
            $countryExists = DB::table('countries')->where('id', $data['country_id'])->exists();
            if (!$countryExists) {
                $errors['country_id'] = 'El país especificado no existe';
            }
        }

        return $errors;
    }

    /**
     * {@inheritDoc}
     */
    public function getOrderedByName(string $direction = 'asc', bool $activeOnly = false): Collection
    {
        $query = $this->model->orderBy('name', $direction);

        if ($activeOnly) {
            $query->where('status', true);
        }

        return $query->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrderedByUtc(string $direction = 'asc', bool $activeOnly = false): Collection
    {
        $query = $this->model->orderBy('utc', $direction);

        if ($activeOnly) {
            $query->where('status', true);
        }

        return $query->get();
    }

    /**
     * {@inheritDoc}
     */
    public function searchByCriteria(array $criteria): Collection
    {
        $query = $this->model->newQuery();

        if (isset($criteria['name'])) {
            $query->where('name', 'LIKE', "%{$criteria['name']}%");
        }

        if (isset($criteria['utc'])) {
            $query->where('utc', $criteria['utc']);
        }

        if (isset($criteria['country_id'])) {
            $query->where('country_id', $criteria['country_id']);
        }

        if (isset($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        return $query->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getForSelect(bool $activeOnly = true, ?string $countryFilter = null): array
    {
        $query = $this->model->select('id', 'name', 'utc');

        if ($activeOnly) {
            $query->where('status', true);
        }

        if ($countryFilter) {
            $query->where('country_id', $countryFilter);
        }

        return $query->orderBy('name')
            ->get()
            ->mapWithKeys(function ($timeZone) {
                return [$timeZone->id => "{$timeZone->name} ({$timeZone->utc})"];
            })
            ->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function isTimeZoneInUse(string $timeZoneId): bool
    {
        // Verificar si la zona horaria está siendo utilizada por usuarios
        $inUsers = DB::table('users')->where('time_zone_id', $timeZoneId)->exists();

        // Verificar otras tablas que puedan usar time_zone_id
        $inSettings = DB::table('settings')
            ->where('key', 'default_timezone')
            ->where('value', $timeZoneId)
            ->exists();

        return $inUsers || $inSettings;
    }

    /**
     * {@inheritDoc}
     */
    public function getMostUsedTimeZone(): mixed
    {
        return $this->model->select('time_zones.*', DB::raw('COUNT(users.id) as usage_count'))
            ->leftJoin('users', 'time_zones.id', '=', 'users.time_zone_id')
            ->groupBy('time_zones.id')
            ->orderBy('usage_count', 'desc')
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function getUnusedTimeZones(): Collection
    {
        return $this->model->select('time_zones.*')
            ->leftJoin('users', 'time_zones.id', '=', 'users.time_zone_id')
            ->whereNull('users.time_zone_id')
            ->groupBy('time_zones.id')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function createBulkTimeZones(array $timeZonesData): Collection
    {
        $timeZones = collect();

        foreach ($timeZonesData as $timeZoneData) {
            $timeZone = $this->createTimeZone($timeZoneData);
            $timeZones->push($timeZone);
        }

        return $timeZones;
    }
}
