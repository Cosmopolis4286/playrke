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
        $labels  = ['Rol', 'Nombre', 'Username', 'Email', 'País', 'Status'];
        $rowKeys = ['role_badge', 'full_name', 'username', 'email', 'country_name', 'status_badge'];

        $source = User::with([
                'country:id,name',
                'roles:id,name,prefix',
            ])
            ->excludeSystemAccounts()
            ->paginate(15);

        $collection = $source->getCollection()->map(function (User $user) {
            $fullName = trim("{$user->first_name} {$user->last_name}");
            $user->setAttribute('full_name',    $fullName);
            $user->setAttribute('role_badge',   $this->roleBadge($user->roles->first()?->name ?? ''));
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

    private function roleBadge(string $role): string
    {
        return match (strtolower($role)) {
            'admin'     => '<span class="badge badge-danger">Admin</span>',
            'commerce'  => '<span class="badge badge-primary">Comercio</span>',
            'seller'    => '<span class="badge badge-info">Vendedor</span>',
            'customer'  => '<span class="badge badge-success">Cliente</span>',
            'treasurer' => '<span class="badge badge-warning">Tesorero</span>',
            'player'    => '<span class="badge badge-secondary">Jugador</span>',
            default     => '<span class="badge badge-dark">' . ucfirst($role) . '</span>',
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
