<?php

namespace App\Services\Roles\Admin\Dashboard;

use App\DTOs\Roles\Admin\Dashboard\AdminDashboardContextDTO;
use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Models\Roles\Admin;
use App\Models\Roles\Commerce;
use App\Models\Roles\Customer;
use App\Models\Roles\Seller;
use App\Models\Roles\Treasurer;
use App\Models\User;
use App\Services\Menu\SidebarMenuService;
use App\Services\Roles\Dashboard\Concerns\BuildsRecentActivity;
use App\Services\Roles\Dashboard\Concerns\BuildsSalesTrend;
use App\Services\Roles\Dashboard\Concerns\BuildsWeeklyTrend;
use App\Services\Roles\SuperAdmin\Rke\RkeSummaryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

final class AdminDashboardContextService
{
    use BuildsRecentActivity;
    use BuildsSalesTrend;
    use BuildsWeeklyTrend;

    private const CACHE_TTL_SECONDS = 90;

    public function __construct(
        private readonly SidebarMenuService $sidebarMenu,
        private readonly RkeSummaryService $rkeSummary,
    ) {}

    public function dashboardContext(): AdminDashboardContextDTO
    {
        $user   = Auth::user();
        $now    = Carbon::now();
        $period = ucfirst($now->translatedFormat('F Y'));

        $cached = Cache::remember('dashboard:admin:aggregates', self::CACHE_TTL_SECONDS, function () use ($now) {
            return [
                'stats'          => $this->buildStats($now),
                'recentActivity' => $this->buildRecentActivity(),
                'entityCounts'   => $this->buildEntityCounts(),
                'salesTrend'     => $this->buildSalesTrend(),
                'rkeTotals'      => $this->rkeSummary->getSummary()['totals'],
            ];
        });

        return new AdminDashboardContextDTO(
            userName:           $user?->first_name ?? $user?->username ?? 'Admin',
            stats:              $cached['stats'],
            recentActivity:     $cached['recentActivity'],
            entityCounts:       $cached['entityCounts'],
            salesTrend:         $cached['salesTrend'],
            rkeTotals:          $cached['rkeTotals'],
            pendingWalletCount: $this->sidebarMenu->pendingWalletMovementsCount(),
            period:             $period,
        );
    }

    // -------------------------------------------------------------------------
    // Stats principales
    // -------------------------------------------------------------------------

    private function buildStats(Carbon $now): array
    {
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfDay   = $now->copy()->startOfDay();

        $totalUsers        = User::count();
        $activeUsers       = User::active()->count();
        $newUsersThisMonth = User::where('created_at', '>=', $startOfMonth)->count();

        $cardsToday      = BingoCard::where('created_at', '>=', $startOfDay)->count();
        $cardsThisMonth  = BingoCard::where('created_at', '>=', $startOfMonth)->count();
        $activeRooms     = BingoRoom::active()->count();

        return [
            'totalUsers' => array_merge([
                'label'       => 'Usuarios totales',
                'value'       => $totalUsers,
                'icon'        => 'fas fa-users',
                'variant'     => 'primary',
                'description' => "{$activeUsers} activos",
                'href'        => '/admin/users',
            ], $this->buildWeeklyTrend(User::query())),
            'newUsersThisMonth' => [
                'label'   => 'Nuevos este mes',
                'value'   => $newUsersThisMonth,
                'icon'    => 'fas fa-user-plus',
                'variant' => 'success',
                'href'    => '/admin/users',
            ],
            'cardsToday' => [
                'label'       => 'Cartones hoy',
                'value'       => $cardsToday,
                'icon'        => 'fas fa-ticket-alt',
                'variant'     => 'info',
                'description' => "{$cardsThisMonth} este mes",
            ],
            'activeRooms' => [
                'label'   => 'Salas activas',
                'value'   => $activeRooms,
                'icon'    => 'fas fa-store',
                'variant' => 'warning',
                'href'    => '/bingo-rooms',
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Conteos de entidades por rol
    // -------------------------------------------------------------------------

    private function buildEntityCounts(): array
    {
        return [
            array_merge([
                'label' => 'Administradores', 'value' => Admin::count(), 'icon' => 'fas fa-user-shield',
                'variant' => 'danger', 'href' => '/admins',
            ], $this->buildWeeklyTrend(Admin::query())),
            array_merge([
                'label' => 'Comercios', 'value' => Commerce::count(), 'icon' => 'fas fa-building',
                'variant' => 'primary', 'href' => '/admin/commerces',
            ], $this->buildWeeklyTrend(Commerce::query())),
            array_merge([
                'label' => 'Vendedores', 'value' => Seller::count(), 'icon' => 'fas fa-user-tie',
                'variant' => 'success', 'href' => '/admin/sellers',
            ], $this->buildWeeklyTrend(Seller::query())),
            array_merge([
                'label' => 'Clientes', 'value' => Customer::count(), 'icon' => 'fas fa-handshake',
                'variant' => 'info', 'href' => '/admin/customers',
            ], $this->buildWeeklyTrend(Customer::query())),
            array_merge([
                'label' => 'Jugadores', 'value' => User::role('player')->count(), 'icon' => 'fas fa-gamepad',
                'variant' => 'secondary', 'href' => '/admin/players',
            ], $this->buildWeeklyTrend(User::role('player'))),
            array_merge([
                'label' => 'Tesoreros', 'value' => Treasurer::count(), 'icon' => 'fas fa-piggy-bank',
                'variant' => 'warning', 'href' => '/admin/treasurers',
            ], $this->buildWeeklyTrend(Treasurer::query())),
        ];
    }
}
