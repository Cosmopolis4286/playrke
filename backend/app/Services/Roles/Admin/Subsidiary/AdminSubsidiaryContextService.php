<?php

namespace App\Services\Roles\Admin\Subsidiary;

use App\DTOs\Roles\Admin\Subsidiary\AdminSubsidiaryContextDTO;
use App\Services\Roles\RoleUserService;
use App\Services\Security\UserContextService;
use App\Traits\HashedTableDataTrait;
use Illuminate\Pagination\LengthAwarePaginator;

final class AdminSubsidiaryContextService
{
    use HashedTableDataTrait;

    public function __construct(
        private readonly \App\Contracts\Roles\Subsidiary\SubsidiaryRepositoryInterface $subsidiaries,
        private readonly \App\Contracts\Roles\Commerce\CommerceRepositoryInterface $commerces,
        private readonly \App\Contracts\CountryRepositoryInterface $countries,
        private readonly \App\Contracts\CurrencyRepositoryInterface $currencies,
        private readonly RoleUserService $roleUserService,
        private readonly UserContextService $userContext,
    ) {}

    /**
     * Resuelve el rol activo y el user_id del actor autenticado —
     * superadmin se normaliza a 'admin' (mismo criterio que
     * ManagerSellerContextService::resolve()): ambos ven el árbol completo
     * sin restricción.
     *
     * @return array{0: string, 1: int}
     */
    private function resolveActorRole(?int $actorUserId): array
    {
        $actorUserId ??= $this->userContext->resolveUserId();
        $role = $this->roleUserService->getActiveUserRolePrefix($actorUserId);
        if ($role === 'superadmin') {
            $role = 'admin';
        }
        return [$role, $actorUserId];
    }

    /**
     * Resolver contexto para sucursales.
     *
     * Sin $actorUserId explícito, resuelve el usuario autenticado (mismo
     * patrón que ManagerSellerContextService::resolve()) — permite que
     * ContextResolverService siga llamando subsidiaryContext() sin
     * argumentos, igual que ya hace con sellerContextService->resolve().
     *
     * El rol/actor acota tanto el listado como el catálogo de comercios del
     * formulario — un commerce solo ve/gestiona SUS PROPIAS subsidiaries, y
     * su select "Comercio" solo ofrece su propio registro (sin esto, el
     * propio formulario permitiría asignar la subsidiary a un comercio ajeno).
     *
     * @return AdminSubsidiaryContextDTO
     */
    public function subsidiaryContext(?int $actorUserId = null): AdminSubsidiaryContextDTO
    {
        [$role, $actorUserId] = $this->resolveActorRole($actorUserId);

        $subsidiaryData = $this->resolveSubsidiaries($role, $actorUserId);

        // Admin ve TODOS los comercios, sin filtrar por status — antes solo
        // traía los ACTIVE, así que editar una subsidiary cuyo commerce_id
        // apuntaba a un comercio ya INACTIVE/BLOCKED dejaba el select
        // "Comercio" sin esa opción disponible (el FormSelect no puede
        // mostrar un value que no está en su lista de options), viéndose
        // vacío pese a que la sucursal sí tenía un comercio asignado.
        $commerces = $role === 'commerce' && $actorUserId
            ? collect([$this->commerces->getByUserId($actorUserId, ['id', 'name'])])->filter()->values()
            : $this->commerces->all(['id', 'name', 'status']);

        return new AdminSubsidiaryContextDTO(
            subsidiaries: $subsidiaryData['subsidiaries'],
            pagination: $subsidiaryData['pagination'],
            commerces: $commerces,
            countries: $this->countries->getActive(['id', 'name', 'iso_code_2']),
            currencies: $this->currencies->getActive(['id', 'iso_code', 'symbol', 'name', 'country_id']),
        );
    }

    /**
     * Condición de filtro para un usuario 'commerce' — sus subsidiaries se
     * filtran por commerce_id, no por user_id directo. Si por alguna
     * inconsistencia de datos no se encuentra el Commerce propio, NO se debe
     * caer a ['commerce_id' => null]: Eloquent lo traduce a
     * `WHERE commerce_id IS NULL`, que expondría TODAS las subsidiaries
     * huérfanas del sistema en vez de una lista vacía — un id inexistente
     * fuerza cero resultados de forma segura. Mismo patrón que
     * ManagerSellerContextService::subsidiaryScopeCondition().
     */
    private function commerceScopeCondition(int $actorUserId): array
    {
        $commerceId = $this->commerces->getByUserId($actorUserId, ['id'])?->id;

        return ['commerce_id' => $commerceId ?? '0'];
    }

    /**
     * Resolver listado de sucursales.
     *
     * @return array [subsidiaries, pagination]
     */
    public function resolveSubsidiaries(string $role = 'admin', ?int $actorUserId = null): array
    {
        $labels  = ['Nombre', 'Comercio', 'País', 'Teléfono', 'Email', 'Status'];
        $rowKeys = ['name', 'commerce.name', 'country.name', 'phone', 'user.email', 'status_badge'];

        $conditions = match (true) {
            $role === 'admin' => [],
            $role === 'commerce' && $actorUserId => $this->commerceScopeCondition($actorUserId),
            default => ['id' => '0'],
        };

        $source = $this->subsidiaries->paginateWithRelations(
            perPage: 15,
            page: 1,
            relations: ['commerce:id,name', 'country:id,name', 'user:id,email'],
            conditions: $conditions,
            fields: ['id', 'commerce_id', 'country_id', 'user_id', 'name', 'phone', 'status']
        );

        $collection = $source instanceof LengthAwarePaginator
            ? $source->getCollection()
            : collect($source);

        $pagination = $source instanceof LengthAwarePaginator
            ? [
                'total'        => $source->total(),
                'per_page'     => $source->perPage(),
                'current_page' => $source->currentPage(),
                'last_page'    => $source->lastPage(),
                'from'         => $source->firstItem(),
                'to'           => $source->lastItem(),
            ]
            : null;

        $subsidiaries = $this->processCollectionTableData($collection, $labels, $rowKeys);

        // Campos meta (no son columnas visibles) — solo admin/superadmin los
        // usan. user_id: botón "Ver perfil" en el listado (Roles/Admin/
        // Subsidiary/Index.vue), que navega a /subsidiary/profile con
        // target_user_id=user_id de la agencia seleccionada. row.id es el id
        // hasheado de la propia Subsidiary, no del User. user_hashed_id:
        // botón "Impersonar" (admin/impersonation/{player}/start espera el
        // hash de Hashids del User, ver HasHashedRouteKey — no el Snowflake
        // plano).
        $subsidiariesList = $collection->values();
        $subsidiaries['rows'] = collect($subsidiaries['rows'])->map(function (array $row, int $index) use ($subsidiariesList) {
            $rowUser = $subsidiariesList->get($index)?->user;
            $row['user_id'] = $rowUser ? (string) $rowUser->id : null;
            $row['user_hashed_id'] = $rowUser?->getHashedIdAttribute();
            return $row;
        })->all();

        return ['subsidiaries' => $subsidiaries, 'pagination' => $pagination];
    }
}
