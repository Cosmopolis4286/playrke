<?php

namespace App\Services\Roles\Admin;

use App\DTOs\ContextDTO;
use App\Traits\HashedTableDataTrait;
use DomainException;

final class AdminContextService
{
    use HashedTableDataTrait;

    public function __construct(
        private readonly \App\Services\Roles\Admin\Commerce\AdminCommerceContextService  $commerceContextService,
        private readonly \App\Services\Roles\Admin\Customer\AdminCustomerContextService  $customerContextService,
        private readonly \App\Services\Roles\Admin\Player\AdminPlayerContextService     $playerContextService,
        private readonly \App\Services\Roles\Admin\User\AdminUserContextService         $userContextService,
        private readonly \App\Services\Roles\Seller\Manager\ManagerSellerContextService  $sellerContextService,
        private readonly \App\Services\Roles\Admin\Treasurer\AdminTreasurerContextService $treasurerContextService,
    ) {}

    /**
     * Resolver contexto para Admin.
     *
     * @param string $context Tipo de contexto a resolver
     * @return ContextDTO
     *
     * @throws DomainException Si el tipo de contexto es inválido
     */
    public function resolve(string $context): ContextDTO
    {
        return match ($context) {
            'commerces'  => $this->commerceContextService->commerceContext(),
            'customers'  => $this->customerContextService->customerContext(),
            'players'    => $this->playerContextService->playerContext(),
            'users'      => $this->userContextService->userContext(),
            'sellers'    => $this->sellerContextService->resolve(),
            'treasurers' => $this->treasurerContextService->treasurerContext(),
            default      => throw new DomainException('Tipo Rol inválido, no existe el contexto'),
        };
    }
}
