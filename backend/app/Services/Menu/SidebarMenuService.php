<?php

namespace App\Services\Menu;

use App\Models\WalletMovement;
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
            'submenu' => [
                'my_wallet' => [
                    'title' => 'Mi Billetera',
                    'route' => 'wallet.index',
                    'permission' => 'wallet.view',
                ],
                'movements' => [
                    'title' => 'Movimientos',
                    'route' => 'wallet-movements.index',
                    'permission' => 'wallet.movements.view',
                ],
            ],
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
                    'exclude_roles' => ['!superadmin'],
                ],
                'all_users' => [
                    'title' => 'Listado',
                    'route' => 'admin.users',
                    'permission' => 'users.manage',
                ],
            ],
        ],
        'settings' => [
            'title' => 'Configuración',
            'icon' => 'fas fa-sliders-h',
            'permission' => 'settings.manage',
            'exclude_roles' => ['player', 'seller', 'commerce', 'customer'],
            'route' => 'settings.index',
        ],
        'prizes' => [
            'title' => 'Gestión de Premios',
            'icon' => 'fas fa-trophy',
            'permission' => 'prizes.view',
            'exclude_roles' => ['player', 'seller'],
            'route' => 'prizes.index',
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
        'games' => [
            'title' => 'Gestión de Juegos',
            'icon' => 'fas fa-gamepad',
            'permission' => 'games.view',
            'submenu' => [
                'games_catalog' => [
                    'title' => 'Catálogo de Juegos',
                    'route' => 'games.index',
                    'permission' => 'games.manage',
                ],
                'bingo_rooms' => [
                    'title' => 'Salas de Bingo',
                    'route' => 'bingo-rooms.index',
                    'permission' => 'games.view',
                ],
            ],
        ],
        'games_bingo' => [
            'title' => 'Bingo',
            'icon' => 'fas fa-gamepad',
            'permission' => 'games.bingo', // Acceso específico a juegos de bingo
            'exclude_roles' => ['superadmin', 'admin'], // Superadmin no ve este menú
            'submenu' => [
                'game_rooms' => [
                    'title' => 'Salas de Bingo',
                    'route' => 'bingo-game-rooms.index',
                    'permission' => 'games.bingo.game_rooms',
                ],
                'my_games' => [
                    'title'         => 'Mis Partidas',
                    'route'         => 'bingo.my-games',
                    'permission'    => null,
                    'exclude_roles' => ['seller', 'commerce', 'customer'],
                ],
                'bingo_cards_sold' => [
                    'title' => 'Cartones',
                    'route' => 'bingo.cards-sold',
                    'permission' => null,
                    'exclude_roles' => ['player'],
                ],
                'bingo_winners' => [
                    'title' => 'Ganadores',
                    'route' => 'bingo.winners',
                    'permission' => 'games.bingo.winners', 
                ],
                'bingo_sales_report' => [
                    'title' => 'Reporte de Ventas',
                    'route' => 'bingo.sales-report',
                    'permission' => 'games.bingo.sales_report', 
                ],
            ],
        ],
        'audit' => [
            'title'         => 'Auditoría',
            'icon'          => 'fas fa-history',
            'permission'    => 'audit.view',
            'exclude_roles' => ['player', 'seller', 'commerce', 'customer'],
            'route'         => 'audit.index',
        ],
        'games_trivia' => [
            'title' => 'Trivia',
            'icon' => 'fas fa-brain',
            'permission' => 'games.trivia.view', // Acceso básico a trivia
            'submenu' => [
                // Lobby: solo para rol player (puede unirse a salas)
                'play' => [
                    'title' => 'Jugar',
                    'route' => 'trivia.lobby',
                    'permission' => 'games.trivia.play',
                ],
                // Gestión de salas: solo admin/superadmin
                'rooms' => [
                    'title' => 'Gestionar Salas',
                    'route' => 'trivia-rooms-admin.index',
                    'permission' => 'games.trivia.rooms.manage',
                ],
                // Banco de preguntas: solo admin/superadmin
                'questions' => [
                    'title' => 'Banco de Preguntas',
                    'route' => 'trivia.questions.index',
                    'permission' => 'games.trivia.questions.manage',
                ],
                // Partidas: todos los que tengan games.trivia.games.view (player, admin, commerce, seller)
                'games' => [
                    'title' => 'Partidas',
                    'route' => 'trivia.games.index',
                    'permission' => 'games.trivia.games.view',
                ],
                // Clasificación: todos con leaderboard.view
                'leaderboard' => [
                    'title' => 'Clasificación',
                    'route' => 'trivia.leaderboard',
                    'permission' => 'games.trivia.leaderboard.view',
                ],
                // Estadísticas: admin, superadmin, treasurer
                'stats' => [
                    'title' => 'Estadísticas',
                    'route' => 'trivia.stats',
                    'permission' => 'games.trivia.stats.view',
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

        $userRoles       = $user->getRoleNames()->toArray();
        $userPermissions = $user->hasRole('superadmin')
            ? null // superadmin no necesita verificar permisos
            : $user->getAllPermissions()->pluck('name')->toArray();

        return collect($this->menuStructure)
            ->map(function ($item) use ($userRoles, $userPermissions) {
                // Filtrar submenús antes de decidir si mostrar el padre
                if (isset($item['submenu'])) {
                    $item['submenu'] = collect($item['submenu'])
                        ->filter(function ($subitem) use ($userRoles, $userPermissions) {
                            // exclude_roles en subitem
                            if (!empty($subitem['exclude_roles'])) {
                                if (array_intersect($userRoles, $subitem['exclude_roles'])) {
                                    return false;
                                }
                            }
                            // superadmin ve todo
                            if ($userPermissions === null) {
                                return true;
                            }
                            return empty($subitem['permission'])
                                || in_array($subitem['permission'], $userPermissions);
                        })
                        ->map(function ($subitem) {
                            if ($subitem['route'] === 'wallet-movements.index') {
                                $subitem['badge'] = $this->pendingWalletMovementsCount();
                            }
                            return $subitem;
                        })
                        ->values()
                        ->toArray();
                }
                return $item;
            })
            ->filter(function ($item) use ($userRoles, $userPermissions) {
                // exclude_roles en item padre
                if (!empty($item['exclude_roles'])) {
                    if (array_intersect($userRoles, $item['exclude_roles'])) {
                        return false;
                    }
                }

                // superadmin ve todo lo no excluido
                if ($userPermissions === null) {
                    // Ocultar si el submenú quedó vacío tras filtrar
                    if (isset($item['submenu'])) {
                        return !empty($item['submenu']);
                    }
                    return true;
                }

                // Sin permiso requerido → visible siempre
                if (empty($item['permission'])) {
                    if (isset($item['submenu'])) {
                        return !empty($item['submenu']);
                    }
                    return true;
                }

                // Verificar permiso del padre
                if (!in_array($item['permission'], $userPermissions)) {
                    return false;
                }

                // Si tiene submenú, mostrar solo si quedó al menos un ítem visible
                if (isset($item['submenu'])) {
                    return !empty($item['submenu']);
                }

                return true;
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
     * Verifica si un menú padre tiene algún submenu activo.
     *
     * @param array $submenu Array de submenús
     * @return bool
     */
    public function hasActiveSubmenu(array $submenu): bool
    {
        foreach ($submenu as $subitem) {
            if (!empty($subitem['route']) && $this->isActive($subitem['route'])) {
                return true;
            }
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

    /**
     * Cuenta los movimientos de billetera (depósitos/retiros) pendientes de revisión.
     */
    private function pendingWalletMovementsCount(): int
    {
        return WalletMovement::whereIn('type', [WalletMovement::TYPE_DEPOSIT, WalletMovement::TYPE_WITHDRAWAL])
            ->where('status', WalletMovement::STATUS_PENDING)
            ->count();
    }
}
