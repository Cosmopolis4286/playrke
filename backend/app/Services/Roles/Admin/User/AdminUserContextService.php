<?php

namespace App\Services\Roles\Admin\User;

use App\DTOs\Roles\Admin\User\AdminUserContextDTO;
use App\Models\Role;
use App\Models\User;
use App\Traits\HashedTableDataTrait;

final class AdminUserContextService
{
    use HashedTableDataTrait;

    public function __construct(
        private readonly \App\Contracts\CountryRepositoryInterface $countries,
    ) {}

    public function userContext(): AdminUserContextDTO
    {
        $data = $this->resolveUsers();

        return new AdminUserContextDTO(
            users:      $data['users'],
            pagination: $data['pagination'],
            countries:  $this->countries->getActive(['id', 'name', 'iso_code_2']),
            roles:      Role::active()->orderBy('name')->get(['id', 'name', 'prefix']),
        );
    }

    public function resolveUsers(): array
    {
        $labels  = ['Nombre', 'Username', 'Email', 'Entidad / Nombre', 'País', 'Status'];
        $rowKeys = ['full_name', 'username', 'email', 'role_name', 'country_name', 'status_badge'];

        $source = User::with([
                'country:id,name',
                'roles:id,name,prefix',
                'commerce:id,user_id,name',
                'seller:id,user_id,name',
                'customer:id,user_id,name',
                'treasurer:id,user_id,name',
            ])
            ->paginate(15);

        $collection = $source->getCollection()->map(function (User $user) {
            $fullName = trim("{$user->first_name} {$user->last_name}");
            $user->setAttribute('full_name',    $fullName);
            $user->setAttribute('role_name',    $this->resolveEntityName($user, $fullName));
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

        $users = $this->processCollectionTableData($collection, $labels, $rowKeys);

        return ['users' => $users, 'pagination' => $pagination];
    }

    private function resolveEntityName(User $user, string $fullName): string
    {
        $role = $user->roles->first()?->name ?? '';

        return match (strtolower($role)) {
            'commerce'  => $user->commerce?->name  ?? $fullName,
            'seller'    => $user->seller?->name     ?? $fullName,
            'customer'  => $user->customer?->name   ?? $fullName,
            'treasurer' => $user->treasurer?->name  ?? $fullName,
            default     => $fullName,
        };
    }

    private function statusBadge(string $status): string
    {
        return match ($status) {
            'ACTIVE'               => '<span class="badge badge-success">Activo</span>',
            'INACTIVE'             => '<span class="badge badge-secondary">Inactivo</span>',
            'DELETED'              => '<span class="badge badge-dark">Eliminado</span>',
            'SUSPENDED'            => '<span class="badge badge-danger">Suspendido</span>',
            'PENDING_VERIFICATION' => '<span class="badge badge-warning">Pendiente</span>',
            default                => '<span class="badge badge-warning">Desconocido</span>',
        };
    }
}
