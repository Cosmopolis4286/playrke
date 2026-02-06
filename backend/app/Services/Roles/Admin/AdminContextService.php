<?php

namespace App\Services\Roles\Admin;

use App\Contracts\CountryRepositoryInterface;
use App\Contracts\CurrencyRepositoryInterface;
use App\Contracts\GameRepositoryInterface;
use App\Contracts\Roles\Commerce\CommerceRepositoryInterface;
use App\Contracts\Roles\Customer\CustomerRepositoryInterface;
use App\Contracts\TimeZoneRepositoryInterface;
use App\DTOs\ContextDTO;
use App\DTOs\Roles\Admin\Commerce\AdminCommerceContextDTO;
use App\DTOs\Roles\Admin\Customer\AdminCustomerContextDTO;
use App\Traits\HashedTableDataTrait;
use DomainException;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminContextService
{
    use HashedTableDataTrait;

    public function __construct(
        protected CountryRepositoryInterface $countries,
        protected CurrencyRepositoryInterface $currencies,
        protected CommerceRepositoryInterface $commerces,
        protected CustomerRepositoryInterface $customers,
        protected GameRepositoryInterface $games,
        protected TimeZoneRepositoryInterface $timeZones
    ) {}

    public function resolve(string $context): ContextDTO
    {
        return match ($context) {
            'commerces' => $this->commerceContext(),
            'customers' => $this->customerContetx(),
            default => throw new DomainException('Tipo Rol inválido, no existe el contexto'),
        };
    }

    private function commerceContext(): AdminCommerceContextDTO
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

        return new AdminCommerceContextDTO(
            commerces: $this->processCollectionTableData($collection, $labels, $rowKeys),
            pagination: $pagination,
            countries: $this->countries->getActive(['id', 'name']),
            currencies: $this->currencies->getActive(['id', 'iso_code', 'symbol', 'name', 'country_id']),
            games: $this->games->getActive(['id', 'name']),
            timeZones: $this->timeZones->getActive(['id', 'name', 'utc', 'country_id'])
        );
    }

    private function customerContetx(): AdminCustomerContextDTO
    {
        $customers = $this->customers->getAllWithRelations([
            'country:id,name',
            'user:id,email',
        ]);

        $labels  = ['Nombre', 'País', 'Teléfono', 'Email', 'Status'];
        $rowKeys = ['name', 'Country.name', 'phone', 'User.email', 'status_badge'];

        $collection = collect($customers);

        return new AdminCustomerContextDTO(
            customers: $this->processCollectionTableData($collection, $labels, $rowKeys),
            pagination: null,
            countries: $this->countries->getActive(),
            currencies: $this->currencies->getActive(),
            games: $this->games->getActive(),
            timeZones: $this->timeZones->getActive()
        );
    }
}
