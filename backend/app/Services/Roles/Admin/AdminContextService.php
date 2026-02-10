<?php

namespace App\Services\Roles\Admin;

use App\DTOs\ContextDTO;
use App\DTOs\Roles\Admin\Commerce\AdminCommerceContextDTO;
use App\DTOs\Roles\Admin\Customer\AdminCustomerContextDTO;
use App\Traits\HashedTableDataTrait;
use DomainException;
use Illuminate\Pagination\LengthAwarePaginator;

final class AdminContextService
{
    use HashedTableDataTrait;

    public function __construct(
        protected readonly \App\Contracts\CountryRepositoryInterface $countries,
        protected readonly \App\Contracts\CurrencyRepositoryInterface $currencies,
        protected readonly \App\Contracts\Roles\Commerce\CommerceRepositoryInterface $commerces,
        protected readonly \App\Contracts\Roles\Customer\CustomerRepositoryInterface $customers,
        protected readonly \App\Contracts\GameRepositoryInterface $games,
        protected readonly \App\Contracts\TimeZoneRepositoryInterface $timeZones
    ) {}

    public function resolve(string $context): ContextDTO
    {
        return match ($context) {
            'commerces' => $this->commerceContext(),
            'customers' => $this->resolveCustomers(),
            default => throw new DomainException('Tipo Rol inválido, no existe el contexto'),
        };
    }

    /**
     * Resolver contexto para comercios.
     *
     * @return AdminCommerceContextDTO
     */
    private function commerceContext(): AdminCommerceContextDTO
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
        $rowKeys = ['name', 'Country.name', 'phone', 'User.email', 'status_badge'];

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

    /**
     * Resolver contexto para clientes.
     *
     * @return AdminCustomerContextDTO
     */
    private function customerContext(): AdminCustomerContextDTO
    {
        $customerData = $this->resolveCustomers();

        return new AdminCustomerContextDTO(
            customers: $customerData['customers'],
            pagination: $customerData['pagination'],
            countries: $this->countries->getActive(['id', 'name']),
            currencies: $this->currencies->getActive(['id', 'iso_code', 'symbol', 'name', 'country_id']),
            games: $this->games->getActive(['id', 'name']),
            timeZones: $this->timeZones->getActive(['id', 'name', 'utc', 'country_id'])
        );
    }

    /**
     * Resolver listado de clientes.
     *
     * @return array [customers, pagination]
     */
    public function resolveCustomers(): array
    {
        $labels  = ['Nombre', 'País', 'Teléfono', 'Email', 'Status'];
        $rowKeys = ['name', 'Country.name', 'phone', 'User.email', 'status_badge'];

        $source = $this->customers->paginateWithRelations(
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

        $customers = $this->processCollectionTableData($collection, $labels, $rowKeys);

        return ['customers' => $customers, 'pagination' => $pagination];
    }
}
