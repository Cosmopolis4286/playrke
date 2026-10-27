<?php

namespace App\Services\Roles\Admin\Customer;

use App\DTOs\Roles\Admin\Customer\AdminCustomerContextDTO;
use App\Traits\HashedTableDataTrait;
use Illuminate\Pagination\LengthAwarePaginator;

final class AdminCustomerContextService
{
    use HashedTableDataTrait;

    public function __construct(
        protected readonly \App\Contracts\Roles\Customer\CustomerRepositoryInterface $customers,
        private readonly \App\Contracts\CountryRepositoryInterface $countries,
        private readonly \App\Contracts\CurrencyRepositoryInterface $currencies,
        private readonly \App\Contracts\GameRepositoryInterface $games,
        private readonly \App\Contracts\Roles\Seller\SellerRepositoryInterface $sellerRepository,
        private readonly \App\Contracts\TimeZoneRepositoryInterface $timeZones,
    ) {
    }

    /**
     * Resolver contexto para clientes.
     */
    public function customerContext() : AdminCustomerContextDTO
    {
        $customerData = $this->resolveCustomers();

        return new AdminCustomerContextDTO(
            customers: $customerData['customers'],
            pagination: $customerData['pagination'],
            countries: $this->countries->getActive(['id', 'name', 'iso_code_2']),
            currencies: $this->currencies->getActive(['id', 'iso_code', 'symbol', 'name', 'country_id']),
            games: $this->games->getActive(['id', 'name']),
            timeZones: $this->timeZones->getActive(['id', 'name', 'utc', 'country_id']),
        );
    }

    /**
     * Resolver listado de clientes.
     *
     * @return array [customers, pagination]
     */
    public function resolveCustomers() : array
    {
        $labels = ['Nombre', 'País', 'Teléfono', 'Email', 'Status'];
        $rowKeys = ['name', 'country.name', 'phone', 'User.email', 'status_badge'];

        $source = $this->customers->paginateWithRelations(
            perPage: 15,
            page: 1,
            relations: ['country:id,name', 'user:id,email'],
            conditions: [],
            fields: ['id', 'country_id', 'user_id', 'name', 'phone', 'status'],
        );

        $collection = $source instanceof LengthAwarePaginator
            ? $source->getCollection()
            : collect($source);

        $pagination = $source instanceof LengthAwarePaginator
            ? [
                'total' => $source->total(),
                'per_page' => $source->perPage(),
                'current_page' => $source->currentPage(),
                'last_page' => $source->lastPage(),
                'from' => $source->firstItem(),
                'to' => $source->lastItem(),
            ]
            : null;

        $customers = $this->processCollectionTableData($collection, $labels, $rowKeys);

        return ['customers' => $customers, 'pagination' => $pagination];
    }
}
