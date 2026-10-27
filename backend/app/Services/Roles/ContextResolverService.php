<?php

namespace App\Services\Roles;

use App\DTOs\ContextDTO;
use App\Exceptions\Domain\Roles\RoleResolutionException;
use App\Traits\HashedTableDataTrait;

/**
 * Resuelve el ContextDTO correspondiente a un tipo de página/dashboard por rol.
 * No es exclusivo de Admin: cubre dashboards (admin/superadmin/player) y
 * listados CRUD (commerces/customers/players/users/sellers/treasurers).
 */
final class ContextResolverService
{
    use HashedTableDataTrait;

    public function __construct(
        private readonly \App\Services\Roles\Admin\Dashboard\AdminDashboardContextService $dashboardContextService,
        private readonly \App\Services\Roles\SuperAdmin\Dashboard\SuperAdminDashboardContextService $superAdminDashboardContextService,
        private readonly \App\Services\Roles\Admin\Commerce\AdminCommerceContextService $commerceContextService,
        private readonly \App\Services\Roles\Admin\Customer\AdminCustomerContextService $customerContextService,
        private readonly \App\Services\Roles\Admin\Player\AdminPlayerContextService $playerContextService,
        private readonly \App\Services\Roles\Admin\User\AdminUserContextService $userContextService,
        private readonly \App\Services\Roles\Seller\Manager\ManagerSellerContextService $sellerContextService,
        private readonly \App\Services\Roles\Admin\Treasurer\AdminTreasurerContextService $treasurerContextService,
        private readonly \App\Services\Roles\Admin\Subsidiary\AdminSubsidiaryContextService $subsidiaryContextService,
        private readonly \App\Services\Roles\Player\Dashboard\PlayerDashboardContextService $playerDashboardContextService,
        private readonly \App\Services\Roles\Commerce\Dashboard\CommerceDashboardContextService $commerceDashboardContextService,
        private readonly \App\Services\Roles\Seller\Dashboard\SellerDashboardContextService $sellerDashboardContextService,
        private readonly \App\Services\Roles\Subsidiary\Dashboard\SubsidiaryDashboardContextService $subsidiaryDashboardContextService,
        private readonly \App\Services\Roles\Treasurer\Dashboard\TreasurerDashboardContextService $treasurerDashboardContextService,
        private readonly \App\Services\Roles\Customer\Dashboard\CustomerDashboardContextService $customerDashboardContextService,
    ) {
    }

    /**
     * Resolver contexto por tipo de página/rol.
     *
     * @param string $context Tipo de contexto a resolver
     *
     * @throws RoleResolutionException Si el tipo de contexto es inválido
     */
    public function resolve(string $context) : ContextDTO
    {
        return match ($context) {
            'dashboard', 'admin_dashboard' => $this->dashboardContextService->dashboardContext(),
            'superadmin_dashboard' => $this->superAdminDashboardContextService->dashboardContext(),
            'player_dashboard' => $this->playerDashboardContextService->dashboardContext(),
            'commerce_dashboard' => $this->commerceDashboardContextService->dashboardContext(),
            'seller_dashboard' => $this->sellerDashboardContextService->dashboardContext(),
            'subsidiary_dashboard' => $this->subsidiaryDashboardContextService->dashboardContext(),
            'treasurer_dashboard' => $this->treasurerDashboardContextService->dashboardContext(),
            'customer_dashboard' => $this->customerDashboardContextService->dashboardContext(),
            'commerces' => $this->commerceContextService->commerceContext(),
            'customers' => $this->customerContextService->customerContext(),
            'players' => $this->playerContextService->playerContext(),
            'users' => $this->userContextService->userContext(),
            'sellers' => $this->sellerContextService->resolve(),
            'treasurers' => $this->treasurerContextService->treasurerContext(),
            'subsidiaries' => $this->subsidiaryContextService->subsidiaryContext(),
            default => throw RoleResolutionException::contextNotFound(),
        };
    }
}
