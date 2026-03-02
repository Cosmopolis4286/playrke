<?php

namespace App\Services\Roles\Seller\Manager;

use App\Contracts\CountryRepositoryInterface;
use App\Contracts\CurrencyRepositoryInterface;
use App\Contracts\GameRepositoryInterface;
use App\Contracts\Roles\Seller\SellerRepositoryInterface;
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
        private readonly UserContextService $userContext
    ) {}

    /**
     * Resolver listado de vendedores del manager.
     *
     * @param int|null $managerUserId
     * @param string   $role
     * @return array{sellers: array, pagination: ?array}
     */
    private function resolveSellers(?int $managerUserId = null, string $role): array
    {
        $isAdmin = $role === 'admin';

        $labels = $isAdmin
            ? ['Manejador', 'Vendedor', 'País', 'Teléfono', 'Email', 'Status']
            : ['Nombre', 'País', 'Teléfono', 'Email', 'Status'];

        $rowKeys = $isAdmin
            ? ['manager_role', 'name', 'country.name', 'phone', 'User.email', 'status_badge']
            : ['name', 'country.name', 'phone', 'User.email', 'status_badge'];

        $conditions = $isAdmin && $managerUserId
            ? ['manager_user_id' => $managerUserId]
            : [];

        $source = $this->sellerRepository->paginateWithRelations(
            perPage: 15,
            page: 1,
            relations: ['country:id,name', 'user:id,email'],
            conditions: $conditions,
            fields: ['id', 'country_id', 'user_id', 'name', 'phone', 'status']
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

        // Resolver rol del usuario manager
        $role = $this->roleUserService->getActiveUserRolePrefix($managerUserId);

        $result = $this->resolveSellers($managerUserId, $role);

        return new ManagerSellerContextDTO(
            sellers: $result['sellers'],
            pagination: $result['pagination'],
            countries: $this->countries->getActive(['id', 'name', 'iso_code_2']),
            currencies: $this->currencies->getActive(['id', 'iso_code', 'symbol', 'name', 'country_id']),
            games: $this->games->getActive(['id', 'name']),
            timeZones: $this->timeZones->getActive(['id', 'name', 'utc', 'country_id'])
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
        return $this->resolveSellers($managerUserId, $role)['sellers']['rows'] ?? [];
    }
}
