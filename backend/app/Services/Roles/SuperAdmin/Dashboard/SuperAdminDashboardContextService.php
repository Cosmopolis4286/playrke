<?php

namespace App\Services\Roles\SuperAdmin\Dashboard;

use App\DTOs\Roles\SuperAdmin\Dashboard\SuperAdminDashboardContextDTO;
use App\Models\Games\Bingo\Room\BingoRoom;
use App\Models\Roles\Admin;
use App\Models\Roles\Commerce;
use App\Models\Roles\Customer;
use App\Models\Roles\Seller;
use App\Models\Roles\Treasurer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;

final class SuperAdminDashboardContextService
{
    public function dashboardContext(): SuperAdminDashboardContextDTO
    {
        $user   = Auth::user();
        $now    = Carbon::now();
        $period = ucfirst($now->translatedFormat('F Y'));

        return new SuperAdminDashboardContextDTO(
            userName:       $user?->first_name ?? $user?->username ?? 'SuperAdmin',
            stats:          $this->buildStats(),
            recentActivity: $this->buildRecentActivity(),
            adminActivity:  $this->buildAdminActivity(),
            entityCounts:   $this->buildEntityCounts(),
            systemHealth:   $this->buildSystemHealth(),
            period:         $period,
        );
    }

    // -------------------------------------------------------------------------
    // Stats principales para SuperAdmin
    // -------------------------------------------------------------------------

    private function buildStats(): array
    {
        $totalUsers      = User::count();
        $totalAdmins     = Admin::count();
        $activeSystems   = $this->countActiveSystems();
        $totalPermissions = Permission::count();

        return [
            'totalUsers' => [
                'label'       => 'Usuarios totales',
                'value'       => $totalUsers,
                'icon'        => 'fas fa-users',
                'variant'     => 'primary',
            ],
            'totalAdmins' => [
                'label'   => 'Administradores',
                'value'   => $totalAdmins,
                'icon'    => 'fas fa-user-shield',
                'variant' => 'danger',
            ],
            'activeSystems' => [
                'label'       => 'Sistemas activos',
                'value'       => $activeSystems,
                'icon'        => 'fas fa-server',
                'variant'     => 'info',
            ],
            'totalPermissions' => [
                'label'   => 'Permisos totales',
                'value'   => $totalPermissions,
                'icon'    => 'fas fa-key',
                'variant' => 'warning',
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
            ['label' => 'Administradores', 'value' => Admin::count(),     'icon' => 'fas fa-user-shield',   'variant' => 'danger'],
            ['label' => 'Comercios',       'value' => Commerce::count(),  'icon' => 'fas fa-building',      'variant' => 'primary'],
            ['label' => 'Vendedores',      'value' => Seller::count(),    'icon' => 'fas fa-user-tie',      'variant' => 'success'],
            ['label' => 'Clientes',        'value' => Customer::count(),  'icon' => 'fas fa-handshake',     'variant' => 'info'],
            ['label' => 'Jugadores',       'value' => $playersCount,      'icon' => 'fas fa-gamepad',       'variant' => 'secondary'],
            ['label' => 'Tesoreros',       'value' => Treasurer::count(), 'icon' => 'fas fa-piggy-bank',    'variant' => 'warning'],
        ];
    }

    // -------------------------------------------------------------------------
    // Actividad reciente (últimos usuarios registrados)
    // -------------------------------------------------------------------------

    private function buildRecentActivity(): array
    {
        return User::with('roles:id,name')
            ->latest()
            ->limit(10)
            ->get(['id', 'first_name', 'last_name', 'username', 'status', 'created_at'])
            ->map(function (User $user) {
                $name = trim("{$user->first_name} {$user->last_name}") ?: $user->username;
                $role = $user->roles->first()?->name ?? 'sin rol';

                return [
                    'id'          => (string) $user->getKey(),
                    'type'        => $user->status === 'ACTIVE' ? 'success' : 'info',
                    'title'       => "Nuevo usuario: {$name}",
                    'description' => "Rol: " . ucfirst($role),
                    'timestamp'   => $user->created_at->toIso8601String(),
                    'actor'       => $name,
                ];
            })
            ->all();
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

    private function buildSystemHealth(): array
    {
        $issues = 0;
        $status = 'healthy';
        $label  = 'Saludable';

        // Verificar salas activas
        $activeRooms = BingoRoom::active()->count();
        if ($activeRooms === 0) {
            $issues++;
        }

        // Verificar admins sin login reciente (más de 30 días)
        $inactiveAdmins = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))
            ->where(function ($q) {
                $q->whereNull('last_login_at')
                  ->orWhere('last_login_at', '<', Carbon::now()->subDays(30));
            })
            ->count();
        if ($inactiveAdmins > 0) {
            $issues += $inactiveAdmins;
        }

        if ($issues > 5) {
            $status = 'critical';
            $label  = 'Crítico';
        } elseif ($issues > 0) {
            $status = 'warning';
            $label  = 'Advertencias';
        }

        return [
            'status' => $status,
            'label'  => $label,
            'issues' => $issues,
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function countActiveSystems(): int
    {
        $count = 0;

        // Bingo rooms activas
        if (BingoRoom::active()->count() > 0) {
            $count++;
        }

        // Al menos un admin activo
        if (User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->active()->count() > 0) {
            $count++;
        }

        // Comercios activos
        if (Commerce::where('status', 'ACTIVE')->count() > 0) {
            $count++;
        }

        return $count;
    }
}
