<?php

namespace App\Services\Roles\Admin;

use App\DTOs\Roles\Admin\Administrator\AdministratorContextDTO;
use App\Models\Role;
use App\Models\User;
use App\Traits\HashedTableDataTrait;

final class AdministratorContextService
{
    use HashedTableDataTrait;

    public function __construct(
        private readonly \App\Contracts\CountryRepositoryInterface $countries,
    ) {
    }

    public function administratorContext() : AdministratorContextDTO
    {
        $data = $this->resolveadmins();

        return new AdministratorContextDTO(
            admins: $data['admins'],
            pagination: $data['pagination'],
            countries: $this->countries->getActive(['id', 'name', 'iso_code_2'])->toArray(),
            roles: Role::whereIn('name', ['admin', 'superadmin'])->orderBy('name')->get(['id', 'name', 'prefix'])->toArray(),
        );
    }

    /**
     * Query base con filtros aplicados — misma responsabilidad que
     * AdminUserContextService::buildFilteredQuery(), aplicada al subconjunto
     * admin/superadmin.
     */
    public function buildFilteredQuery(array $filters = []) : \Illuminate\Database\Eloquent\Builder
    {
        $query = User::with(['country:id,name', 'roles:id,name,prefix'])
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'superadmin']))
            ->where('status', '!=', 'DELETED');

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function($q) use ($term) {
                $q->where('first_name', 'LIKE', "%{$term}%")
                    ->orWhere('last_name', 'LIKE', "%{$term}%")
                    ->orWhere('username', 'LIKE', "%{$term}%")
                    ->orWhere('email', 'LIKE', "%{$term}%");
            });
        }

        if (! empty($filters['role_name'])) {
            $roleName = strtolower($filters['role_name']);
            $query->whereHas('roles', fn ($q) => $q->where('name', $roleName));
        }

        if (! empty($filters['country_name'])) {
            $countryName = $filters['country_name'];
            $query->whereHas('country', fn ($q) => $q->where('name', $countryName));
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }

    /**
     * $filters acepta: search (nombre/username/email), role_name, country_name,
     * status — mismo contrato que AdminUserContextService::resolveUsers().
     */
    public function resolveadmins(array $filters = [], int $page = 1, int $perPage = 15) : array
    {
        $labels = ['Usuario', 'País', 'Registrado', 'Última conexión', 'Status'];
        $rowKeys = ['full_name', 'country_name', 'registered_at', 'last_login_at', 'status_badge'];

        $source = $this->buildFilteredQuery($filters)->paginate($perPage, ['*'], 'page', $page);

        $collection = $source->getCollection()->map(function(User $user) {
            $fullName = trim("{$user->first_name} {$user->last_name}");
            $user->setAttribute('full_name', $fullName);
            $user->setAttribute('role_badge', $this->administratorRoleBadge($user->roles->first()?->name ?? ''));
            $user->setAttribute('country_name', $user->country?->name ?? '—');
            $user->setAttribute('registered_at', $user->created_at?->toIso8601String());
            $user->setAttribute('last_login_at', $user->last_login_at?->toIso8601String());
            $user->setAttribute('status_badge', $this->statusBadge($user->status ?? ''));

            return $user;
        });

        $pagination = [
            'total' => $source->total(),
            'per_page' => $source->perPage(),
            'current_page' => $source->currentPage(),
            'last_page' => $source->lastPage(),
            'from' => $source->firstItem(),
            'to' => $source->lastItem(),
        ];

        $admins = $this->processCollectionTableData($collection, $labels, $rowKeys);

        $usersList = $collection->values();
        $admins['rows'] = collect($admins['rows'])->map(function(array $row, int $index) use ($usersList) {
            $user = $usersList->get($index);
            $row['role_name'] = strtolower($user?->roles->first()?->name ?? '');
            $row['role_badge'] = $user?->role_badge;
            $row['username'] = $user?->username;
            $row['email'] = $user?->email;
            $row['phone_number'] = $user?->phone_number;
            $row['country_id'] = $user?->country_id;
            $row['first_name'] = $user?->first_name;
            $row['last_name'] = $user?->last_name;
            // ID real (distinto del 'id' hasheado de la fila) — necesario en
            // el frontend para comparar contra el usuario autenticado y
            // deshabilitar la fila propia (no auto-gestión).
            $row['real_id'] = $user?->id;

            return $row;
        })->all();

        return ['admins' => $admins, 'pagination' => $pagination];
    }

    private function administratorRoleBadge(string $role) : string
    {
        return match (strtolower($role)) {
            'superadmin' => '<span class="badge badge-danger">SuperAdmin</span>',
            'admin' => '<span class="badge badge-warning">Admin</span>',
            default => '<span class="badge badge-secondary">' . ucfirst($role) . '</span>',
        };
    }

    private function statusBadge(string $status) : string
    {
        return match ($status) {
            'ACTIVE' => '<span class="badge badge-success">Activo</span>',
            'INACTIVE' => '<span class="badge badge-secondary">Inactivo</span>',
            'DELETED' => '<span class="badge badge-danger">Eliminado</span>',
            default => '<span class="badge badge-dark">' . ucfirst($status) . '</span>',
        };
    }
}
