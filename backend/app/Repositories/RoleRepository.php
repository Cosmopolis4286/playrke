<?php

namespace App\Repositories;

use App\Repositories\BaseRepository;
use App\Contracts\RoleRepositoryInterface;
use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Repositorio para la gestión de roles de usuario.
 *
 * Implementa todas las operaciones específicas para el modelo Role,
 * incluyendo gestión de estados, búsquedas, estadísticas y validaciones.
 */
class RoleRepository extends BaseRepository implements RoleRepositoryInterface
{
    /**
     * Constructor del repositorio.
     *
     * @param Role $model
     */
    public function __construct(Role $model)
    {
        $this->model = $model;
    }

    /**
     * Obtener roles activos
     *
     * @return Collection<Role>
     */
    public function getActiveRoles(): Collection
    {
        return $this->getByStatus(true);
    }

    /**
     * Obtener roles inactivos
     *
     * @return Collection<Role>
     */
    public function getInactiveRoles(): Collection
    {
        return $this->getByStatus(false);
    }

    /**
     * Buscar rol por nombre exacto
     *
     * @param string $name
     * @return Role|null
     */
    public function getByName(string $name): ?Role
    {
        return $this->model->where('name', $name)->first();
    }

    /**
     * Obtener roles por prefijo
     *
     * @param string $prefix
     * @return Collection<Role>
     */
    public function getByPrefix(string $prefix): Collection
    {
        return $this->model->where('prefix', $prefix)->get();
    }

    /**
     * Obtener el rol asignado a un usuario
     *
     * @param int $userId
     * @return Role|null
     */
    public function getByUserId(int $userId): ?Role
    {
        return $this->model
            ->select('roles.*')
            ->join('role_user', 'roles.id', '=', 'role_user.role_id')
            ->where('role_user.user_id', $userId)
            ->first();
    }

    /**
     * Obtener prefijos de roles activos asignados a un usuario.
     *
     * @param string $userId
     * @return array<string>
     */
    public function getRolesByUserId(string $userId): array
    {
        return $this->model
            ->where('user_id', $userId)
            ->whereNull('expires_at') // o condiciones para asignaciones activas
            ->orWhere('expires_at', '>', now())
            ->with('role') // relación con Role para obtener prefijo
            ->get()
            ->pluck('role.prefix')
            ->toArray();
    }

    /**
     * Buscar roles por descripción
     *
     * @param string $searchTerm
     * @return Collection<Role>
     */
    public function searchByDescription(string $searchTerm): Collection
    {
        return $this->model->where('description', 'LIKE', "%{$searchTerm}%")->get();
    }

    /**
     * Verificar existencia de rol por nombre
     *
     * @param string $name
     * @param int|null $excludeId
     * @return bool
     */
    public function existsByName(string $name, ?int $excludeId = null): bool
    {
        $query = $this->model->where('name', $name);
        if ($excludeId) $query->where('id', '!=', $excludeId);
        return $query->exists();
    }

    /**
     * Crear un nuevo rol
     *
     * @param string $name
     * @param string|null $prefix
     * @param string|null $description
     * @param bool $status
     * @return Role
     */
    public function createRole(string $name, ?string $prefix = null, ?string $description = null, bool $status = true): Role
    {
        return $this->model->create([
            'name' => $this->normalizeName($name),
            'prefix' => $prefix ? strtoupper(trim($prefix)) : null,
            'description' => $description ? trim($description) : null,
            'status' => $status,
        ]);
    }

    /**
     * Actualizar un rol
     *
     * @param int $roleId
     * @param array $data
     * @return bool
     */
    public function updateRole(int $roleId, array $data): bool
    {
        if (isset($data['name'])) $data['name'] = $this->normalizeName($data['name']);
        if (isset($data['prefix'])) $data['prefix'] = strtoupper(trim($data['prefix']));
        if (isset($data['description'])) $data['description'] = trim($data['description']);
        return $this->model->where('id', $roleId)->update($data) > 0;
    }

    /**
     * Actualizar estado de un rol
     *
     * @param int $roleId
     * @param bool $status
     * @return bool
     */
    public function updateStatus(int $roleId, bool $status): bool
    {
        return $this->model->where('id', $roleId)->update(['status' => $status]) > 0;
    }

    public function activateRole(int $roleId): bool
    {
        return $this->updateStatus($roleId, true);
    }
    public function deactivateRole(int $roleId): bool
    {
        return $this->updateStatus($roleId, false);
    }

    /**
     * Eliminar rol
     *
     * @param int $roleId
     * @return bool
     */
    public function deleteRole(int $roleId): bool
    {
        return $this->model->where('id', $roleId)->delete() > 0;
    }

    /**
     * Actualizar estado de múltiples roles
     *
     * @param array $roleIds
     * @param bool $status
     * @return int Cantidad de registros afectados
     */
    public function bulkUpdateStatus(array $roleIds, bool $status): int
    {
        return $this->model->whereIn('id', $roleIds)->update(['status' => $status]);
    }

    /**
     * Obtener estadísticas de roles
     *
     * @return array
     */
    public function getRoleStatistics(): array
    {
        $total = $this->model->count();
        $active = $this->model->where('status', true)->count();
        $inactive = $total - $active;
        $withPrefix = $this->model->whereNotNull('prefix')->count();
        $withDescription = $this->model->whereNotNull('description')->count();
        $averageNameLength = $this->model->avg(DB::raw('LENGTH(name)'));

        return [
            'total_roles' => $total,
            'active_roles' => $active,
            'inactive_roles' => $inactive,
            'active_percentage' => $total > 0 ? round(($active / $total) * 100, 2) : 0,
            'with_prefix' => $withPrefix,
            'with_description' => $withDescription,
            'prefix_percentage' => $total > 0 ? round(($withPrefix / $total) * 100, 2) : 0,
            'description_percentage' => $total > 0 ? round(($withDescription / $total) * 100, 2) : 0,
            'average_name_length' => round($averageNameLength ?? 0, 2),
        ];
    }

    /**
     * Obtener roles ordenados por nombre
     *
     * @param bool $activeOnly
     * @return Collection<Role>
     */
    public function getOrderedByName(bool $activeOnly = false): Collection
    {
        $query = $this->model->orderBy('name');
        if ($activeOnly) $query->where('status', true);
        return $query->get();
    }

    /**
     * Obtener roles más usados por cantidad de usuarios
     *
     * @param int $limit
     * @return Collection<Role>
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
     * Roles sin usuarios asignados
     *
     * @return Collection<Role>
     */
    public function getUnusedRoles(): Collection
    {
        return $this->model->select('roles.*')
            ->leftJoin('role_user', 'roles.id', '=', 'role_user.role_id')
            ->whereNull('role_user.role_id')
            ->get();
    }

    /**
     * Verificar si un rol está en uso
     *
     * @param int $roleId
     * @return bool
     */
    public function isRoleInUse(int $roleId): bool
    {
        return DB::table('role_user')->where('role_id', $roleId)->exists();
    }

    /**
     * Contar usuarios por rol
     *
     * @param int $roleId
     * @return int
     */
    public function getUserCountByRole(int $roleId): int
    {
        return DB::table('role_user')->where('role_id', $roleId)->count();
    }

    /**
     * Normalizar nombre de rol (título, espacios únicos)
     *
     * @param string $name
     * @return string
     */
    public function normalizeName(string $name): string
    {
        $normalized = trim($name);
        $normalized = Str::title($normalized);
        return preg_replace('/\s+/', ' ', $normalized);
    }

    /**
     * Validar nombre de rol
     *
     * @param string $name
     * @return bool
     */
    public function isValidName(string $name): bool
    {
        $normalized = $this->normalizeName($name);
        return strlen($normalized) >= 2 &&
            strlen($normalized) <= 50 &&
            preg_match('/^[\p{L}\p{N}\s\-_]+$/u', $normalized);
    }

    /**
     * Obtener sugerencias de nombres de rol
     *
     * @param string $partialText
     * @param int $limit
     * @return Collection<Role>
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
     * Roles por tipo de usuario (prefix)
     *
     * @param string $userType
     * @return Collection<Role>
     */
    public function getRolesByUserType(string $userType): Collection
    {
        return $this->model->where('prefix', 'LIKE', "%{$userType}%")
            ->where('status', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Verificar si un rol puede ser eliminado
     *
     * @param int $roleId
     * @return bool
     */
    public function canBeDeleted(int $roleId): bool
    {
        return !$this->isRoleInUse($roleId);
    }

    /**
     * Obtener roles similares por prefijo
     *
     * @param string $prefix
     * @param int|null $excludeId
     * @return Collection<Role>
     */
    public function getSimilarRoles(string $prefix, ?int $excludeId = null): Collection
    {
        $query = $this->model->where('prefix', $prefix);
        if ($excludeId) $query->where('id', '!=', $excludeId);
        return $query->orderBy('name')->get();
    }

    /**
     * Crear múltiples roles de forma masiva
     *
     * @param array $rolesData
     * @param bool $status
     * @return Collection<Role>
     */
    public function createBulkRoles(array $rolesData, bool $status = true): Collection
    {
        return collect($rolesData)->reduce(function ($collection, $roleData) use ($status) {
            $name = $roleData['name'] ?? '';
            $prefix = $roleData['prefix'] ?? null;
            $description = $roleData['description'] ?? null;

            if (!$this->existsByName($name)) {
                $collection->push($this->createRole($name, $prefix, $description, $status));
            }
            return $collection;
        }, collect());
    }

    /**
     * Obtener roles con estadísticas de uso
     *
     * @param bool $activeOnly
     * @return Collection<Role>
     */
    public function getRolesWithUsageStats(bool $activeOnly = true): Collection
    {
        $query = $this->model->select('roles.*')
            ->selectRaw('COUNT(role_user.user_id) as users_count')
            ->leftJoin('role_user', 'roles.id', '=', 'role_user.role_id')
            ->groupBy('roles.id', 'roles.name', 'roles.prefix', 'roles.description', 'roles.status', 'roles.created_at', 'roles.updated_at');

        if ($activeOnly) {
            $query->where('roles.status', true);
        }

        return $query->orderBy('roles.name')->get();
    }

    /**
     * Obtener roles para selects (id => name)
     *
     * @param bool $activeOnly
     * @return array<int, string>
     */
    public function getForSelect(bool $activeOnly = true): array
    {
        $query = $this->model->select('id', 'name');

        if ($activeOnly) {
            $query->where('status', true);
        }

        return $query->orderBy('name')->pluck('name', 'id')->toArray();
    }
}
