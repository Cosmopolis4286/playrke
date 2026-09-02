<?php

namespace App\Services\Roles\Seller\Manager;

use App\Contracts\CountryRepositoryInterface;
use App\Contracts\CurrencyRepositoryInterface;
use App\Contracts\GameRepositoryInterface;
use App\Contracts\Roles\Seller\SellerRepositoryInterface;
use App\Contracts\Roles\Subsidiary\SubsidiaryRepositoryInterface;
use App\Contracts\TimeZoneRepositoryInterface;
use App\DTOs\Roles\Seller\Manager\ManagerSellerContextDTO;
use App\Services\Roles\RoleUserService;
use App\Services\Security\UserContextService;
use App\Traits\HashedTableDataTrait;
use Illuminate\Pagination\LengthAwarePaginator;

class ManagerSellerContextService
{
    use HashedTableDataTrait;

    public function __construct(
        private readonly CountryRepositoryInterface $countries,
        private readonly CurrencyRepositoryInterface $currencies,
        private readonly GameRepositoryInterface $games,
        private readonly SellerRepositoryInterface $sellerRepository,
        private readonly TimeZoneRepositoryInterface $timeZones,
        private readonly RoleUserService $roleUserService,
        private readonly UserContextService $userContext,
        private readonly SubsidiaryRepositoryInterface $subsidiaryRepository,
    ) {}

    /**
     * Condición de filtro para un usuario 'subsidiary' — sus sellers se
     * filtran por subsidiary_id, no por manager_user_id (ver
     * resolveSellers()). Si por alguna inconsistencia de datos no se
     * encuentra la Subsidiary propia, NO se debe caer a
     * ['subsidiary_id' => null]: Eloquent traduce eso a
     * `WHERE subsidiary_id IS NULL`, que expondría TODOS los sellers sin
     * agencia (reportando directo a cualquier Commerce) en vez de una lista
     * vacía — un id inexistente fuerza cero resultados de forma segura.
     */
    private function subsidiaryScopeCondition(int $managerUserId): array
    {
        $subsidiaryId = $this->subsidiaryRepository->getByUserId($managerUserId, ['id'])?->id;

        return ['subsidiary_id' => $subsidiaryId ?? '0'];
    }

    /**
     * Resolver listado de vendedores del manager.
     *
     * @param string   $role
     * @param int|null $managerUserId
     * @return array{sellers: array, pagination: ?array}
     */
    private function resolveSellers(string $role, ?int $managerUserId = null): array
    {
        $isAdmin = $role === 'admin';

        $labels = $isAdmin
            ? ['Rol', 'Manejador', 'Vendedor', 'País', 'Teléfono', 'Email', 'Status']
            : ['Nombre', 'País', 'Teléfono', 'Email', 'Status'];

        $rowKeys = $isAdmin
            ? ['manager_role_badge', 'manager_name', 'name', 'country.name', 'phone', 'User.email', 'status_badge']
            : ['name', 'country.name', 'phone', 'User.email', 'status_badge'];

        // Admin ve todos los sellers; commerce/customer solo los suyos.
        // 'subsidiary' es un caso aparte: ManagerSellerService::
        // resolveManagerForUser() siempre normaliza manager_user_id al
        // user_id del Commerce raíz (nunca al de la Subsidiary), así que un
        // usuario 'subsidiary' filtrando por manager_user_id=su propio id
        // nunca encontraba nada — sus sellers reales tienen subsidiary_id =
        // su propio id, no manager_user_id.
        $conditions = match (true) {
            $isAdmin => [],
            $role === 'subsidiary' && $managerUserId => $this->subsidiaryScopeCondition($managerUserId),
            (bool) $managerUserId => ['manager_user_id' => $managerUserId],
            default => [],
        };

        $relations = $isAdmin
            ? ['country:id,name', 'user:id,email', 'managerCommerce:id,user_id,name', 'subsidiary:id,name']
            : ['country:id,name', 'user:id,email'];

        $fields = $isAdmin
            ? ['id', 'country_id', 'user_id', 'manager_user_id', 'subsidiary_id', 'name', 'phone', 'status']
            : ['id', 'country_id', 'user_id', 'name', 'phone', 'status'];

        $source = $this->sellerRepository->paginateWithRelations(
            perPage: 15,
            page: 1,
            relations: $relations,
            conditions: $conditions,
            fields: $fields
        );

        $isPaginator = $source instanceof LengthAwarePaginator;

        $collection = $isPaginator
            ? $source->getCollection()
            : collect($source);

        $pagination = $isPaginator ? [
            'total'        => $source->total(),
            'per_page'     => $source->perPage(),
            'current_page' => $source->currentPage(),
            'last_page'    => $source->lastPage(),
            'from'         => $source->firstItem(),
            'to'           => $source->lastItem(),
        ] : null;

        return [
            'sellers'    => $this->processCollectionTableData($collection, $labels, $rowKeys),
            'pagination' => $pagination,
        ];
    }

    /**
     * Obtener contexto de vendedores del usuario manager.
     *
     * @param int|null $managerUserId ID del usuario manager
     * @return ManagerSellerContextDTO Contexto de vendedores para la vista Inertia
     */
    public function resolve(?int $managerUserId = null): ManagerSellerContextDTO
    {
        // Resolver ID del usuario Manager si no corresponde
        if (!$managerUserId) $managerUserId = $this->userContext->resolveUserId();

        // Resolver rol del usuario manager. 'superadmin' se normaliza a
        // 'admin' aquí mismo (único punto de entrada de este contexto) — para
        // toda esta pantalla ambos deben comportarse igual (ven TODOS los
        // sellers del sistema, con columnas de jerarquía y el select de
        // reasignación completo). Sin esto, un superadmin real recibía
        // roleUser: 'superadmin', que no calzaba con ningún chequeo
        // === 'admin' (backend o frontend), y terminaba viendo la vista
        // restringida de un commerce/subsidiary sin serlo — sin manager
        // select, sin columnas de jerarquía, sin la sección de resetPassword
        // habilitada. RoleAuthorizationService::isSuperadmin() ya trata a
        // superadmin como con acceso total en toda la app; este es el mismo
        // criterio aplicado aquí.
        $role = $this->roleUserService->getActiveUserRolePrefix($managerUserId);
        if ($role === 'superadmin') {
            $role = 'admin';
        }

        $result = $this->resolveSellers($role, $managerUserId);

        return new ManagerSellerContextDTO(
            sellers: $result['sellers'],
            pagination: $result['pagination'],
            countries: $this->countries->getActive(['id', 'name', 'iso_code_2']),
            currencies: $this->currencies->getActive(['id', 'iso_code', 'symbol', 'name', 'country_id']),
            games: $this->games->getActive(['id', 'name']),
            timeZones: $this->timeZones->getActive(['id', 'name', 'utc', 'country_id']),
            roleUser: $role
        );
    }

    /**
     * Obtener todos los vendedores.
     *
     * @param int|null $managerUserId ID del usuario manager
     * @return array
     */
    public function getAll(int $managerUserId): array
    {
        // Resolver rol del usuario manager
        $role = $this->roleUserService->getActiveUserRolePrefix($managerUserId);
        return $this->resolveSellers($role, $managerUserId)['sellers']['rows'] ?? [];
    }
}
