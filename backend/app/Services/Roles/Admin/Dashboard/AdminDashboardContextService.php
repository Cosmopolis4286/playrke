<?php

namespace App\Services\Roles\Admin\Dashboard;

use App\DTOs\Roles\Admin\Dashboard\AdminDashboardContextDTO;
use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Models\Roles\Commerce;
use App\Models\Roles\Customer;
use App\Models\Roles\Seller;
use App\Models\Roles\Treasurer;
use App\Models\User;
use App\Services\Menu\SidebarMenuService;
use App\Services\Roles\Dashboard\Concerns\BuildsRecentActivity;
use App\Services\Roles\Dashboard\Concerns\BuildsSalesTrend;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

final class AdminDashboardContextService
{
    use BuildsRecentActivity;
    use BuildsSalesTrend;

    private const CACHE_TTL_SECONDS = 90;

    public function __construct(
        private readonly SidebarMenuService $sidebarMenu,
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
            ];
        });

        return new AdminDashboardContextDTO(
            userName:           $user?->first_name ?? $user?->username ?? 'Admin',
            stats:              $cached['stats'],
            recentActivity:     $cached['recentActivity'],
            entityCounts:       $cached['entityCounts'],
            salesTrend:         $cached['salesTrend'],
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
            'totalUsers' => [
                'label'       => 'Usuarios totales',
                'value'       => $totalUsers,
                'icon'        => 'fas fa-users',
                'variant'     => 'primary',
                'description' => "{$activeUsers} activos",
            ],
            'newUsersThisMonth' => [
                'label'   => 'Nuevos este mes',
                'value'   => $newUsersThisMonth,
                'icon'    => 'fas fa-user-plus',
                'variant' => 'success',
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
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Conteos de entidades por rol
    // -------------------------------------------------------------------------

    private function buildEntityCounts(): array
    {
        return [
            ['label' => 'Comercios',   'value' => Commerce::count(),  'icon' => 'fas fa-building',      'variant' => 'primary'],
            ['label' => 'Vendedores',  'value' => Seller::count(),    'icon' => 'fas fa-user-tie',       'variant' => 'success'],
            ['label' => 'Clientes',    'value' => Customer::count(),  'icon' => 'fas fa-handshake',      'variant' => 'info'],
            ['label' => 'Tesoreros',   'value' => Treasurer::count(), 'icon' => 'fas fa-piggy-bank',     'variant' => 'warning'],
        ];
    }
}
