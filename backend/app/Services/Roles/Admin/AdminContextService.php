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

    /**
     * Construye el contexto de tabla para el tipo de rol/usuario
     * - IDs hasheados
     * - Keys normalizadas
     * - Columnas para frontend
     * 
     * @param object $collection Colección de Eloquent
     * @param array $columns Columnas visibles en la tabla
     * @param array $rowKeys Keys fuente para mapear valores desde el modelo
     *
     * @return array
     */
    private function getTableData(object $collection, array $columns, array $rowKeys): array
    {
        return $this->processCollectionTableData(
            $collection,
            $columns,
            $rowKeys
        );
    }

    private function commerceContext(): AdminCommerceContextDTO
    {
        $commerces = $this->commerces->paginateWithRelations(
            perPage: 15,
            page: 1,
            relations: ['country:id,name', 'user:id,email'],
            conditions: [],
            fields: ['id', 'country_id', 'user_id', 'name', 'phone', 'status']
        );

        return new AdminCommerceContextDTO(
            commerces: $this->getTableData($commerces, $columns, $rowKeys),
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

        return new AdminCustomerContextDTO(
            customers: $this->getTableData($customers),
            countries: $this->countries->getActive(),
            currencies: $this->currencies->getActive(),
            games: $this->games->getActive(),
            timeZones: $this->timeZones->getActive()
        );
    }
}
