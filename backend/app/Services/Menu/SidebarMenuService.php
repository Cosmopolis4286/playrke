<?php

namespace App\Services\Menu;

use App\Domains\Wallet\Models\WalletMovement;
use App\Models\Module;
use App\Models\SidebarMenuVisibility;
use App\Models\User;
use App\Models\UserSidebarFavorite;
use Illuminate\Support\Collection;

class SidebarMenuService
{
    /**
     * Métodos de este servicio invocables desde `badge_counter` en
     * $menuStructure — declarar uno nuevo aquí es todo lo que hace falta
     * para que un subitem muestre un badge dinámico (ver generateMenu()).
     *
     * @var array<string, string>
     */
    private const BADGE_COUNTERS = [
        'pendingWalletMovementsCount' => 'pendingWalletMovementsCount',
        'pendingBingoCardPaymentsCount' => 'pendingBingoCardPaymentsCount',
    ];
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
        // ── Juegos (operación diaria de juego, flujo de venta hacia el player) ──
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
                    // games.bingo.manage (no games.view): antes exigía
                    // games.view, permiso que SOLO tiene admin — commerce/
                    // subsidiary/customer nunca veían este ítem pese a que la
                    // ruta bingo-rooms.* ya los autoriza (ver
                    // RolePermissionSeeder + BingoRoomPolicy). games.view
                    // quedó de un menú viejo "Gestión de Juegos" que agrupaba
                    // solo-admin; el submenú se reorganizó pero el permiso
                    // nunca se actualizó para reflejarlo.
                    'permission' => 'games.bingo.manage',
                ],
                'game_rooms' => [
                    'title' => 'Salas de Bingo',
                    'route' => 'bingo-game-rooms.index',
                    'permission' => 'games.bingo.game_rooms',
                ],
                'generate_card' => [
                    'title' => 'Generar Cartón',
                    'route' => 'bingo-card.view-generate-card',
                    // sellers.manage-own: solo el rol seller lo tiene (mismo
                    // permiso que "Mi Perfil" del vendedor) — la venta a
                    // jugadores externos es exclusiva del seller
                    // (BingoCardGenerateRequest::authorize() solo admite
                    // 'seller'). games.bingo/game_rooms no sirven aquí: también
                    // los tiene 'player'.
                    'permission' => 'sellers.manage-own',
                ],
                'card_payments_review' => [
                    'title' => 'Revisión de Pagos',
                    'route' => 'bingo-card-payments.index',
                    // Solo quien puede revisar (admin: todo; commerce: su
                    // red — ver BingoCardPaymentReviewController). Antes la
                    // cola no tenía ítem: solo se llegaba escribiendo la URL.
                    'permission' => 'games.bingo.payments.review',
                    'badge_counter' => 'pendingBingoCardPaymentsCount',
                ],
                'my_games' => [
                    'title' => 'Mis Partidas',
                    'route' => 'bingo.my-games',
                    'permission' => null,
                ],
                'bingo_cards_sold' => [
                    'title' => 'Cartones',
                    'route' => 'bingo.cards-sold',
                    // games.bingo.sales_report (no null): la ruta real es un
                    // reporte de VENTAS de cartones ("Reporte de Cartones
                    // Vendidos", BingoReportController::cardsSold() ya
                    // bloquea 'player' con abort_if), no "mis cartones" — con
                    // permission=null cualquier rol veía este ítem aunque la
                    // ruta ahora exige games.manage,games.bingo.sales_report
                    // explícitamente (antes solo games.bingo, el mismo hueco
                    // que bingo.winners/sales-report: visible por URL a
                    // cualquiera con acceso general a jugar bingo).
                    'permission' => 'games.bingo.sales_report',
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

        // ── Mi Cuenta: perfil propio según el rol (subsidiary/commerce/
        // seller), separado de "Negocio" (gestión de red de terceros) —
        // antes ambos vivían mezclados en un solo submenú de 10 items sin
        // orden claro. Cada subitem se autofiltra por su propio permiso
        // *-manage-own, así que el padre no exige ninguno en particular
        // (mismo criterio que "Billetera"). ────────────────────────────
        'my_account' => [
            'title' => 'Mi Cuenta',
            'icon' => 'fa-solid fa-id-card',
            'submenu' => [
                'my_profile_subsidiary' => [
                    'title' => 'Mi Agencia',
                    'route' => 'subsidiary.profile.show',
                    'permission' => 'subsidiaries.manage-own',
                ],
                'my_profile_commerce' => [
                    'title' => 'Mi Comercio',
                    'route' => 'commerce.profile.show',
                    'permission' => 'commerces.manage-own',
                ],
                'commerce_economy_summary' => [
                    'title' => 'Resumen Financiero',
                    'route' => 'commerce.economy-summary.index',
                    'permission' => 'commerces.manage-own',
                ],
                'my_profile_seller' => [
                    'title' => 'Mi Perfil',
                    'route' => 'seller.profile.show',
                    'permission' => 'sellers.manage-own',
                ],
                'my_profile_customer' => [
                    'title' => 'Mi Cliente',
                    'route' => 'customer.profile.show',
                    'permission' => 'customers.manage-own',
                ],
                'my_profile_treasurer' => [
                    'title' => 'Mi Tesorería',
                    'route' => 'treasurer.profile.show',
                    'permission' => 'treasurers.manage-own',
                ],
            ],
        ],

        // ── Negocio: gestión de la red de terceros (vendedores, sucursales,
        // jugadores externos) y operación de premios/eventos — ya no mezcla
        // perfil propio (ver "Mi Cuenta" arriba). ──────────────────────
        'business' => [
            'title' => 'Negocio',
            'icon' => 'fa-solid fa-briefcase',
            'submenu' => [
                'exchange_rates' => [
                    'title' => 'Tasa de Cambio',
                    'route' => 'exchange-rate.index',
                    'permission' => 'exchange.view',
                ],
                'my_sellers' => [
                    'title' => 'Mis Vendedores',
                    'route' => 'manager-seller.index',
                    'permission' => 'sellers.view',
                ],
                'my_subsidiaries' => [
                    'title' => 'Mis Sucursales',
                    'route' => 'admin.subsidiaries',
                    'permission' => 'subsidiaries.view',
                ],
                'my_external_players' => [
                    'title' => 'Jugadores Externos',
                    'route' => 'admin.external-players.index',
                    'permission' => 'external-players.view',
                ],
                'prizes' => [
                    'title' => 'Gestión de Premios',
                    'route' => 'prizes.index',
                    'permission' => 'prizes.view',
                ],
                'win_events' => [
                    'title' => 'Eventos de Ganancia',
                    'route' => 'admin.win-event.index',
                    'permission' => 'win-events.manage',
                ],
            ],
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
                'usdt_deposit' => [
                    'title' => 'Depositar USDT',
                    'route' => 'wallet.usdt-deposit',
                    'permission' => 'wallet.deposit',
                    'enabled_config' => 'payments.usdt_deposits.enabled',
                ],
                // Histórico de comisiones propias (vendedor/agencia/comercializadora/
                // creador). Solo roles de negocio: ver CommissionLedgerPolicy.
                'my_commissions' => [
                    'title' => 'Mis Comisiones',
                    'route' => 'commissions.index',
                    'permission' => 'commissions.view-own',
                ],
                'movements' => [
                    'title' => 'Movimientos',
                    'route' => 'wallet-movements.index',
                    'permission' => 'wallet.movements.view',
                    // Recalculado también en tiempo real por
                    // PendingMovementsBadge.vue vía websocket (ver
                    // sidebar-dynamic.blade.php) — este valor es solo el
                    // conteo inicial al cargar la página.
                    'badge_counter' => 'pendingWalletMovementsCount',
                ],
                'usdt_deposit_review' => [
                    'title' => 'Revisión USDT',
                    'route' => 'usdt-deposits.review',
                    'permission' => 'wallet.movements.manage',
                    'enabled_config' => 'payments.usdt_deposits.enabled',
                ],
                'deposit_accounts' => [
                    'title' => 'Cuentas para Depósitos',
                    'route' => 'deposit-accounts.index',
                    'permission' => 'wallet.movements.manage',
                    'group' => 'Configuración',
                ],
                'movement_types' => [
                    'title' => 'Tipos de Movimiento',
                    'route' => 'movement-types.index',
                    'permission' => 'movement-types.view',
                    'group' => 'Configuración',
                ],
                'wallet_limits' => [
                    'title' => 'Límites de Billetera',
                    'route' => 'wallet-limits.index',
                    'permission' => 'wallet-limits.view',
                    'group' => 'Configuración',
                ],
            ],
        ],

        // Marketplace P2P: inventario y compra/venta de activos digitales
        // entre jugadores. Ver MARKETPLACE_ARCHITECTURE_AUDIT.md — grupo
        // separado de "wallet" porque no mueve dinero directamente (eso lo
        // hace el escrow internamente), es un dominio de activos/comercio.
        'marketplace' => [
            'title' => 'Marketplace',
            'icon' => 'fas fa-store',
            'submenu' => [
                'browse' => [
                    'title' => 'Explorar',
                    'route' => 'marketplace.index',
                    'permission' => 'marketplace.listings.view',
                ],
                'my_inventory' => [
                    'title' => 'Mi Inventario',
                    'route' => 'inventory.index',
                    'permission' => 'inventory.view',
                ],
                'moderation' => [
                    'title' => 'Moderación (Escrows)',
                    'route' => 'marketplace-escrows.index',
                    'permission' => 'marketplace.manage',
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
                    'permission' => 'superadmin.access',
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
                // 'group' agrupa visualmente los subitems en el sidebar (ver
                // sidebar-dynamic.blade.php) con un separador entre grupos
                // distintos — puramente cosmético, no afecta filtrado por
                // permiso/rol ni ninguna otra lógica de SidebarMenuService.
                'games_catalog' => [
                    'title' => 'Catálogo de Juegos',
                    'route' => 'games.index',
                    'permission' => 'games.manage',
                    'group' => 'Operación',
                ],
                'app-parameters' => [
                    'title' => 'Configuración',
                    'route' => 'app-parameters.index',
                    'permission' => 'app-parameters.manage',
                    'group' => 'Operación',
                ],
                'sidebar_menu' => [
                    'title' => 'Menú del Sidebar',
                    'route' => 'sidebar-menu.index',
                    // sidebar-menu.view (no superadmin.access): la ruta se
                    // migró a permisos granulares (multiPermission:
                    // sidebar-menu.view,sidebar-menu.manage — ver migración
                    // 2026_09_27_000001 y routes/modules/admin.php) para que
                    // el acceso sea delegable a futuro sin tocar código; el
                    // ítem del menú se quedó con el permiso viejo, así que
                    // ese "delegable" nunca funcionaría hasta este fix. Hoy
                    // solo superadmin tiene sidebar-menu.view, mismo acceso
                    // efectivo que antes.
                    'permission' => 'sidebar-menu.view',
                    'group' => 'Operación',
                ],
                'dashboard_widgets' => [
                    'title' => 'Widgets del Dashboard',
                    'route' => 'dashboard-widgets.index',
                    // dashboard-widgets.view (no superadmin.access) — mismo
                    // criterio que sidebar_menu de arriba.
                    'permission' => 'dashboard-widgets.view',
                    'group' => 'Operación',
                ],
                'audit' => [
                    'title' => 'Auditoría',
                    'route' => 'audit.index',
                    'permission' => 'audit.view',
                    'group' => 'Monitoreo',
                ],
                'kyc' => [
                    'title' => 'Verificación KYC',
                    'route' => 'admin.kyc.index',
                    'permission' => 'kyc.view',
                    'group' => 'Monitoreo',
                ],
                'logs' => [
                    'title' => 'Logs del Sistema',
                    'route' => 'logs.index',
                    'permission' => 'system.logs',
                    'group' => 'Monitoreo',
                ],
                'jobs' => [
                    'title' => 'Jobs y Scheduler',
                    'route' => 'system.jobs.index',
                    'permission' => 'system.jobs',
                    'group' => 'Monitoreo',
                ],
            ],
        ],
    ];

    /**
     * Set de keys de módulos activos, cacheado en memoria por instancia
     * (una por request vía el container) — evita una query por cada ítem
     * raíz del menú al recorrer $menuStructure.
     *
     * @var array<string, bool>|null
     */
    private ?array $activeModuleKeys = null;

    /**
     * Set de "item_key:role_name" => sort_order, misma forma y mismo query
     * que $itemRoleVisibility (comparten el resultado de una sola llamada a
     * loadVisibilityAndOrder()) — null = sin override, usa el orden natural
     * de $menuStructure.
     *
     * @var array<string, int|null>|null
     */
    private ?array $itemRoleSortOrder = null;

    /**
     * Set de "item_key:role_name" => is_visible, cacheado en memoria por
     * instancia — una sola query total para todo el árbol, sin importar
     * cuántos items/roles evalúe generateMenu() (mismo patrón que
     * $activeModuleKeys/isModuleActive()).
     *
     * @var array<string, bool>|null
     */
    private ?array $itemRoleVisibility = null;

    /**
     * Genera el menú basado en los permisos del usuario actual
     */
    public function generateMenu() : Collection
    {
        $user = auth()->user();

        if (! $user) {
            return collect([]);
        }

        $userRoles = $user->getRoleNames()->toArray();
        $userPermissions = $user->hasRole('superadmin')
            ? null // superadmin no necesita verificar permisos
            : $user->getAllPermissionNames();

        return collect($this->menuStructure)
            ->map(function($item, $parentKey) use ($userRoles, $userPermissions) {
                // Filtrar submenús antes de decidir si mostrar el padre
                if (isset($item['submenu'])) {
                    $item['submenu'] = collect($item['submenu'])
                        ->filter(function($subitem, $subKey) use ($parentKey, $userRoles, $userPermissions) {
                            // Función apagada por configuración (ej. depósitos
                            // USDT sin dirección configurada): oculta para todos.
                            if (! empty($subitem['enabled_config']) && ! config($subitem['enabled_config'])) {
                                return false;
                            }
                            // Visibilidad por rol editable en BD (reemplaza
                            // exclude_roles hardcodeado — ver isVisibleForRoles()).
                            if (! $this->isVisibleForRoles("{$parentKey}.{$subKey}", $userRoles)) {
                                return false;
                            }
                            // superadmin ve todo
                            if ($userPermissions === null) {
                                return true;
                            }

                            return empty($subitem['permission'])
                                || in_array($subitem['permission'], $userPermissions);
                        })
                        ->map(function($subitem, $subKey) use ($parentKey, $userRoles) {
                            if (! empty($subitem['badge_counter'])) {
                                $subitem['badge'] = $this->resolveBadgeCounter($subitem['badge_counter']);
                            }
                            // sort_order editable por rol — null = orden
                            // natural del array (ver sortOrderForRoles()).
                            $subitem['_sortOrder'] = $this->sortOrderForRoles("{$parentKey}.{$subKey}", $userRoles);

                            return $subitem;
                        })
                        ->sortBy(fn ($subitem) => $subitem['_sortOrder'] ?? PHP_INT_MAX)
                        ->map(function($subitem) {
                            unset($subitem['_sortOrder']);

                            return $subitem;
                        })
                        ->values()
                        ->toArray();
                }

                return $item;
            })
            ->filter(function($item, $key) use ($userRoles, $userPermissions) {
                // Módulo apagado en /permissions → oculto para TODOS,
                // incluido superadmin (a diferencia de la visibilidad por rol
                // y de permission, que sí puede saltarse el superadmin). Se
                // evalúa primero y sin excepción porque es un interruptor de
                // disponibilidad del sistema, no de autorización por rol.
                if (! $this->isModuleActive($key)) {
                    return false;
                }

                // Visibilidad por rol editable en BD (reemplaza exclude_roles
                // hardcodeado — ver isVisibleForRoles()). Se evalúa para
                // todos los roles sin excepción, incluido superadmin (mismo
                // criterio que exclude_roles tenía antes).
                if (! $this->isVisibleForRoles($key, $userRoles)) {
                    return false;
                }

                // superadmin ve todo lo no excluido
                if ($userPermissions === null) {
                    // Ocultar si el submenú quedó vacío tras filtrar
                    if (isset($item['submenu'])) {
                        return ! empty($item['submenu']);
                    }

                    return true;
                }

                // Sin permiso requerido → visible siempre
                if (empty($item['permission'])) {
                    if (isset($item['submenu'])) {
                        return ! empty($item['submenu']);
                    }

                    return true;
                }

                // Verificar permiso del padre
                if (! in_array($item['permission'], $userPermissions)) {
                    return false;
                }

                // Si tiene submenú, mostrar solo si quedó al menos un ítem visible
                if (isset($item['submenu'])) {
                    return ! empty($item['submenu']);
                }

                return true;
            })
            // sort_order editable por rol a nivel raíz — mismo criterio que
            // el submenu, null = orden natural del array. sortBy() preserva
            // las claves string (necesarias para el @foreach del Blade).
            ->sortBy(fn ($item, $key) => $this->sortOrderForRoles($key, $userRoles) ?? PHP_INT_MAX);
    }

    /**
     * Construye el rastro de breadcrumbs según la ruta activa. Recorre
     * $menuStructure recursivamente (vía findActiveTrail()) en vez de
     * asumir un máximo de 2 niveles fijos — soporta cualquier profundidad
     * de 'submenu' anidado sin cambios si el árbol crece.
     */
    public function getBreadcrumb() : array
    {
        $trail = $this->findActiveTrail($this->menuStructure);

        if ($trail === null) {
            return [['title' => 'Tablero', 'route' => 'dashboard']];
        }

        return [
            ['title' => 'Tablero', 'route' => 'dashboard'],
            ...$trail,
        ];
    }

    /**
     * Accesos directos del usuario para la sección "Favoritos" del sidebar
     * (ver sidebar-dynamic.blade.php). Resuelve cada item_key guardado
     * contra el árbol YA FILTRADO de generateMenu() —no contra
     * $menuStructure crudo— para que un favorito a un item que el usuario
     * perdió por rol/permiso/módulo desactivado simplemente deje de
     * aparecer, sin dejar una fila huérfana visible ni requerir limpieza
     * de la tabla user_sidebar_favorites.
     *
     * @return array<int, array{item_key: string, title: string, url: string}>
     */
    public function getFavorites(User $user) : array
    {
        $favoriteKeys = UserSidebarFavorite::query()
            ->where('user_id', $user->id)
            ->orderBy('sort_order')
            ->pluck('item_key');

        if ($favoriteKeys->isEmpty()) {
            return [];
        }

        $visibleByKey = $this->generateMenu()
            ->flatMap(function(array $item, string $parentKey) {
                $rows = [];
                if (! empty($item['route'])) {
                    $rows[$parentKey] = ['title' => $item['title'], 'route' => $item['route']];
                }
                foreach ($item['submenu'] ?? [] as $subKey => $subitem) {
                    if (! empty($subitem['route'])) {
                        $rows["{$parentKey}.{$subKey}"] = ['title' => $subitem['title'], 'route' => $subitem['route']];
                    }
                }

                return $rows;
            });

        return $favoriteKeys
            ->filter(fn (string $key) => $visibleByKey->has($key))
            ->map(fn (string $key) => [
                'item_key' => $key,
                'title' => $visibleByKey[$key]['title'],
                'url' => route($visibleByKey[$key]['route']),
            ])
            ->values()
            ->all();
    }

    /**
     * Árbol de $menuStructure aplanado a una lista de items (padres +
     * submenús) para la matriz de administración de visibilidad por rol.
     * `permission` viaja solo como dato informativo de solo lectura — la
     * matriz no lo edita, solo edita visibilidad por rol.
     *
     * @return array<int, array{key: string, parent_key: string|null, title: string, permission: string|null}>
     */
    public function getFlatMenuItemsForMatrix() : array
    {
        $flat = [];

        foreach ($this->menuStructure as $parentKey => $parentItem) {
            $flat[] = [
                'key' => $parentKey,
                'parent_key' => null,
                'title' => $parentItem['title'],
                'permission' => $parentItem['permission'] ?? null,
            ];

            foreach ($parentItem['submenu'] ?? [] as $subKey => $subItem) {
                $flat[] = [
                    'key' => "{$parentKey}.{$subKey}",
                    'parent_key' => $parentKey,
                    'title' => $subItem['title'],
                    'permission' => $subItem['permission'] ?? null,
                ];
            }
        }

        return $flat;
    }

    /**
     * Genera las clases CSS para un ítem de menú
     */
    public function getMenuItemClasses(string $route) : string
    {
        return $this->isActive($route) ? 'nav-link active' : 'nav-link';
    }

    /**
     * Obtiene los roles del usuario para mostrar en el sidebar
     */
    public function getUserRoleDisplay() : string
    {
        $user = auth()->user();

        if (! $user) {
            return 'Invitado';
        }

        $roles = $user->getRoleNames()->map(function($role) {
            return match ($role) {
                'superadmin' => 'Super Administrador',
                'admin' => 'Administrador',
                'commerce' => 'Comercio',
                'seller' => 'Vendedor',
                'treasurer' => 'Tesorero',
                'provider' => 'Proveedor',
                'customer' => 'Cliente',
                'player' => 'Jugador',
                'external_player' => 'Jugador Externo',
                default => ucfirst($role),
            };
        });

        return $roles->implode(', ');
    }

    /**
     * Verifica si un menú padre tiene algún submenu activo.
     *
     * @param array $submenu Array de submenús
     */
    public function hasActiveSubmenu(array $submenu) : bool
    {
        foreach ($submenu as $subitem) {
            if (! empty($subitem['route']) && $this->isActive($subitem['route'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica si un ítem de menú está activo
     */
    public function isActive(string $route) : bool
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
     * Pagos de ventas a jugadores externos esperando revisión, con el mismo
     * scope que la cola (BingoCardPaymentReviewController): admin/superadmin
     * cuentan todos; un comercio, solo los de su red comercial.
     */
    public function pendingBingoCardPaymentsCount() : int
    {
        $user = auth()->user();

        if (! $user) {
            return 0;
        }

        $query = \App\Domains\Games\Bingo\Models\Card\CardPayment\BingoCardPayment::where('payment_status', 'PENDING');

        if ($user->hasRole('superadmin') || $user->hasRole('admin')) {
            return $query->count();
        }

        $network = \App\Models\Roles\Commerce::where('user_id', $user->id)->first()?->networkUserIds();

        return $network ? $query->whereIn('seller_user_id', $network)->count() : 0;
    }

    /**
     * Cuenta los movimientos de billetera pendientes de revisión: cualquier
     * tipo con requires_approval=true en el catálogo MovementType (incluye
     * DEPOSIT/WITHDRAWAL y cualquier tipo custom aprobable creado por admin).
     */
    public function pendingWalletMovementsCount() : int
    {
        $approvableTypes = \App\Models\MovementType::catalog()
            ->filter(fn ($type) => $type->requires_approval)
            ->pluck('code');

        return WalletMovement::whereIn('type', $approvableTypes)
            ->where('status', WalletMovement::STATUS_PENDING)
            ->count();
    }

    /**
     * Busca la ruta activa dentro de un nivel de items (y sus 'submenu'
     * anidados, si los hay) y devuelve el trail de breadcrumb desde ese
     * nivel hasta el item activo, o null si ninguno está activo aquí.
     *
     * @param array<string, array> $items
     *
     * @return array<int, array{title: string, route: string|null}>|null
     */
    private function findActiveTrail(array $items) : ?array
    {
        foreach ($items as $item) {
            if (isset($item['submenu'])) {
                $childTrail = $this->findActiveTrail($item['submenu']);
                if ($childTrail !== null) {
                    return [['title' => $item['title'], 'route' => null], ...$childTrail];
                }

                continue;
            }

            if (! empty($item['route']) && $item['route'] !== 'dashboard' && $this->isActive($item['route'])) {
                return [['title' => $item['title'], 'route' => $item['route']]];
            }
        }

        return null;
    }

    /**
     * Un módulo sin fila en `modules` (nunca sembrado) se considera activo
     * por defecto — la tabla es una lista de excepciones a apagar, no una
     * whitelist; así un módulo nuevo en $menuStructure no requiere migración
     * de datos para seguir siendo visible.
     */
    private function isModuleActive(string $key) : bool
    {
        if ($this->activeModuleKeys === null) {
            $this->activeModuleKeys = Module::pluck('is_active', 'key')->all();
        }

        return $this->activeModuleKeys[$key] ?? true;
    }

    /**
     * Reemplaza el filtro `exclude_roles` (antes hardcodeado en
     * $menuStructure) por la fuente de verdad editable en BD
     * (SidebarMenuVisibility). Sin fila para (itemKey, roleName) => visible
     * por defecto (lista de excepciones, no whitelist — mismo criterio que
     * isModuleActive()).
     *
     * $userRoles puede traer varios roles del mismo usuario: el item es
     * visible si lo es para AL MENOS UNO de sus roles — replica el
     * comportamiento previo de exclude_roles, donde bastaba con que ninguno
     * de los roles del usuario estuviera en la lista de exclusión.
     *
     * Se indexa por Role::name (no Role::prefix): getRoleNames(), que arma
     * $userRoles, devuelve name — prefix vive en BD en mayúsculas y no es
     * el campo que exclude_roles comparaba.
     *
     * @param string[] $userRoles
     */
    private function isVisibleForRoles(string $itemKey, array $userRoles) : bool
    {
        $this->loadVisibilityAndOrder();

        foreach ($userRoles as $roleName) {
            if ($this->itemRoleVisibility["{$itemKey}:{$roleName}"] ?? true) {
                return true;
            }
        }

        return false;
    }

    private function loadVisibilityAndOrder() : void
    {
        if ($this->itemRoleVisibility !== null) {
            return;
        }

        $rows = SidebarMenuVisibility::query()
            ->join('roles', 'roles.id', '=', 'sidebar_menu_visibilities.role_id')
            ->select(
                'sidebar_menu_visibilities.item_key',
                'roles.name',
                'sidebar_menu_visibilities.is_visible',
                'sidebar_menu_visibilities.sort_order',
            )
            ->get();

        $this->itemRoleVisibility = $rows
            ->mapWithKeys(fn ($row) => ["{$row->item_key}:{$row->name}" => (bool) $row->is_visible])
            ->all();

        $this->itemRoleSortOrder = $rows
            ->mapWithKeys(fn ($row) => ["{$row->item_key}:{$row->name}" => $row->sort_order !== null ? (int) $row->sort_order : null])
            ->all();
    }

    private function resolveBadgeCounter(string $counter) : int
    {
        $method = self::BADGE_COUNTERS[$counter] ?? null;

        return $method ? $this->{$method}() : 0;
    }

    /**
     * Orden explícito de un item para el usuario actual, o null si ningún
     * rol del usuario tiene override (queda en el orden natural del array).
     * Con varios roles y varios overrides, se usa el menor (aparece antes)
     * — mismo criterio "más permisivo gana" que isVisibleForRoles().
     *
     * @param string[] $userRoles
     */
    private function sortOrderForRoles(string $itemKey, array $userRoles) : ?int
    {
        $this->loadVisibilityAndOrder();

        $orders = [];
        foreach ($userRoles as $roleName) {
            $order = $this->itemRoleSortOrder["{$itemKey}:{$roleName}"] ?? null;
            if ($order !== null) {
                $orders[] = $order;
            }
        }

        return $orders === [] ? null : min($orders);
    }
}
