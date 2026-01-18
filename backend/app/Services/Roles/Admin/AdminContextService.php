<?php

namespace App\Services\Roles\Admin;

use App\Contracts\CountryRepositoryInterface;
use App\Contracts\CurrencyRepositoryInterface;
use App\Contracts\GameRepositoryInterface;
use App\Contracts\Roles\Commerce\CommerceRepositoryInterface;
use App\Contracts\Roles\Customer\CustomerRepositoryInterface;
use App\Contracts\TimeZoneRepositoryInterface;
use App\DTOs\Roles\Admin\AdminContextDTO;
use App\DTOs\Roles\Admin\Commerce\AdminCommerceContextDTO;
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
     * @return \App\DTOs\Roles\Admin\AdminContextDTO Contexto listo para la vista.
     *
     * @throws \DomainException Si no se encuentra el comercio asociado al usuario.
     */
    public function resolve(string $context): AdminContextDTO
    {
        return match ($context) {
            'commerce' => $this->commerceContext(),
            'customer' => $this->customerContetx(),
            default => throw new DomainException('Tipo Rol inválido, no existe el contexto'),
        };
    }

    private function commerceContext(): AdminCommerceContextDTO
    {
        $commerces = $this->commerces->getAllWithRelations([
            'country:id,name',
            'user:id,email',
        ]);

        return new AdminCommerceContextDTO(
            commerces: $commerces,
            countries: $this->countries->getActive(),
            currencies: $this->currencies->getActive(),
            games: $this->games->getActive(),
            timeZones: $this->timeZones->getActive()
        );
    }

    private function customerContetx(): AdminCommerceContextDTO
    {
        $customers = $this->customers->getAllWithRelations([
            'country:id,name',
            'user:id,email',
        ]);

        return new AdminCommerceContextDTO(
            commerces: $customers,
            countries: $this->countries->getActive(),
            currencies: $this->currencies->getActive(),
            games: $this->games->getActive(),
            timeZones: $this->timeZones->getActive()
        );
    }
}
