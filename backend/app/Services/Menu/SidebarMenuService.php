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
        // ── Uso diario (todos los roles) ──────────────────────────────
        'dashboard' => [
            'title' => 'Tablero',
            'icon' => 'fas fa-tachometer-alt',
            'route' => 'dashboard',
            'permission' => null, // Accesible para todos
        ],
        // Billetera agrupa Directorio (accesible a todos) junto con el manejo
        // de dinero del usuario; cada subitem se autofiltra por su propio
        // permiso, así que el padre no exige ninguno en particular.
        'wallet' => [
            'title' => 'Billetera',
            'icon' => 'fas fa-wallet',
            'submenu' => [
                'directory' => [
                    'title' => 'Directorio',
                    'route' => 'directory.index',
                    'permission' => null, // Accesible para todos
                ],
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
                'movement_types' => [
                    'title' => 'Tipos de Movimiento',
                    'route' => 'movement-types.index',
                    'permission' => 'movement-types.view',
                ],
            ],
        ],
        // ── Juegos (operación diaria de juego) ────────────────────────
        // Un solo menú por juego: Bingo agrupa la administración de salas
        // (admin/superadmin) y la operación de venta (commerce/subsidiary/
        // seller/customer/player). Cada subitem se autofiltra por su propio
        // permiso/exclude_roles.
        'games_bingo' => [
            'title' => 'Bingo',
            'icon' => 'fas fa-dice',
            'submenu' => [
                'bingo_rooms' => [
                    'title' => 'Gestionar Salas',
                    'route' => 'bingo-rooms.index',
                    'permission' => 'games.view',
                ],
                'game_rooms' => [
                    'title' => 'Salas de Bingo',
                    'route' => 'bingo-game-rooms.index',
                    'permission' => 'games.bingo.game_rooms',
                ],
                'my_games' => [
                    'title'         => 'Mis Partidas',
                    'route'         => 'bingo.my-games',
                    'permission'    => null,
                    'exclude_roles' => ['superadmin', 'admin', 'seller', 'commerce', 'subsidiary', 'customer'],
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

        // ── Gestión de negocio ─────────────────────────────────────────
        'business' => [
            'title' => 'Negocio',
            'icon' => 'fa-solid fa-briefcase',
            'submenu' => [
                'exchange_rates' => [
                    'title' => 'Tasa de Cambio',
                    'route' => 'exchange-rate.index',
                    'permission' => 'exchange.view',
                    'exclude_roles' => ['player', 'seller'],
                ],
                'my_sellers' => [
                    'title' => 'Mis Vendedores',
                    'route' => 'manager-seller.index',
                    'permission' => 'sellers.view',
                    'exclude_roles' => ['superadmin', 'admin', 'subsidiary', 'seller', 'player'], // visible solo para commerce y customer
                ],
                'prizes' => [
                    'title' => 'Gestión de Premios',
                    'route' => 'prizes.index',
                    'permission' => 'prizes.view',
                    'exclude_roles' => ['player', 'seller'],
                ],
                'win_events' => [
                    'title' => 'Eventos de Ganancia',
                    'route' => 'admin.win-event.index',
                    'permission' => 'win-events.manage',
                    'exclude_roles' => ['player', 'seller', 'commerce', 'customer'],
                ],
            ],
        ],

        // ── Administración del sistema ──────────────────────────────────
        'users' => [
            'title' => 'Usuarios',
            'icon' => 'fa-solid fa-users-gear',
            'permission' => 'users.view',
            'submenu' => [
                'permissions' => [
                    'title' => 'Permisos',
                    'route' => 'permissions.index',
                    'permission' => 'superadmin.access', // ya restringe a superadmin, exclude_roles no es necesario aquí
                ],
                'all_users' => [
                    'title' => 'Listado',
                    'route' => 'admin.users',
                    'permission' => 'users.manage',
                ],
            ],
        ],
        'system' => [
            'title' => 'Sistema',
            'icon' => 'fas fa-cogs',
            'submenu' => [
                'games_catalog' => [
                    'title' => 'Catálogo de Juegos',
                    'route' => 'games.index',
                    'permission' => 'games.manage',
                ],
                'settings' => [
                    'title' => 'Configuración',
                    'route' => 'settings.index',
                    'permission' => 'settings.manage',
                    'exclude_roles' => ['player', 'seller', 'commerce', 'customer'],
                ],
                'audit' => [
                    'title' => 'Auditoría',
                    'route' => 'audit.index',
                    'permission' => 'audit.view',
                    'exclude_roles' => ['player', 'seller', 'commerce', 'customer'],
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
            : $user->getAllPermissionNames();

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
     * Cuenta los movimientos de billetera pendientes de revisión: cualquier
     * tipo con requires_approval=true en el catálogo MovementType (incluye
     * DEPOSIT/WITHDRAWAL y cualquier tipo custom aprobable creado por admin).
     */
    public function pendingWalletMovementsCount(): int
    {
        $approvableTypes = \App\Models\MovementType::catalog()
            ->filter(fn ($type) => $type->requires_approval)
            ->pluck('code');

        return WalletMovement::whereIn('type', $approvableTypes)
            ->where('status', WalletMovement::STATUS_PENDING)
            ->count();
    }

    /**
     * Construye el rastro de breadcrumbs según la ruta activa.
     */
    public function getBreadcrumb(): array
    {
        $breadcrumb = [
            ['title' => 'Tablero', 'route' => 'dashboard'],
        ];

        foreach ($this->menuStructure as $item) {
            if (isset($item['submenu'])) {
                foreach ($item['submenu'] as $subitem) {
                    if (!empty($subitem['route']) && $this->isActive($subitem['route'])) {
                        $breadcrumb[] = ['title' => $item['title'], 'route' => null];
                        $breadcrumb[] = ['title' => $subitem['title'], 'route' => $subitem['route']];
                        return $breadcrumb;
                    }
                }
                continue;
            }

            if (!empty($item['route']) && $this->isActive($item['route']) && $item['route'] !== 'dashboard') {
                $breadcrumb[] = ['title' => $item['title'], 'route' => $item['route']];
                return $breadcrumb;
            }
        }

        return $breadcrumb;
    }
}
