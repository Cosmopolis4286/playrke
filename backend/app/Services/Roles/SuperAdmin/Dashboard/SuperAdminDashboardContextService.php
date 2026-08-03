<?php

namespace App\Services\Roles\SuperAdmin\Dashboard;

use App\DTOs\Roles\SuperAdmin\Dashboard\SuperAdminDashboardContextDTO;
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
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

final class SuperAdminDashboardContextService
{
    use BuildsRecentActivity;
    use BuildsSalesTrend;
    use BuildsWeeklyTrend;

    private const CACHE_TTL_SECONDS = 90;

    public function __construct(
        private readonly SidebarMenuService $sidebarMenu,
    ) {}

    public function dashboardContext(): SuperAdminDashboardContextDTO
    {
        $user   = Auth::user();
        $now    = Carbon::now();
        $period = ucfirst($now->translatedFormat('F Y'));

        $cached = Cache::remember('dashboard:superadmin:aggregates', self::CACHE_TTL_SECONDS, function () {
            $activeRooms   = BingoRoom::active()->count();
            $activeAdmins  = User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->active()->count();
            $activeCommerce = Commerce::where('status', 'ACTIVE')->count();

            return [
                'stats'          => $this->buildStats($activeRooms, $activeAdmins, $activeCommerce),
                'recentActivity' => $this->buildRecentActivity(excludeSystemAccounts: true),
                'adminActivity'  => $this->buildAdminActivity(),
                'entityCounts'   => $this->buildEntityCounts(),
                'systemHealth'   => $this->buildSystemHealth($activeRooms),
                'salesTrend'     => $this->buildSalesTrend(),
            ];
        });

        return new SuperAdminDashboardContextDTO(
            userName:           $user?->first_name ?? $user?->username ?? 'SuperAdmin',
            stats:              $cached['stats'],
            recentActivity:     $cached['recentActivity'],
            adminActivity:      $cached['adminActivity'],
            entityCounts:       $cached['entityCounts'],
            systemHealth:       $cached['systemHealth'],
            salesTrend:         $cached['salesTrend'],
            pendingWalletCount: $this->sidebarMenu->pendingWalletMovementsCount(),
            period:             $period,
        );
    }

    // -------------------------------------------------------------------------
    // Stats principales para SuperAdmin
    // -------------------------------------------------------------------------

    private function buildStats(int $activeRooms, int $activeAdmins, int $activeCommerce): array
    {
        $totalUsers       = User::excludeSystemAccounts()->count();
        $totalAdmins      = Admin::count();
        $activeSystems    = $this->countActiveSystems($activeRooms, $activeAdmins, $activeCommerce);
        $totalPermissions = Permission::count();

        return [
            'totalUsers' => array_merge([
                'label'       => 'Usuarios totales',
                'value'       => $totalUsers,
                'icon'        => 'fas fa-users',
                'variant'     => 'primary',
                'href'        => '/admin/users',
            ], $this->buildWeeklyTrend(User::excludeSystemAccounts())),
            'totalAdmins' => [
                'label'   => 'Administradores',
                'value'   => $totalAdmins,
                'icon'    => 'fas fa-user-shield',
                'variant' => 'danger',
                'href'    => '/admins',
            ],
            'activeSystems' => [
                'label'       => 'Sistemas activos',
                'value'       => $activeSystems,
                'icon'        => 'fas fa-server',
                'variant'     => 'info',
                'description' => "de 3 monitoreados",
            ],
            'totalPermissions' => [
                'label'   => 'Permisos totales',
                'value'   => $totalPermissions,
                'icon'    => 'fas fa-key',
                'variant' => 'warning',
                'href'    => '/permissions',
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Conteos de entidades por rol (extendido para SuperAdmin)
    // -------------------------------------------------------------------------

    private function buildEntityCounts(): array
    {
        $playersCount = User::role('player')->count();

        return [
            ['label' => 'Administradores', 'value' => Admin::count(),     'icon' => 'fas fa-user-shield',   'variant' => 'danger',    'href' => '/admins'],
            ['label' => 'Comercios',       'value' => Commerce::count(),  'icon' => 'fas fa-building',      'variant' => 'primary',   'href' => '/admin/commerces'],
            ['label' => 'Vendedores',      'value' => Seller::count(),    'icon' => 'fas fa-user-tie',      'variant' => 'success',   'href' => '/admin/sellers'],
            ['label' => 'Clientes',        'value' => Customer::count(),  'icon' => 'fas fa-handshake',     'variant' => 'info',      'href' => '/admin/customers'],
            ['label' => 'Jugadores',       'value' => $playersCount,      'icon' => 'fas fa-gamepad',       'variant' => 'secondary', 'href' => '/admin/players'],
            ['label' => 'Tesoreros',       'value' => Treasurer::count(), 'icon' => 'fas fa-piggy-bank',    'variant' => 'warning',   'href' => '/admin/treasurers'],
        ];
    }

    // -------------------------------------------------------------------------
    // Actividad de administradores
    // -------------------------------------------------------------------------

    private function buildAdminActivity(): array
    {
        return User::with('roles:id,name')
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['admin', 'superadmin']);
            })
            ->latest()
            ->limit(5)
            ->get(['id', 'first_name', 'last_name', 'username', 'last_login_at', 'created_at'])
            ->map(function (User $user) {
                $name = trim("{$user->first_name} {$user->last_name}") ?: $user->username;

                return [
                    'id'          => (string) $user->getKey(),
                    'type'        => 'danger',
                    'title'       => "Admin activo: {$name}",
                    'description' => $user->last_login_at
                        ? "Último acceso: " . $user->last_login_at->diffForHumans()
                        : "Sin accesos registrados",
                    'timestamp'   => $user->last_login_at?->toIso8601String() ?? $user->created_at->toIso8601String(),
                    'actor'       => $name,
                ];
            })
            ->all();
    }

    // -------------------------------------------------------------------------
    // Salud del sistema
    // -------------------------------------------------------------------------

    private function buildSystemHealth(int $activeRooms): array
    {
        $issues = 0;
        $status = 'healthy';
        $label  = 'Saludable';

        if ($activeRooms === 0) {
            $issues++;
        }

        // Admins sin login reciente (más de 30 días)
        $inactiveAdmins = User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))
            ->where(function ($q) {
                $q->whereNull('last_login_at')
                  ->orWhere('last_login_at', '<', Carbon::now()->subDays(30));
            })
            ->count();
        if ($inactiveAdmins > 0) {
            $issues += $inactiveAdmins;
        }

        // Jobs fallidos en cola (tabla failed_jobs, driver database)
        $failedJobsCount = DB::table('failed_jobs')->count();
        if ($failedJobsCount > 0) {
            $issues += $failedJobsCount;
        }

        $broadcastLive = config('broadcasting.default') === 'reverb';

        if ($issues > 5) {
            $status = 'critical';
            $label  = 'Crítico';
        } elseif ($issues > 0) {
            $status = 'warning';
            $label  = 'Advertencias';
        }

        return [
            'status'          => $status,
            'label'           => $label,
            'issues'          => $issues,
            'failedJobsCount' => $failedJobsCount,
            'broadcastLive'   => $broadcastLive,
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function countActiveSystems(int $activeRooms, int $activeAdmins, int $activeCommerce): int
    {
        $count = 0;

        if ($activeRooms > 0) {
            $count++;
        }

        if ($activeAdmins > 0) {
            $count++;
        }

        if ($activeCommerce > 0) {
            $count++;
        }

        return $count;
    }
}
