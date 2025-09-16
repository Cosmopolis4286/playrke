<?php

namespace App\Repositories\Users\Roles;

use App\Models\Users\Roles\Role;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Users\Roles\Contracts\RoleRepositoryContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * Repositorio para la gestión de roles de usuario.
 *
 * Implementa las operaciones específicas para el modelo Role,
 * incluyendo gestión de estados, búsquedas y estadísticas de uso.
 */
class RoleRepository extends BaseRepository implements RoleRepositoryContract
{
    /**
     * Constructor del repositorio.
     *
     * @param Role $model Instancia del modelo Role
     */
    public function __construct(Role $model)
    {
        parent::__construct($model);
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
    public function getActiveRoles(): Collection
    {
        return $this->getByStatus(true);
    }

    /**
     * {@inheritDoc}
     */
    public function getInactiveRoles(): Collection
    {
        return $this->getByStatus(false);
    }

    /**
     * {@inheritDoc}
     */
    public function getByName(string $name): ?Role
    {
        return $this->model->where('name', $name)->first();
    }

    /**
     * {@inheritDoc}
     */
    public function getByPrefix(string $prefix): Collection
    {
        return $this->model->where('prefix', $prefix)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function searchByDescription(string $searchTerm): Collection
    {
        return $this->model->where('description', 'LIKE', "%{$searchTerm}%")->get();
    }

    /**
     * {@inheritDoc}
     */
    public function existsByName(string $name, ?int $excludeId = null): bool
    {
        $query = $this->model->where('name', $name);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function createRole(string $name, ?string $prefix = null, ?string $description = null, bool $status = true): Role
    {
        return $this->model->create([
            'name' => $this->normalizeName($name),
            'prefix' => $prefix ? strtoupper(trim($prefix)) : null,
            'description' => $description ? trim($description) : null,
            'status' => $status
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function updateRole(int $roleId, array $data): bool
    {
        // Normalizar datos si están presentes
        if (isset($data['name'])) {
            $data['name'] = $this->normalizeName($data['name']);
        }

        if (isset($data['prefix'])) {
            $data['prefix'] = strtoupper(trim($data['prefix']));
        }

        if (isset($data['description'])) {
            $data['description'] = trim($data['description']);
        }

        return $this->model->where('id', $roleId)->update($data) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function updateStatus(int $roleId, bool $status): bool
    {
        return $this->model->where('id', $roleId)
            ->update(['status' => $status]) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function activateRole(int $roleId): bool
    {
        return $this->updateStatus($roleId, true);
    }

    /**
     * {@inheritDoc}
     */
    public function deactivateRole(int $roleId): bool
    {
        return $this->updateStatus($roleId, false);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteRole(int $roleId): bool
    {
        return $this->model->where('id', $roleId)->delete() > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function bulkUpdateStatus(array $roleIds, bool $status): int
    {
        return $this->model->whereIn('id', $roleIds)
            ->update(['status' => $status]);
    }

    /**
     * {@inheritDoc}
     */
    public function getRoleStatistics(): array
    {
        $total = $this->model->count();
        $active = $this->model->where('status', true)->count();
        $inactive = $this->model->where('status', false)->count();
        $withPrefix = $this->model->whereNotNull('prefix')->count();
        $withDescription = $this->model->whereNotNull('description')->count();
        $averageNameLength = $this->model->avg(\DB::raw('LENGTH(name)'));

        return [
            'total_roles' => $total,
            'active_roles' => $active,
            'inactive_roles' => $inactive,
            'active_percentage' => $total > 0 ? round(($active / $total) * 100, 2) : 0,
            'with_prefix' => $withPrefix,
            'with_description' => $withDescription,
            'prefix_percentage' => $total > 0 ? round(($withPrefix / $total) * 100, 2) : 0,
            'description_percentage' => $total > 0 ? round(($withDescription / $total) * 100, 2) : 0,
            'average_name_length' => round($averageNameLength ?? 0, 2)
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getOrderedByName(bool $activeOnly = false): Collection
    {
        $query = $this->model->orderBy('name');

        if ($activeOnly) {
            $query->where('status', true);
        }

        return $query->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getMostUsedRoles(int $limit = 10): Collection
    {
        return $this->model->select('roles.*')
            ->selectRaw('COUNT(role_user.user_id) as user_count')
            ->leftJoin('role_user', 'roles.id', '=', 'role_user.role_id')
            ->groupBy('roles.id')
            ->orderBy('user_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getUnusedRoles(): Collection
    {
        return $this->model->select('roles.*')
            ->leftJoin('role_user', 'roles.id', '=', 'role_user.role_id')
            ->whereNull('role_user.role_id')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function isRoleInUse(int $roleId): bool
    {
        return \DB::table('role_user')
            ->where('role_id', $roleId)
            ->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function getUserCountByRole(int $roleId): int
    {
        return \DB::table('role_user')
            ->where('role_id', $roleId)
            ->count();
    }

    /**
     * {@inheritDoc}
     */
    public function getRolesWithUsageStats(bool $activeOnly = true): Collection
    {
        $query = $this->model->select('roles.*')
            ->selectRaw('COUNT(role_user.user_id) as user_count')
            ->leftJoin('role_user', 'roles.id', '=', 'role_user.role_id')
            ->groupBy('roles.id')
            ->orderBy('user_count', 'desc');

        if ($activeOnly) {
            $query->where('roles.status', true);
        }

        return $query->get();
    }

    /**
     * {@inheritDoc}
     */
    public function createBulkRoles(array $rolesData, bool $status = true): Collection
    {
        $roles = collect();

        foreach ($rolesData as $roleData) {
            $name = $roleData['name'] ?? '';
            $prefix = $roleData['prefix'] ?? null;
            $description = $roleData['description'] ?? null;

            // Verificar si ya existe antes de crear
            if (!$this->existsByName($name)) {
                $role = $this->createRole($name, $prefix, $description, $status);
                $roles->push($role);
            }
        }

        return $roles;
    }

    /**
     * {@inheritDoc}
     */
    public function getForSelect(bool $activeOnly = true): array
    {
        $query = $this->model->orderBy('name');

        if ($activeOnly) {
            $query->where('status', true);
        }

        return $query->pluck('name', 'id')->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function isValidName(string $name): bool
    {
        $normalized = $this->normalizeName($name);

        // Verificar longitud
        if (strlen($normalized) < 2 || strlen($normalized) > 50) {
            return false;
        }

        // Verificar caracteres válidos (letras, números, espacios, guiones)
        if (!preg_match('/^[\p{L}\p{N}\s\-_]+$/u', $normalized)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function normalizeName(string $name): string
    {
        // Limpiar espacios extra
        $normalized = trim($name);

        // Convertir a título (primera letra de cada palabra en mayúscula)
        $normalized = Str::title($normalized);

        // Reemplazar múltiples espacios con uno solo
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return $normalized;
    }

    /**
     * {@inheritDoc}
     */
    public function getNameSuggestions(string $partialText, int $limit = 5): Collection
    {
        return $this->model->where('name', 'LIKE', "%{$partialText}%")
            ->where('status', true)
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getRolesByUserType(string $userType): Collection
    {
        return $this->model->where('prefix', 'LIKE', "%{$userType}%")
            ->where('status', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function canBeDeleted(int $roleId): bool
    {
        return !$this->isRoleInUse($roleId);
    }

    /**
     * {@inheritDoc}
     */
    public function getSimilarRoles(string $prefix, ?int $excludeId = null): Collection
    {
        $query = $this->model->where('prefix', $prefix);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->orderBy('name')->get();
    }
}