<?php

namespace App\Services\Roles\Admin\Dashboard;

use App\DTOs\Roles\Admin\Dashboard\AdminDashboardContextDTO;
use App\Models\Games\Bingo\Card\BingoCard;
use App\Models\Games\Bingo\Room\BingoRoom;
use App\Models\Roles\Commerce;
use App\Models\Roles\Customer;
use App\Models\Roles\Seller;
use App\Models\Roles\Treasurer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

final class AdminDashboardContextService
{
    public function dashboardContext(): AdminDashboardContextDTO
    {
        $user   = Auth::user();
        $now    = Carbon::now();
        $period = ucfirst($now->translatedFormat('F Y'));

        return new AdminDashboardContextDTO(
            userName:       $user?->first_name ?? $user?->username ?? 'Admin',
            stats:          $this->buildStats($now),
            recentActivity: $this->buildRecentActivity(),
            entityCounts:   $this->buildEntityCounts(),
            period:         $period,
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

        $totalCommerce  = Commerce::where('status', 'ACTIVE')->count();
        $totalSellers   = Seller::where('status', 'ACTIVE')->count();
        $totalCustomers = Customer::where('status', 'ACTIVE')->count();

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

    // -------------------------------------------------------------------------
    // Actividad reciente (últimos 10 usuarios registrados)
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
}
