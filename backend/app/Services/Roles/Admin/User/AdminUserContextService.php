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

    /**
     * Query base con filtros aplicados — compartida entre `resolveUsers()`
     * (paginado, para la tabla) y `exportQuery()` (cursor completo, para CSV).
     */
    public function buildFilteredQuery(array $filters = []): \Illuminate\Database\Eloquent\Builder
    {
        $query = User::with([
                'country:id,name',
                'roles:id,name,prefix',
                // Solo 'id' — necesario para resolver domain_id (botón "Ver
                // más" -> página dedicada del rol), no se expone nada más.
                'commerce:id,user_id',
                'seller:id,user_id',
                'customer:id,user_id',
                'treasurer:id,user_id',
            ])
            ->excludeSystemAccounts();

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('first_name', 'LIKE', "%{$term}%")
                    ->orWhere('last_name', 'LIKE', "%{$term}%")
                    ->orWhere('username', 'LIKE', "%{$term}%")
                    ->orWhere('email', 'LIKE', "%{$term}%");
            });
        }

        if (!empty($filters['role_name'])) {
            $roleName = strtolower($filters['role_name']);
            $query->whereHas('roles', fn ($q) => $q->where('name', $roleName));
        }

        if (!empty($filters['country_name'])) {
            $countryName = $filters['country_name'];
            $query->whereHas('country', fn ($q) => $q->where('name', $countryName));
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['has_specific_permissions']) && $filters['has_specific_permissions'] !== '') {
            $hasPermissions = filter_var($filters['has_specific_permissions'], FILTER_VALIDATE_BOOLEAN);
            $query->when(
                $hasPermissions,
                fn ($q) => $q->whereNotNull('specific_permissions')->whereJsonLength('specific_permissions', '>', 0),
                fn ($q) => $q->whereNull('specific_permissions')->orWhereJsonLength('specific_permissions', 0),
            );
        }

        return $query;
    }

    /**
     * $filters acepta: search (nombre/username/email), role_name, country_name,
     * status, has_specific_permissions ('true'|'false'). Antes se ignoraban
     * por completo — la tabla del frontend "filtraba" solo la página de 15
     * registros ya cargada, dando resultados incompletos/engañosos frente al
     * total real del sistema.
     */
    public function resolveUsers(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        // "Usuario" acopla rol + nombre + username + email en una sola columna
        // (antes 4 columnas separadas: Rol, Nombre, Username, Email) para dejar
        // espacio a Permisos y Registrado sin sobrecargar la tabla — el
        // frontend arma el bloque visual a partir de role_badge/full_name/
        // username/email vía slot #cell-full_name.
        $labels  = ['Usuario', 'País', 'Permisos', 'Registrado', 'Status'];
        $rowKeys = ['full_name', 'country_name', 'permissions_badge', 'registered_at', 'status_badge'];

        $source = $this->buildFilteredQuery($filters)->paginate($perPage, ['*'], 'page', $page);

        $collection = $source->getCollection()->map(function (User $user) {
            $fullName = trim("{$user->first_name} {$user->last_name}");
            $permissionsCount = count($user->specific_permissions ?? []);
            $user->setAttribute('full_name',    $fullName);
            $user->setAttribute('role_badge',   $this->roleBadge($user->roles->first()?->name ?? ''));
            $user->setAttribute('country_name', $user->country?->name ?? '—');
            $user->setAttribute('permissions_badge', $this->permissionsBadge($permissionsCount));
            $user->setAttribute('registered_at', $user->created_at?->toIso8601String());
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

        // Datos crudos adicionales (no son columnas visibles): necesarios para
        // que el frontend decida qué filas/acciones deshabilitar (superadmin,
        // usuario propio) y para precargar el modal de edición.
        // Los rows ya vienen en el mismo orden que $collection (buildRows no
        // reordena), así que se combinan por índice, no por id (el id de la
        // row está hasheado, no es el id real del usuario).
        $usersList = $collection->values();
        $users['rows'] = collect($users['rows'])->map(function (array $row, int $index) use ($usersList) {
            $user = $usersList->get($index);
            $row['role_name']  = strtolower($user?->roles->first()?->name ?? '');
            $row['role_id']    = $user?->roles->first()?->id;
            $row['country_id'] = $user?->country_id;
            $row['first_name'] = $user?->first_name;
            $row['last_name']  = $user?->last_name;
            // role_badge/username/email dejaron de ser columnas independientes
            // (ver labels/rowKeys arriba) pero se necesitan como datos crudos
            // para el slot #cell-full_name (bloque "Usuario" acoplado: rol +
            // nombre + username + email) y la búsqueda.
            $row['role_badge'] = $user?->role_badge;
            $row['username']   = $user?->username;
            $row['email']      = $user?->email;
            $row['phone_number'] = $user?->phone_number;
            $row['has_specific_permissions'] = !empty($user?->specific_permissions);
            $row['specific_permissions_count'] = count($user?->specific_permissions ?? []);
            // ID real del usuario (distinto del 'id' hasheado de la fila) —
            // necesario para deep-linkear al historial de auditoría, donde
            // Audit.auditable_id se guarda con la PK real, no el hash de tabla.
            $row['real_id'] = $user?->id;
            // ID del registro de dominio (Commerce/Seller/Customer/Treasurer)
            // para el botón "Ver más" -> página dedicada del rol
            // (/admin/commerces, etc.), que identifica sus filas por ese id,
            // no por el id del User. 'player' no tiene modelo de dominio
            // propio (ver AdminPlayerContextService, opera sobre User
            // directo), así que ahí domain_id es el mismo real_id.
            $row['domain_id'] = match ($row['role_name']) {
                'commerce'  => $user?->commerce?->id,
                'seller'    => $user?->seller?->id,
                'customer'  => $user?->customer?->id,
                'treasurer' => $user?->treasurer?->id,
                'player'    => $user?->id,
                default     => null,
            };
            return $row;
        })->all();

        return ['users' => $users, 'pagination' => $pagination];
    }

    private function roleBadge(string $role): string
    {
        return match (strtolower($role)) {
            'admin'      => '<span class="badge badge-danger">Admin</span>',
            'commerce'   => '<span class="badge badge-primary">Comercio</span>',
            'seller'     => '<span class="badge badge-info">Vendedor</span>',
            'customer'   => '<span class="badge badge-success">Cliente</span>',
            'treasurer'  => '<span class="badge badge-warning">Tesorero</span>',
            'player'     => '<span class="badge badge-secondary">Jugador</span>',
            'subsidiary' => '<span class="badge badge-light">Sucursal</span>',
            'provider'   => '<span class="badge badge-dark">Proveedor</span>',
            default      => '<span class="badge badge-dark">' . ucfirst($role) . '</span>',
        };
    }

    private function permissionsBadge(int $count): string
    {
        if ($count === 0) {
            return '<span class="badge badge-light text-muted">Sin permisos individuales</span>';
        }

        return '<span class="badge badge-info"><i class="fas fa-user-shield mr-1"></i>' . $count . ' individual' . ($count > 1 ? 'es' : '') . '</span>';
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
