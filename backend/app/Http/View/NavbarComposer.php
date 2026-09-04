<?php

namespace App\Http\View;

use App\Domains\Wallet\Models\Wallet;
use App\Services\Games\DailyMissionService;
use App\Services\Menu\SidebarMenuService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NavbarComposer
{
    /**
     * Roles que pueden gestionar depósitos/retiros pendientes.
     */
    private const ROLES_PENDING_MOVEMENTS = ['superadmin', 'admin', 'treasurer'];

    public function __construct(
        private SidebarMenuService $menuService,
        private DailyMissionService $dailyMissionService,
    ) {
    }

    public function compose(View $view): void
    {
        if (! Auth::check()) {
            $view->with([
                'navWallet'              => null,
                'navUser'                => null,
                'navBreadcrumb'          => [],
                'navPendingCount'        => 0,
                'navMissionsPending'     => 0,
            ]);
            return;
        }

        $wallet = Wallet::with('currency')
            ->where('user_id', Auth::id())
            ->where('status', 'ACTIVE')
            ->first();

        $user = Auth::user();

        // Nivel/XP y misiones diarias son datos exclusivos de jugador
        // (games_played_count, games_won_count, total_winnings, wallet de
        // jugador) — el resto de roles (admin, commerce, treasurer,
        // subsidiary, seller, customer, superadmin) nunca generan esos
        // datos, así que sin este gate el navbar les mostraba "Nivel 1,
        // 0/1000 XP" y un badge de "misiones pendientes" que nunca podrán
        // completar por una vía legítima. Mismo criterio ya usado arriba
        // para navPendingCount (ROLES_PENDING_MOVEMENTS). Se evita también
        // la query de userLevel/todayStatus para quien no la necesita.
        $isPlayer = $user->hasRole('player');

        $missionsPending = 0;
        if ($isPlayer) {
            $user->loadMissing('userLevel');

            // Indicador discreto (badge de conteo, no notificación push) —
            // las misiones se resetean cada día, así que empujar una
            // notificación diaria de "misiones nuevas disponibles" se vuelve
            // ruido rápido; el badge es visible siempre sin interrumpir.
            $missionsToday = $this->dailyMissionService->todayStatus($user);
            $missionsPending = $missionsToday->filter(fn (array $row) => ! $row['completed'])->count();
        }

        $view->with([
            'navWallet'          => $wallet,
            'navUser'            => $user,
            'navUserLevel'       => $isPlayer ? $user->userLevel : null,
            'navBreadcrumb'      => $this->menuService->getBreadcrumb(),
            'navPendingCount'    => $user->hasRole(self::ROLES_PENDING_MOVEMENTS)
                ? $this->menuService->pendingWalletMovementsCount()
                : 0,
            'navMissionsPending' => $missionsPending,
        ]);
    }
}
