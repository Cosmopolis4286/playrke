<?php

namespace App\Repositories\Roles;

use App\Models\User;
use App\Repositories\BaseRepository;
use App\Contracts\Roles\RoleUserRepositoryInterface;
use App\Models\Pivots\Roles\RoleUser;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Repositorio para la gestión de relaciones usuario-rol.
 *
 * Implementa las operaciones específicas para el modelo RoleUser,
 * incluyendo gestión de asignaciones, consultas por estado de activación,
 * fechas de expiración y operaciones masivas.
 */
class RoleUserRepository extends BaseRepository implements RoleUserRepositoryInterface
{
    /**
     * Constructor del repositorio.
     *
     * @param RoleUser $model Instancia del modelo RoleUser
     */
    public function __construct(RoleUser $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritDoc}
     */
    public function getByUser(string $userId): Collection
    {
        return $this->model->where('user_id', $userId)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByRole(string $roleId): Collection
    {
        return $this->model->where('role_id', $roleId)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function userHasRolePrefix(string $userId, string $prefix): bool
    {
        return $this->model
            ->where('user_id', $userId)
            ->whereHas('role', function ($query) use ($prefix) {
                // prefix se persiste siempre en mayúsculas (Role::setPrefixAttribute)
                $query->where('prefix', strtoupper($prefix));
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', Carbon::now());
            })
            ->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function getActiveByUser(string $userId): Collection
    {
        return $this->model->with('role')
            ->where('user_id', $userId)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', Carbon::now());
            })->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getExpiredByUser(string $userId): Collection
    {
        return $this->model->where('user_id', $userId)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', Carbon::now())
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getAllActive(): Collection
    {
        return $this->model->where(function ($query) {
            $query->whereNull('expires_at')
                ->orWhere('expires_at', '>', Carbon::now());
        })->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getAllExpired(): Collection
    {
        return $this->model->whereNotNull('expires_at')
            ->where('expires_at', '<=', Carbon::now())
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function hasActiveRole(string $userId, string $roleId): bool
    {
        return $this->model->where('user_id', $userId)
            ->where('role_id', $roleId)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', Carbon::now());
            })->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function userHasAnyRole(User $user, array $roles): bool
    {
        $rolesLower = array_map('strtolower', $roles);
        return $this->getActiveByUser($user->id)
            ->loadMissing('role')
            ->contains(function ($assignment) use ($rolesLower) {
                return $assignment->role
                    && in_array(strtolower((string) $assignment->role->prefix), $rolesLower, true);
            });
    }

    /**
     * {@inheritDoc}
     */
    public function userHasRole(User $user, string $role): bool
    {
        return $this->userHasAnyRole($user, [$role]);
    }

    /**
     * {@inheritDoc}
     */
    public function getUserRole(string $userId, string $roleId): mixed
    {
        return $this->model->where('user_id', $userId)
            ->where('role_id', $roleId)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function assignRole(string $userId, string $roleId, ?string $assignedBy = null, ?string $expiresAt = null): mixed
    {
        // Validar datos antes de asignar
        $errors = $this->validateAssignmentData([
            'user_id' => $userId,
            'role_id' => $roleId,
            'assigned_by' => $assignedBy,
            'expires_at' => $expiresAt
        ]);

        if (!empty($errors)) {
            throw new \InvalidArgumentException('Datos de asignación inválidos: ' . json_encode($errors));
        }

        return $this->model->updateOrCreate(
            [
                'user_id' => $userId,
                'role_id' => $roleId
            ],
            [
                'assigned_by' => $assignedBy,
                'expires_at' => $expiresAt ? Carbon::parse($expiresAt) : null
            ]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function removeRole(string $userId, string $roleId): bool
    {
        return $this->model->where('user_id', $userId)
            ->where('role_id', $roleId)
            ->delete() > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function updateExpiration(string $userId, string $roleId, ?string $expiresAt): bool
    {
        return $this->model->where('user_id', $userId)
            ->where('role_id', $roleId)
            ->update(['expires_at' => $expiresAt ? Carbon::parse($expiresAt) : null]) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function extendExpiration(string $userId, string $roleId, int $days): bool
    {
        $assignment = $this->getUserRole($userId, $roleId);

        if (!$assignment) {
            return false;
        }

        $newExpiration = $assignment->expires_at
            ? Carbon::parse($assignment->expires_at)->addDays($days)
            : Carbon::now()->addDays($days);

        return $this->updateExpiration($userId, $roleId, $newExpiration->toDateTimeString());
    }

    /**
     * {@inheritDoc}
     */
    public function makePermanent(string $userId, string $roleId): bool
    {
        return $this->updateExpiration($userId, $roleId, null);
    }

    /**
     * {@inheritDoc}
     */
    public function getExpiringInRange(string $startDate, string $endDate): Collection
    {
        return $this->model->whereNotNull('expires_at')
            ->whereBetween('expires_at', [
                Carbon::parse($startDate),
                Carbon::parse($endDate)
            ])->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getExpiringSoon(int $days = 7): Collection
    {
        $endDate = Carbon::now()->addDays($days);

        return $this->model->whereNotNull('expires_at')
            ->whereBetween('expires_at', [Carbon::now(), $endDate])
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getAssignedBy(string $assignedBy): Collection
    {
        return $this->model->where('assigned_by', $assignedBy)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function removeAllUserRoles(string $userId): int
    {
        return $this->model->where('user_id', $userId)->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function removeRoleFromAllUsers(string $roleId): int
    {
        return $this->model->where('role_id', $roleId)->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function assignMultipleRoles(string $userId, array $roleIds, ?string $assignedBy = null, ?string $expiresAt = null): Collection
    {
        $assignments = collect();

        foreach ($roleIds as $roleId) {
            $assignment = $this->assignRole($userId, $roleId, $assignedBy, $expiresAt);
            $assignments->push($assignment);
        }

        return $assignments;
    }

    /**
     * {@inheritDoc}
     */
    public function assignRoleToMultipleUsers(array $userIds, string $roleId, ?string $assignedBy = null, ?string $expiresAt = null): Collection
    {
        $assignments = collect();

        foreach ($userIds as $userId) {
            $assignment = $this->assignRole($userId, $roleId, $assignedBy, $expiresAt);
            $assignments->push($assignment);
        }

        return $assignments;
    }

    /**
     * {@inheritDoc}
     */
    public function getRoleAssignmentStats(): array
    {
        $total = $this->model->count();
        $active = $this->getAllActive()->count();
        $expired = $this->getAllExpired()->count();
        $permanent = $this->model->whereNull('expires_at')->count();
        $temporary = $this->model->whereNotNull('expires_at')->count();

        return [
            'total_assignments' => $total,
            'active_assignments' => $active,
            'expired_assignments' => $expired,
            'permanent_assignments' => $permanent,
            'temporary_assignments' => $temporary,
            'active_percentage' => $total > 0 ? round(($active / $total) * 100, 2) : 0,
            'permanent_percentage' => $total > 0 ? round(($permanent / $total) * 100, 2) : 0
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getRoleStats(string $roleId): array
    {
        $assignments = $this->getByRole($roleId);
        $active = $assignments->filter(function ($assignment) {
            return is_null($assignment->expires_at) || Carbon::parse($assignment->expires_at)->isFuture();
        });

        return [
            'total_users' => $assignments->count(),
            'active_users' => $active->count(),
            'expired_users' => $assignments->count() - $active->count(),
            'permanent_users' => $assignments->whereNull('expires_at')->count(),
            'active_percentage' => $assignments->count() > 0 ? round(($active->count() / $assignments->count()) * 100, 2) : 0
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getUserStats(string $userId): array
    {
        $assignments = $this->getByUser($userId);
        $active = $assignments->filter(function ($assignment) {
            return is_null($assignment->expires_at) || Carbon::parse($assignment->expires_at)->isFuture();
        });

        return [
            'total_roles' => $assignments->count(),
            'active_roles' => $active->count(),
            'expired_roles' => $assignments->count() - $active->count(),
            'permanent_roles' => $assignments->whereNull('expires_at')->count(),
            'active_percentage' => $assignments->count() > 0 ? round(($active->count() / $assignments->count()) * 100, 2) : 0
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function validateAssignmentData(array $data, ?string $excludeId = null): array
    {
        $errors = [];

        // Validar campos requeridos
        if (!isset($data['user_id']) || empty($data['user_id'])) {
            $errors['user_id'] = 'El ID del usuario es requerido';
        }

        if (!isset($data['role_id']) || empty($data['role_id'])) {
            $errors['role_id'] = 'El ID del rol es requerido';
        }

        // Validar fecha de expiración si está presente
        if (isset($data['expires_at']) && $data['expires_at']) {
            try {
                $expirationDate = Carbon::parse($data['expires_at']);
                if ($expirationDate->isPast()) {
                    $errors['expires_at'] = 'La fecha de expiración no puede ser en el pasado';
                }
            } catch (\Exception $e) {
                $errors['expires_at'] = 'Formato de fecha de expiración inválido';
            }
        }

        return $errors;
    }

    /**
     * {@inheritDoc}
     */
    public function cleanExpiredAssignments(bool $dryRun = false): int
    {
        $expiredQuery = $this->model->whereNotNull('expires_at')
            ->where('expires_at', '<=', Carbon::now());

        if ($dryRun) {
            return $expiredQuery->count();
        }

        return $expiredQuery->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function getUserUniqueRoles(string $userId): Collection
    {
        return $this->model->with('role')
            ->where('user_id', $userId)
            ->get()
            ->pluck('role')
            ->unique('id');
    }

    /**
     * {@inheritDoc}
     */
    public function getRoleUniqueUsers(string $roleId): Collection
    {
        return $this->model->with('user')
            ->where('role_id', $roleId)
            ->get()
            ->pluck('user')
            ->unique('id');
    }

    /**
     * {@inheritDoc}
     */
    public function isAssignmentActive(string $assignmentId): bool
    {
        $assignment = $this->model->find($assignmentId);

        if (!$assignment) {
            return false;
        }

        return is_null($assignment->expires_at) || Carbon::parse($assignment->expires_at)->isFuture();
    }

    /**
     * {@inheritDoc}
     */
    public function getRoleDistribution(): array
    {
        return $this->model->select('roles.name', DB::raw('COUNT(role_user.id) as assignment_count'))
            ->leftJoin('roles', 'role_user.role_id', '=', 'roles.id')
            ->groupBy('roles.id', 'roles.name')
            ->pluck('assignment_count', 'roles.name')
            ->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function syncUserRoles(string $userId, array $roleIds, ?string $assignedBy = null): array
    {
        $currentRoles = $this->getByUser($userId)->pluck('role_id')->toArray();

        $toAdd = array_diff($roleIds, $currentRoles);
        $toRemove = array_diff($currentRoles, $roleIds);

        $added = [];
        $removed = [];

        // Agregar nuevos roles
        foreach ($toAdd as $roleId) {
            $assignment = $this->assignRole($userId, $roleId, $assignedBy);
            $added[] = $assignment;
        }

        // Remover roles que ya no están en la lista
        foreach ($toRemove as $roleId) {
            if ($this->removeRole($userId, $roleId)) {
                $removed[] = $roleId;
            }
        }

        return [
            'added' => $added,
            'removed' => $removed,
            'added_count' => count($added),
            'removed_count' => count($removed)
        ];
    }
}
