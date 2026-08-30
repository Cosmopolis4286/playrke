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
use App\Services\Dashboard\DashboardWidgetFilter;
use App\Services\Dashboard\DashboardWidgetVisibilityService;
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
        private readonly DashboardWidgetVisibilityService $widgetVisibility,
    ) {}

    public function dashboardContext(): AdminDashboardContextDTO
    {
        $user   = Auth::user();
        $now    = Carbon::now();
        $period = ucfirst($now->translatedFormat('F Y'));

        // Los agregados crudos (conteos, tendencias) SÍ se comparten entre
        // todos los admins vía cache — son datos objetivos, no dependen de
        // quién mira. El filtrado por permiso/visibilidad ocurre DESPUÉS,
        // fuera del bloque cacheado: cachear ya-filtrado "congelaría" el
        // primer usuario que cargue el dashboard para todos los demás,
        // aunque tengan permisos distintos.
        $cached = Cache::remember('dashboard:admin:aggregates', self::CACHE_TTL_SECONDS, function () use ($now) {
            return [
                'stats'          => $this->buildStats($now),
                'recentActivity' => $this->buildRecentActivity(),
                'entityCounts'   => $this->buildEntityCounts(),
                'salesTrend'     => $this->buildSalesTrend(),
                'rkeTotals'      => $this->rkeSummary->getSummary()['totals'],
            ];
        });

        $filter = $this->widgetVisibility->filterFor($user);

        return new AdminDashboardContextDTO(
            userName:           $user?->first_name ?? $user?->username ?? 'Admin',
            stats:              $this->filterStats($cached['stats'], $filter),
            recentActivity:     $cached['recentActivity'],
            entityCounts:       $this->filterEntityCounts($cached['entityCounts'], $filter),
            salesTrend:         $cached['salesTrend'],
            rkeTotals:          $cached['rkeTotals'],
            pendingWalletCount: $this->sidebarMenu->pendingWalletMovementsCount(),
            period:             $period,
            allowedQuickLinks:  $this->allowedQuickLinks($filter),
        );
    }

    /**
     * Labels de accesos rápidos que el usuario puede ver — el catálogo de
     * links en sí (href/icon/description) sigue viviendo en
     * useDashboardQuickLinks.ts; esto solo dice cuáles mostrar. No se
     * comparten permisos crudos al frontend (superficie de ataque
     * innecesaria) — el backend ya resolvió la decisión de autorización.
     *
     * @return string[]
     */
    private function allowedQuickLinks(DashboardWidgetFilter $filter): array
    {
        return collect($this->widgetVisibility->getCatalog())
            ->filter(fn ($w) => $w['dashboard'] === 'admin' && $w['type'] === 'quicklink')
            ->filter(fn ($w) => $filter->isVisible($w['key'], $w['permission']))
            ->map(fn ($w) => str_replace('admin.quicklink.', '', $w['key']))
            ->values()
            ->all();
    }

    /**
     * Claves de $stats coinciden 1:1 con el sufijo de item_key en
     * DashboardWidgetVisibilityService::CATALOG ('admin.stat.{clave}').
     */
    private function filterStats(array $stats, DashboardWidgetFilter $filter): array
    {
        $permissions = [
            'totalUsers'        => null,
            'newUsersThisMonth' => null,
            'cardsToday'        => 'games.bingo',
            'activeRooms'       => 'games.bingo',
        ];

        return collect($stats)
            ->filter(fn ($_, $key) => $filter->isVisible("admin.stat.{$key}", $permissions[$key] ?? null))
            ->all();
    }

    /**
     * $entityCounts es una lista indexada (no asociativa) — cada entrada
     * trae su propio 'label', usado como sufijo del item_key
     * ('admin.entity.{label}'), igual que ya se usa como countsFrom en
     * useDashboardQuickLinks.ts.
     */
    private function filterEntityCounts(array $entityCounts, DashboardWidgetFilter $filter): array
    {
        $permissions = [
            'Administradores' => null,
            'Comercios'       => 'commerces.view',
            'Vendedores'      => 'sellers.view',
            'Clientes'        => 'customers.view',
            'Jugadores'       => null,
            'Tesoreros'       => 'treasury.view',
        ];

        return collect($entityCounts)
            ->filter(fn ($entity) => $filter->isVisible("admin.entity.{$entity['label']}", $permissions[$entity['label']] ?? null))
            ->values()
            ->all();
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
