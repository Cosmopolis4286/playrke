<?php

namespace App\Services\Roles\SuperAdmin\Dashboard;

use App\DTOs\Roles\SuperAdmin\Dashboard\SuperAdminDashboardContextDTO;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Models\Commands\CommandLog;
use App\Models\KycDocument;
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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
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
            $activeRooms = BingoRoom::active()->count();

            return [
                'stats'          => $this->buildStats($activeRooms),
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

    private function buildStats(int $activeRooms): array
    {
        $totalUsers       = User::excludeSystemAccounts()->count();
        $totalAdmins      = Admin::count();
        $totalPermissions = Permission::count();
        $pendingKyc       = KycDocument::where('status', KycDocument::STATUS_PENDING)->count();

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
            'activeRooms' => [
                'label'       => 'Salas de Bingo activas',
                'value'       => $activeRooms,
                'icon'        => 'fas fa-dice',
                'variant'     => 'info',
                'description' => $activeRooms > 0 ? 'En operación ahora' : 'Sin salas activas',
                'href'        => '/bingo-rooms',
            ],
            'totalPermissions' => [
                'label'   => 'Permisos totales',
                'value'   => $totalPermissions,
                'icon'    => 'fas fa-key',
                'variant' => 'warning',
                'href'    => '/permissions',
            ],
            'pendingKyc' => [
                'label'       => 'KYC pendientes',
                'value'       => $pendingKyc,
                'icon'        => 'fas fa-id-card',
                'variant'     => $pendingKyc > 0 ? 'warning' : 'success',
                'description' => $pendingKyc > 0 ? 'Esperando revisión' : 'Sin pendientes',
                'href'        => '/admin/kyc',
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Conteos de entidades por rol (extendido para SuperAdmin)
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

    /**
     * Versión standalone de buildSystemHealth() para el endpoint de polling
     * (GET superadmin/system-health) — cacheada aparte con TTL corto (20s),
     * mismo criterio que checkReverbHealth(): no tiene sentido recalcular
     * jobs fallidos/sesiones activas/etc. en cada tick del polling (cada
     * 30s desde el frontend) si dos admins con el dashboard abierto a la
     * vez duplicarían el trabajo sin este caché.
     */
    public function systemHealthForPolling(): array
    {
        return Cache::remember(
            'dashboard:superadmin:system-health-poll',
            20,
            fn () => $this->buildSystemHealth(BingoRoom::active()->count())
        );
    }

    /**
     * Cada issue queda como {code, label, count} para que el frontend pueda
     * mostrar el desglose real en vez de solo un número — antes sumaba
     * inactiveAdmins/failedJobsCount al mismo contador sin decir cuál era
     * cuál. reverbHealthy se calcula aparte (checkReverbHealth(), con su
     * propio caché de 20s) para que un ping de red lento no alargue el TTL
     * de 90s del resto del dashboard.
     */
    private function buildSystemHealth(int $activeRooms): array
    {
        $issues = [];

        if ($activeRooms === 0) {
            $issues[] = ['code' => 'no_active_rooms', 'label' => 'Sin salas de Bingo activas', 'count' => 1];
        }

        // Salas con premio ganado pero aún no pagado (paid_prizes = 'N' y prize_pool > 0)
        $pendingPrizesCount = BingoRoom::withPendingPrizes()->count();
        if ($pendingPrizesCount > 0) {
            $issues[] = ['code' => 'pending_prizes', 'label' => 'Salas con premios pendientes de pago', 'count' => $pendingPrizesCount];
        }

        // Admins sin login reciente (más de 30 días)
        $inactiveAdminsCount = User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))
            ->where(function ($q) {
                $q->whereNull('last_login_at')
                  ->orWhere('last_login_at', '<', Carbon::now()->subDays(30));
            })
            ->count();
        if ($inactiveAdminsCount > 0) {
            $issues[] = ['code' => 'inactive_admins', 'label' => 'Administradores sin acceso reciente (30+ días)', 'count' => $inactiveAdminsCount];
        }

        // Jobs fallidos en cola (tabla failed_jobs)
        $failedJobsCount = DB::table('failed_jobs')->count();
        if ($failedJobsCount > 0) {
            $issues[] = ['code' => 'failed_jobs', 'label' => 'Jobs fallidos pendientes de revisión', 'count' => $failedJobsCount];
        }

        $pendingJobsCount = $this->queuePendingCount();

        $reverbHealthy = $this->checkReverbHealth();
        if (!$reverbHealthy) {
            $issues[] = ['code' => 'reverb_down', 'label' => 'Servidor de WebSockets (Reverb) no responde', 'count' => 1];
        }

        // Comandos programados cuya última ejecución fue un error — un
        // comando puede seguir "programado" (Schedule::command en
        // routes/console.php) pero fallar silenciosamente corrida tras
        // corrida si nadie mira /system/jobs; esto lo sube al dashboard.
        $failedCommandsCount = CommandLog::query()
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')
                    ->from('command_logs')
                    ->groupBy('command');
            })
            ->errors()
            ->count();
        if ($failedCommandsCount > 0) {
            $issues[] = ['code' => 'command_failed', 'label' => 'Comandos programados con última ejecución fallida', 'count' => $failedCommandsCount];
        }

        $activeSessionsCount = DB::table('sessions')
            ->where('last_activity', '>', Carbon::now()->subMinutes(15)->timestamp)
            ->count();

        $totalIssues = array_sum(array_column($issues, 'count'));

        $status = 'healthy';
        $label  = 'Saludable';
        if ($totalIssues > 5) {
            $status = 'critical';
            $label  = 'Crítico';
        } elseif ($totalIssues > 0) {
            $status = 'warning';
            $label  = 'Advertencias';
        }

        return [
            'status'              => $status,
            'label'               => $label,
            'issues'              => $totalIssues,
            'issueDetails'        => $issues,
            'failedJobsCount'     => $failedJobsCount,
            'pendingJobsCount'    => $pendingJobsCount,
            'inactiveAdminsCount' => $inactiveAdminsCount,
            'broadcastLive'       => $reverbHealthy,
            'failedCommandsCount' => $failedCommandsCount,
            'activeSessionsCount' => $activeSessionsCount,
            'checkedAt'           => Carbon::now()->toIso8601String(),
        ];
    }

    /**
     * Ping real (no solo de config) al endpoint /up interno de Reverb —
     * expuesto nativamente por laravel/reverb (protocolo Pusher). Se cachea
     * 20s aparte del bloque de 90s del resto del dashboard: un timeout de
     * red no debe alargar la espera de todo dashboardContext().
     */
    private function checkReverbHealth(): bool
    {
        if (config('broadcasting.default') !== 'reverb') {
            return false;
        }

        return Cache::remember('dashboard:superadmin:reverb-health', 20, function () {
            $options = config('broadcasting.connections.reverb.options', []);
            $host    = $options['host']   ?? null;
            $port    = $options['port']   ?? 8080;
            $scheme  = $options['scheme'] ?? 'http';

            if (!$host) {
                return false;
            }

            try {
                return Http::timeout(2)->get("{$scheme}://{$host}:{$port}/up")->successful();
            } catch (\Throwable) {
                return false;
            }
        });
    }

    /**
     * Jobs pendientes (no fallidos) en la cola default — complementa
     * failedJobsCount con "cuánto trabajo hay esperando", señal de un
     * worker caído o saturado que failed_jobs por sí solo no muestra (un
     * job atascado no cuenta como fallido hasta agotar reintentos).
     *
     * Soporta ambos drivers reales de este proyecto: Redis (si algún
     * entorno lo activa) y database (QUEUE_CONNECTION=database en el .env
     * de este repo) — antes solo contemplaba Redis, así que con la config
     * real esto siempre devolvía 0 sin importar cuántos jobs hubiera
     * realmente en la tabla `jobs`.
     */
    private function queuePendingCount(): int
    {
        if (config('queue.default') === 'database') {
            return (int) DB::table('jobs')->count();
        }

        if (config('queue.default') !== 'redis') {
            return 0;
        }

        try {
            $connection = config('queue.connections.redis.connection', 'default');
            $queueName  = config('queue.connections.redis.queue', 'default');
            $prefix     = config("database.redis.options.prefix", '');

            return (int) Redis::connection($connection)->llen("{$prefix}queues:{$queueName}");
        } catch (\Throwable) {
            return 0;
        }
    }

}
