<?php

namespace App\Services\Menu;

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

class SidebarMenuService
{
    /**
     * Estructura completa del menú con permisos requeridos
     */
    private array $menuStructure = [
        'dashboard' => [
            'title' => 'Tablero',
            'icon' => 'fas fa-tachometer-alt',
            'route' => 'dashboard',
            'permission' => null, // Accesible para todos
        ],
        'users' => [
            'title' => 'Usuarios',
            'icon' => 'fa-solid fa-users-gear',
            'permission' => 'users.view',
            'submenu' => [
                'admins' => [
                    'title' => 'Administradores',
                    'route' => 'admins.index',
                    'permission' => 'superadmin.access',
                ],
                'permissions' => [
                    'title' => 'Permisos',
                    'route' => 'permissions.index',
                    'permission' => 'superadmin.access',
                ],
                'customers' => [
                    'title' => 'Clientes',
                    'route' => 'admin.customers',
                    'permission' => 'customers.view',
                ],
                'commerces' => [
                    'title' => 'Comercios',
                    'route' => 'admin.commerces',
                    'permission' => 'commerces.view',
                ],
                'treasurers' => [
                    'title' => 'Tesoreros',
                    'route' => 'admin.treasurers',
                    'permission' => 'treasury.view',
                ],
                'sellers' => [
                    'title' => 'Vendedores',
                    'route' => 'admin.sellers',
                    'permission' => 'sellers.view',
                ],
                'players' => [
                    'title' => 'Jugadores',
                    'route' => 'admin.players',
                    'permission' => 'users.view',
                ],
                'all_users' => [
                    'title' => 'Todos',
                    'route' => 'admin.users',
                    'permission' => 'users.manage',
                ],
            ],
        ],
        'commerces_management' => [
            'title' => 'Gestión de Comercios',
            'icon' => 'fas fa-store',
            'permission' => 'commerces.view',
            'submenu' => [
                'directory' => [
                    'title' => 'Directorio',
                    'route' => 'directory.index',
                    'permission' => 'commerces.view',
                ],
                'exchange_rates' => [
                    'title' => 'Tasa de Cambio',
                    'route' => 'exchange-rate.index',
                    'permission' => 'exchange.view',
                ],
                'prizes' => [
                    'title' => 'Premios',
                    'route' => 'prizes.index',
                    'permission' => 'games.view',
                ],
            ],
        ],
        'seller_management' => [
            'title' => 'Gestión de Ventas',
            'icon' => 'fa-solid fa-comments-dollar',
            'permission' => 'sellers.view',
            'submenu' => [
                'my_sellers' => [
                    'title' => 'Mis Vendedores',
                    'route' => 'manager-seller.index',
                    'permission' => 'sellers.view',
                ],
            ],
        ],
        'games' => [
            'title' => 'Juegos',
            'icon' => 'fas fa-gamepad',
            'permission' => 'games.view',
            'submenu' => [
                'bingo_rooms' => [
                    'title' => 'Salas de Bingo',
                    'route' => 'bingo-rooms.index',
                    'permission' => 'games.view',
                ],
                'bingo_patterns' => [
                    'title' => 'Patrones de Bingo',
                    'route' => 'bingo-patterns.index',
                    'permission' => 'games.edit',
                ],
                'game_configs' => [
                    'title' => 'Configuración de Juegos',
                    'route' => 'bingo.config.index',
                    'permission' => 'games.manage',
                ],
            ],
        ],
        /*
        'treasury' => [
            'title' => 'Tesorería',
            'icon' => 'fas fa-coins',
            'permission' => 'treasury.view',
            'submenu' => [
                'transactions' => [
                    'title' => 'Transacciones',
                    'route' => 'treasury.transactions',
                    'permission' => 'treasury.manage',
                ],
                'approvals' => [
                    'title' => 'Aprobaciones',
                    'route' => 'treasury.approvals',
                    'permission' => 'treasury.approve.transactions',
                ],
                'exchange_management' => [
                    'title' => 'Gestión de Cambio',
                    'route' => 'treasury.exchange',
                    'permission' => 'exchange.manage',
                ],
            ],
        ],
        */
        /*
        'reports' => [
            'title' => 'Reportes',
            'icon' => 'fas fa-chart-bar',
            'permission' => 'reports.view',
            'submenu' => [
                'index' => [
                    'title' => 'Todos los Reportes',
                    'route' => 'reports.index',
                    'permission' => 'reports.view',
                ],
                'export' => [
                    'title' => 'Exportar Reportes',
                    'route' => 'reports.export',
                    'permission' => 'reports.export',
                ],
            ],
        ],
        */
        /*
        'system' => [
            'title' => 'Sistema',
            'icon' => 'fas fa-cogs',
            'permission' => 'system.settings',
            'submenu' => [
                'settings' => [
                    'title' => 'Configuración',
                    'route' => 'system.settings',
                    'permission' => 'system.settings',
                ],
                'maintenance' => [
                    'title' => 'Mantenimiento',
                    'route' => 'system.maintenance',
                    'permission' => 'system.maintenance',
                ],
                'logs' => [
                    'title' => 'Logs del Sistema',
                    'route' => 'system.logs',
                    'permission' => 'system.logs',
                ],
                'backup' => [
                    'title' => 'Respaldo',
                    'route' => 'system.backup',
                    'permission' => 'system.backup',
                ],
                'audit' => [
                    'title' => 'Auditoría',
                    'route' => 'system.audit',
                    'permission' => 'audit.view',
                ],
            ],
        ],
        */
    ];

    /**
     * Genera el menú basado en los permisos del usuario actual
     */
    public function generateMenu(): Collection
    {
        $user = auth()->user();
        
        if (!$user) {
            return collect([]);
        }

        // Si es superadmin, mostrar todo
        if ($user->hasRole('superadmin')) {
            return collect($this->menuStructure);
        }

        // Filtrar menú basado en permisos
        return collect($this->menuStructure)->filter(function ($item) use ($user) {
            // Si no requiere permiso, mostrar siempre
            if (empty($item['permission'])) {
                return true;
            }

            // Verificar si tiene el permiso
            if ($user->can($item['permission'])) {
                // Filtrar submenú si existe
                if (isset($item['submenu'])) {
                    $item['submenu'] = collect($item['submenu'])->filter(function ($subitem) use ($user) {
                        return empty($subitem['permission']) || $user->can($subitem['permission']);
                    })->toArray();
                    
                    // Mostrar solo si hay submenús visibles
                    return !empty($item['submenu']);
                }
                return true;
            }

            return false;
        });
    }

    /**
     * Verifica si un ítem de menú está activo
     */
    public function isActive(string $route): bool
    {
        $currentRoute = request()->route()->getName();
        
        // Verificación exacta
        if ($currentRoute === $route) {
            return true;
        }

        // Verificación por patrón (ej: admin.* coincide con admin.users)
        if (str_ends_with($route, '.*')) {
            $prefix = str_replace('.*', '', $route);
            return str_starts_with($currentRoute, $prefix . '.');
        }

        return false;
    }

    /**
     * Genera las clases CSS para un ítem de menú
     */
    public function getMenuItemClasses(string $route): string
    {
        return $this->isActive($route) ? 'nav-link active' : 'nav-link';
    }

    /**
     * Obtiene los roles del usuario para mostrar en el sidebar
     */
    public function getUserRoleDisplay(): string
    {
        $user = auth()->user();
        
        if (!$user) {
            return 'Invitado';
        }

        $roles = $user->getRoleNames()->map(function ($role) {
            return match($role) {
                'superadmin' => 'Super Administrador',
                'admin' => 'Administrador',
                'commerce' => 'Comercio',
                'seller' => 'Vendedor',
                'treasurer' => 'Tesorero',
                'provider' => 'Proveedor',
                'customer' => 'Cliente',
                'player' => 'Jugador',
                default => ucfirst($role),
            };
        });

        return $roles->implode(', ');
    }
}
