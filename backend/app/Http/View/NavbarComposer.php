<?php

namespace App\Http\View;

use App\Models\Wallet;
use App\Services\Menu\SidebarMenuService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NavbarComposer
{
    /**
     * Roles que pueden gestionar depósitos/retiros pendientes.
     */
    private const ROLES_PENDING_MOVEMENTS = ['superadmin', 'admin', 'treasurer'];

    public function __construct(private SidebarMenuService $menuService)
    {
    }

    public function compose(View $view): void
    {
        if (! Auth::check()) {
            $view->with([
                'navWallet'        => null,
                'navUser'          => null,
                'navBreadcrumb'    => [],
                'navPendingCount'  => 0,
            ]);
            return;
        }

        $wallet = Wallet::with('currency')
            ->where('user_id', Auth::id())
            ->where('status', 'ACTIVE')
            ->first();

        $user = Auth::user();

        $view->with([
            'navWallet'       => $wallet,
            'navUser'         => $user,
            'navBreadcrumb'   => $this->menuService->getBreadcrumb(),
            'navPendingCount' => $user->hasRole(self::ROLES_PENDING_MOVEMENTS)
                ? $this->menuService->pendingWalletMovementsCount()
                : 0,
        ]);
    }
}
