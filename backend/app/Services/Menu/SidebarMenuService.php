<?php

namespace App\Services\Menu;

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

class SidebarMenuService
{
    /**
     * Estructura completa del menú con permisos requeridos
     */
    public array $menuStructure = [
        'dashboard' => [
            'title' => 'Tablero',
            'icon' => 'fas fa-tachometer-alt',
            'route' => 'dashboard',
            'permission' => null, // Accesible para todos
        ],
        'directory' => [
            'title' => 'Directorio',
            'icon' => 'fas fa-address-book',
            'permission' => null, // Accesible para todos
            'route' => 'directory.index',
        ],
        'wallet' => [
            'title' => 'Billetera',
            'icon' => 'fas fa-wallet',
            'permission' => 'wallet.view',
            'route' => 'wallet.index',
        ], 
        'exchange_rates' => [
            'title' => 'Tasa de Cambio',
            'icon' => 'fas fa-exchange-alt',
            'permission' => 'exchange.view',
            'exclude_roles' => ['player', 'seller'],
            'route' => 'exchange-rate.index',
        ],
        'users' => [
            'title' => 'Usuarios',
            'icon' => 'fa-solid fa-users-gear',
            'permission' => 'users.view',
            'submenu' => [
                'permissions' => [
                    'title' => 'Permisos',
                    'route' => 'permissions.index',
                    'permission' => 'superadmin.access',
                ],
                'all_users' => [
                    'title' => 'Gestión de Usuarios',
                    'route' => 'admin.users',
                    'permission' => 'users.manage',
                ],
            ],
        ],
        'prizes' => [
            'title' => 'Gestión de Premios',
            'icon' => 'fas fa-trophy',
            'permission' => 'prizes.view',
            'exclude_roles' => ['player', 'seller'],
            'route' => 'prizes.index',
        ],
        'game_bingo' => [
            'title' => 'Bingo',
            'icon' => 'fas fa-gamepad',
            'permission' => 'game.bingo', // Acceso específico a juegos de bingo
            'exclude_roles' => ['superadmin'], // Superadmin no ve este menú
            'submenu' => [
                'game_rooms' => [
                    'title' => 'Salas de Bingo',
                    'route' => 'bingo-game-rooms.index',
                    'permission' => 'game.bingo.game_rooms', // Acceso específico a juegos de bingo
                ],
            ],
        ],
        'games' => [
            'title' => 'Gestión de Juegos',
            'icon' => 'fas fa-gamepad',
            'permission' => 'games.view',
            'submenu' => [
                'bingo_rooms' => [
                    'title' => 'Salas de Bingo',
                    'route' => 'bingo-rooms.index',
                    'permission' => 'games.view',
                ],
            ],
        ],
        'sales_management' => [
            'title' => 'Gestión de Ventas',
            'icon' => 'fa-solid fa-comments-dollar',
            'permission' => 'sellers.view,customers.view', // Accesible para commerce, customer, seller
            'submenu' => [
                'my_sellers' => [
                    'title' => 'Mis Vendedores',
                    'route' => 'manager-seller.index',
                    'permission' => 'sellers.view',
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

        // Obtener roles del usuario
        $userRoles = $user->getRoleNames()->toArray();

        // Si es superadmin, mostrar todo excepto menús excluidos explícitamente
        if ($user->hasRole('superadmin')) {
            return collect($this->menuStructure)->filter(function ($item) use ($userRoles) {
                // Verificar si el menú tiene roles excluidos
                if (!empty($item['exclude_roles'])) {
                    $excludedRoles = $item['exclude_roles'];
                    // Si el usuario tiene algún rol excluido, no mostrar el menú
                    if (array_intersect($userRoles, $excludedRoles)) {
                        return false;
                    }
                }
                return true;
            });
        }

        // Obtener permisos del usuario una sola vez
        $userPermissions = $user->getAllPermissions()->pluck('name')->toArray();

        // Filtrar menú basado en permisos
        return collect($this->menuStructure)->filter(function ($item) use ($user, $userPermissions) {
            // Verificar si el menú tiene roles excluidos
            if (!empty($item['exclude_roles'])) {
                $userRoles = $user->getRoleNames()->toArray();
                $excludedRoles = $item['exclude_roles'];
                if (array_intersect($userRoles, $excludedRoles)) {
                    return false;
                }
            }

            // Si no requiere permiso, mostrar siempre
            if (empty($item['permission'])) {
                return true;
            }

            // Verificar si tiene el permiso (usando array en lugar de can())
            if (in_array($item['permission'], $userPermissions)) {
                // Filtrar submenú si existe
                if (isset($item['submenu'])) {
                    $item['submenu'] = collect($item['submenu'])->filter(function ($subitem) use ($userPermissions) {
                        return empty($subitem['permission']) || in_array($subitem['permission'], $userPermissions);
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
