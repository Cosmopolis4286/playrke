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
    ) {}

    public function administratorContext(): AdministratorContextDTO
    {
        $data = $this->resolveAdministrators();

        return new AdministratorContextDTO(
            administrators: $data['administrators'],
            pagination:     $data['pagination'],
            countries:      $this->countries->getActive(['id', 'name', 'iso_code_2']),
            roles:          Role::whereIn('name', ['admin', 'superadmin'])->orderBy('name')->get(['id', 'name', 'prefix']),
        );
    }

    public function resolveAdministrators(): array
    {
        $labels  = ['Rol', 'Nombre', 'Username', 'Email', 'País', 'Status'];
        $rowKeys = ['role_badge', 'full_name', 'username', 'email', 'country_name', 'status_badge'];

        $source = User::with([
                    'country:id,name',
                    'roles:id,name,prefix',
                ])
                ->whereHas('roles', function ($query) {
                    $query->whereIn('name', ['admin', 'superadmin']);
                })
                ->where('status', '!=', 'DELETED')
                ->paginate(15);

        $collection = $source->getCollection()->map(function (User $user) {
            $fullName = trim("{$user->first_name} {$user->last_name}");
            $user->setAttribute('full_name',    $fullName);
            $user->setAttribute('role_badge',   $this->administratorRoleBadge($user->roles->first()?->name ?? ''));
            $user->setAttribute('country_name', $user->country?->name ?? '—');
            $user->setAttribute('status_badge', $this->statusBadge($user->status ?? ''));
            return $user;
        });

        $pagination = [
            'total'        => $source->total(),
            'per_page'     => $source->perPage(),
            'current_page' => $source->currentPage(),
            'last_page'    => $source->lastPage(),
            'from'         => $source->firstItem(),
            'to'           => $source->lastItem(),
        ];

        $administrators = $this->processCollectionTableData($collection, $labels, $rowKeys);

        return ['administrators' => $administrators, 'pagination' => $pagination];
    }

    private function administratorRoleBadge(string $role): string
    {
        return match (strtolower($role)) {
            'superadmin' => '<span class="badge badge-danger">SuperAdmin</span>',
            'admin'     => '<span class="badge badge-warning">Admin</span>',
            default     => '<span class="badge badge-secondary">' . ucfirst($role) . '</span>',
        };
    }

    private function statusBadge(string $status): string
    {
        return match ($status) {
            'ACTIVE'               => '<span class="badge badge-success">Activo</span>',
            'INACTIVE'             => '<span class="badge badge-secondary">Inactivo</span>',
            'DELETED'              => '<span class="badge badge-danger">Eliminado</span>',
            default                 => '<span class="badge badge-dark">' . ucfirst($status) . '</span>',
        };
    }
}
