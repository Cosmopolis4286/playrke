<?php

namespace App\Services\Roles\Seller\Dashboard;

use App\Contracts\CountryRepositoryInterface;
use App\Contracts\CurrencyRepositoryInterface;
use App\Contracts\GameRepositoryInterface;
use App\Contracts\Roles\Commerce\CommerceRepositoryInterface;
use App\Contracts\Roles\Customer\CustomerRepositoryInterface;
use App\Contracts\TimeZoneRepositoryInterface;
use App\DTOs\ContextDTO;
use App\DTOs\Roles\Admin\Customer\AdminCustomerContextDTO;
use App\Traits\HashedTableDataTrait;
use DomainException;
use Illuminate\Pagination\LengthAwarePaginator;

class DashboardSellerContextService
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
            'commerces' => $this->resolveCommerces(),
            'customers' => $this->resolveCustomers(),
            default => throw new DomainException('Tipo Rol inválido, no existe el contexto'),
        };
    }

    /**
     * Resolver listado de comercios.
     *
     * @param int|null $userId ID del usuario manager
     * @return array [commerces, pagination]
     */
    public function resolveCommerces(?int $userId = null): array
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

    /**
     * Resolver listado de clientes.
     *
     * @param int|null $userId ID del usuario manager
     * @return array [customers, pagination]
     */
    public function resolveCustomers(): AdminCustomerContextDTO
    {
        $customers = $this->customers->getAllWithRelations([
            'country:id,name',
            'user:id,email',
        ]);

        $labels  = ['Nombre', 'País', 'Teléfono', 'Email', 'Status'];
        $rowKeys = ['name', 'country.name', 'phone', 'User.email', 'status_badge'];

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
