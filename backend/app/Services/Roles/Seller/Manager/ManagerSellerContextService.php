<?php

namespace App\Services\Roles\Seller\Manager;

use App\Contracts\CountryRepositoryInterface;
use App\Contracts\CurrencyRepositoryInterface;
use App\Contracts\GameRepositoryInterface;
use App\Contracts\Roles\Seller\SellerRepositoryInterface;
use App\Contracts\TimeZoneRepositoryInterface;
use App\DTOs\Roles\Seller\Manager\ManagerSellerContextDTO;
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
        private readonly TimeZoneRepositoryInterface $timeZones
    ) {}

    /**
     * Resolver listado de vendedores del manager.
     *
     * @param int $userId ID del usuario manager
     * @return array [sellers, pagination]
     */
    private function resolveSellers(int $userId): array
    {
        $labels  = ['Nombre', 'País', 'Teléfono', 'Email', 'Status'];
        $rowKeys = ['name', 'country.name', 'phone', 'User.email', 'status_badge'];

        $source = $this->sellerRepository->paginateWithRelations(
            perPage: 15,
            page: 1,
            relations: ['country:id,name', 'user:id,email'],
            conditions: ['manager_user_id' => $userId],
            fields: ['id', 'country_id', 'user_id', 'name', 'phone', 'status']
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

        $sellers = $this->processCollectionTableData($collection, $labels, $rowKeys);

        return ['sellers' => $sellers, 'pagination' => $pagination];
    }

    /**
     * Obtener contexto de vendedores del usuario manager.
     *
     * @param int|null $userId ID del usuario manager
     * @return ManagerSellerContextDTO Contexto de vendedores para la vista Inertia
     */
    public function resolve(int $userId): ManagerSellerContextDTO
    {
        $result = $this->resolveSellers($userId);

        return new ManagerSellerContextDTO(
            sellers: $result['sellers'],
            pagination: $result['pagination'],
            countries: $this->countries->getActive(['id', 'name']),
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
        return $this->resolveSellers($managerUserId)['sellers']['rows'] ?? [];
    }
}
