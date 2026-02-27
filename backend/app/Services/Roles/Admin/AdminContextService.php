<?php

namespace App\Services\Roles\Admin;

use App\DTOs\ContextDTO;
use App\Services\Roles\Admin\Commerce\AdminCommerceContextService;
use App\Services\Roles\Admin\Customer\AdminCustomerContextService;
use App\Services\Roles\Admin\Treasurer\AdminTreasurerContextService;
use App\Traits\HashedTableDataTrait;
use DomainException;

final class AdminContextService
{
    use HashedTableDataTrait;

    public function __construct(
        private readonly AdminCommerceContextService $commerceContextService,
        private readonly AdminCustomerContextService $customerContextService,
        private readonly AdminTreasurerContextService $treasurerContextService,
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
            'commerces' => $this->commerceContextService->commerceContext(),
            'customers' => $this->customerContextService->customerContext(),
            'treasurers' => $this->treasurerContextService->treasurerContext(),
            default => throw new DomainException('Tipo Rol inválido, no existe el contexto'),
        };
    }
}
