<?php

namespace App\Services\Roles\Admin;

use App\Contracts\CountryRepositoryInterface;
use App\Contracts\CurrencyRepositoryInterface;
use App\Contracts\GameRepositoryInterface;
use App\Contracts\Roles\Commerce\CommerceRepositoryInterface;
use App\Contracts\Roles\Customer\CustomerRepositoryInterface;
use App\Contracts\TimeZoneRepositoryInterface;
use App\DTOs\Roles\Admin\ContextDTO;
use App\DTOs\Roles\Admin\Commerce\AdminCommerceContextDTO;
use App\DTOs\Roles\Admin\Customer\AdminCustomerContextDTO;
use DomainException;

class AdminContextService
{
    public function __construct(
        protected CountryRepositoryInterface $countries,
        protected CurrencyRepositoryInterface $currencies,
        protected CommerceRepositoryInterface $commerces,
        protected CustomerRepositoryInterface $customers,
        protected GameRepositoryInterface $games,
        protected TimeZoneRepositoryInterface $timeZones
    ) {}

    /**
     * Obtiene el contexto necesario para la visualización de tasas de cambio.
     *
     * Este método resuelve la información según el usuario que realiza la petición,
     * diferenciando entre usuario autenticado y usuario público.
     *
     * Para usuarios autenticados, incluye layout específico, monedas disponibles
     * para el comercio asociado y listado de tasas de cambio.
     * Para usuarios públicos, muestra un layout de invitado y oculta las monedas.
     *
     * @return \App\DTOs\Roles\Admin\ContextDTO Contexto listo para la vista.
     *
     * @throws \DomainException Si no se encuentra el comercio asociado al usuario.
     */
    public function resolve(string $context): ContextDTO
    {
        return match ($context) {
            'commerce' => $this->commerceContext(),
            'customer' => $this->customerContetx(),
            default => throw new DomainException('Tipo Rol inválido, no existe el contexto'),
        };
    }

    private function commerceContext(): AdminCommerceContextDTO
    {
        $commerces = $this->commerces->getAllWithRelations(
            [
                'country:id,name',
                'user:id,email',
            ],
            ['id', 'country_id', 'user_id', 'name', 'phone', 'status']
        );

        return new AdminCommerceContextDTO(
            commerces: $commerces,
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
            commerces: $customers,
            countries: $this->countries->getActive(),
            currencies: $this->currencies->getActive(),
            games: $this->games->getActive(),
            timeZones: $this->timeZones->getActive()
        );
    }
}
