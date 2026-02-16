<?php

namespace App\Services\Roles\Admin\Commerce;

use App\DTOs\Roles\Admin\Commerce\AdminCommerceContextDTO;
use App\Traits\HashedTableDataTrait;
use Illuminate\Pagination\LengthAwarePaginator;

final class AdminCommerceContextService
{
    use HashedTableDataTrait;

    public function __construct(
        private readonly \App\Contracts\Roles\Commerce\CommerceRepositoryInterface $commerces,
        private readonly \App\Contracts\CountryRepositoryInterface $countries,
        private readonly \App\Contracts\CurrencyRepositoryInterface $currencies,
        private readonly \App\Contracts\GameRepositoryInterface $games,
        private readonly \App\Contracts\Roles\Seller\SellerRepositoryInterface $sellerRepository,
        private readonly \App\Contracts\TimeZoneRepositoryInterface $timeZones
    ) {}

    /**
     * Resolver contexto para comercios.
     *
     * @return AdminCommerceContextDTO
     */
    public function commerceContext(): AdminCommerceContextDTO
    {
        $commerceData = $this->resolveCommerces();

        return new AdminCommerceContextDTO(
            commerces: $commerceData['commerces'],
            pagination: $commerceData['pagination'],
            countries: $this->countries->getActive(['id', 'name']),
            currencies: $this->currencies->getActive(['id', 'iso_code', 'symbol', 'name', 'country_id']),
            games: $this->games->getActive(['id', 'name']),
            timeZones: $this->timeZones->getActive(['id', 'name', 'utc', 'country_id'])
        );
    }

    /**
     * Resolver listado de comercios.
     *
     * @return array [commerces, pagination]
     */
    public function resolveCommerces(): array
    {
        $labels  = ['Nombre', 'País', 'Teléfono', 'Email', 'Status'];
        $rowKeys = ['name', 'country.name', 'phone', 'User.email', 'status_badge'];

        $source = $this->commerces->paginateWithRelations(
            perPage: 15,
            page: 1,
            relations: ['country:id,name', 'user:id,email'],
            conditions: [],
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

        $commerces = $this->processCollectionTableData($collection, $labels, $rowKeys);

        return ['commerces' => $commerces, 'pagination' => $pagination];
    }
}
