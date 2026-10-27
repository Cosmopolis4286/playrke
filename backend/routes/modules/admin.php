<?php

use App\Http\Controllers\Audit\AuditController;
use App\Http\Controllers\Audit\SystemLogController;
use App\Http\Controllers\Roles\Admin\AdminCommerceController;
use App\Http\Controllers\Roles\Admin\AdminCustomerController;
use App\Http\Controllers\Roles\Admin\AdminPlayerController;
use App\Http\Controllers\Roles\Admin\AdminSubsidiaryController;
use App\Http\Controllers\Roles\Admin\AdminTreasurerController;
use App\Http\Controllers\Roles\Admin\AdminUserController;
use App\Http\Controllers\Roles\Admin\AdminWinEventController;
use App\Http\Controllers\Roles\Admin\AntiCheatAdminController;
use App\Http\Controllers\Roles\Admin\ExternalPlayerAdminController;
use App\Http\Controllers\Roles\Admin\ImpersonationController;
use App\Http\Controllers\Roles\Admin\KycAdminController;
use App\Http\Controllers\Roles\Admin\User\UserPermissionController;
use App\Http\Controllers\Roles\AdminController;
use App\Http\Controllers\Roles\AdministratorController;
use App\Http\Controllers\Roles\DashboardWidgetVisibilityController;
use App\Http\Controllers\Roles\ModuleController;
use App\Http\Controllers\Roles\PermissionController;
use App\Http\Controllers\Roles\RoleController;
use App\Http\Controllers\Roles\SidebarMenuVisibilityController;
use App\Http\Controllers\System\JobsCenterController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Administración del Sistema
|--------------------------------------------------------------------------
| Gestión completa del panel administrativo.
| Todas protegidas por auth + role:admin salvo excepciones.
*/

Route::middleware('auth')->group(function() {
    /*
     * =========================================================
     * PANEL ADMINISTRATIVO
     * =========================================================
     */
    role_protected_routes(
        ['role:admin,superadmin'],
        'admin',
        AdminController::class,
        [
            'customers' => [
                'method' => 'get',
                'uri' => 'customers',
                'action' => 'customers',
            ],
            'commerces' => [
                'method' => 'get',
                'uri' => 'commerces',
                'action' => 'commerces',
            ],
            'players' => [
                'method' => 'get',
                'uri' => 'players',
                'action' => 'players',
            ],
            'sellers' => [
                'method' => 'get',
                'uri' => 'sellers',
                'action' => 'sellers',
            ],
            'treasurers' => [
                'method' => 'get',
                'uri' => 'treasurers',
                'action' => 'treasurers',
            ],
            'users' => [
                'method' => 'get',
                'uri' => 'users',
                'action' => 'users',
            ],
            'getUserInfo' => [
                'method' => 'get',
                'uri' => 'get-user-info/{userRole}/{exclude?}',
                'action' => 'getUserInfo',
            ],
        ],
    );

    /**
     * =========================================================
     * CRUDs ADMINISTRATIVOS
     * =========================================================
     */
    $adminCrudRoutes = [
        'store' => [
            'method' => 'post',
            'uri' => '',
            'action' => 'store',
        ],
        'fetchList' => [
            'method' => 'get',
            'uri' => 'fetch-list',
            'action' => 'fetchList',
        ],
        'show' => [
            'method' => 'get',
            'uri' => '{id}/show',
            'action' => 'show',
        ],
        'update' => [
            'method' => 'put',
            'uri' => '{id}/update',
            'action' => 'update',
        ],
        'delete' => [
            'method' => 'put',
            'uri' => '{id}/delete',
            'action' => 'delete',
        ],
        'destroy' => [
            'method' => 'delete',
            'uri' => '{id}',
            'action' => 'destroy',
        ],
        'toggleStatus' => [
            'method' => 'put',
            'uri' => '{id}/status',
            'action' => 'toggleStatus',
        ],
        'resetPassword' => [
            'method' => 'put',
            'uri' => '{id}/reset-password',
            'action' => 'resetPassword',
        ],
    ];

    /**
     * Commerce: array propio (copia de $adminCrudRoutes + export/bulk/
     * setAsDefault), mismo criterio que $playerRoutes más abajo —
     * Customer/Treasurer siguen en el CRUD genérico hasta que se les
     * aplique el mismo patrón.
     *
     * Protegido por multiPermission:commerces.* por acción (no role:admin
     * plano) — mismo patrón ya usado por KYC/external-players. Se usa
     * array_replace_recursive() sobre $adminCrudRoutes en vez de agregarle
     * 'middleware' directo a sus entradas, porque ese array es compartido
     * con customer/treasurer (deben seguir bajo role:admin, sin tocar).
     */
    $commerceRoutes = array_replace_recursive($adminCrudRoutes, [
        'fetchList' => ['middleware' => 'multiPermission:commerces.view'],
        'show' => ['middleware' => 'multiPermission:commerces.view'],
        'store' => ['middleware' => 'multiPermission:commerces.create'],
        'update' => ['middleware' => 'multiPermission:commerces.edit'],
        'toggleStatus' => ['middleware' => 'multiPermission:commerces.edit'],
        'resetPassword' => ['middleware' => 'multiPermission:commerces.edit'],
        'delete' => ['middleware' => 'multiPermission:commerces.delete'],
        'destroy' => ['middleware' => 'multiPermission:commerces.delete'],
    ]) + [
        'export' => ['method' => 'get', 'uri' => 'export',      'action' => 'export', 'middleware' => 'multiPermission:commerces.view'],
        'toggleStatusBulk' => ['method' => 'put',  'uri' => 'bulk/status', 'action' => 'toggleStatusBulk', 'middleware' => ['throttle:admin-users-bulk', 'multiPermission:commerces.edit']],
        'deleteBulk' => ['method' => 'put',  'uri' => 'bulk/delete', 'action' => 'deleteBulk', 'middleware' => ['throttle:admin-users-bulk', 'multiPermission:commerces.delete']],
        // commerces.set-default es exclusivo de superadmin (declarado en
        // PermissionSeeder, deliberadamente NO asignado a admin en
        // RolePermissionSeeder) — marcar/desmarcar el comercio vinculado a
        // toda la app es más sensible que una edición normal de comercio.
        'setAsDefault' => ['method' => 'put',  'uri' => '{id}/set-as-default', 'action' => 'setAsDefault', 'middleware' => 'multiPermission:commerces.set-default'],
        'unsetAsDefault' => ['method' => 'put',  'uri' => '{id}/unset-as-default', 'action' => 'unsetAsDefault', 'middleware' => 'multiPermission:commerces.set-default'],
    ];

    // Rol vacío ([]): role_protected_routes ya antepone 'auth' siempre, y
    // cada entrada de $commerceRoutes ya trae su propio multiPermission —
    // agregar un permiso a nivel de grupo lo apilaría sobre el de cada
    // acción individual (ej. store exigiría view Y create a la vez).
    role_protected_routes([], 'admin/commerce', AdminCommerceController::class, $commerceRoutes, 'commerce');

    role_protected_routes(['role:admin'], 'admin/customer', AdminCustomerController::class, $adminCrudRoutes, 'customer');
    role_protected_routes(['role:admin'], 'admin/treasurer', AdminTreasurerController::class, $adminCrudRoutes, 'treasurer');

    /**
     * Subsidiary: mismo patrón que $commerceRoutes (multiPermission:
     * subsidiaries.* por acción, no role:admin plano) — compartido entre
     * admin y commerce, ya que un commerce debe poder gestionar SUS PROPIAS
     * subsidiaries (scoping real en AdminSubsidiaryPolicy::ownsSubsidiary()
     * + AdminSubsidiaryContextService).
     */
    $subsidiaryRoutes = array_replace_recursive($adminCrudRoutes, [
        'fetchList' => ['middleware' => 'multiPermission:subsidiaries.view'],
        'show' => ['middleware' => 'multiPermission:subsidiaries.view'],
        'store' => ['middleware' => 'multiPermission:subsidiaries.create'],
        'update' => ['middleware' => 'multiPermission:subsidiaries.edit'],
        'toggleStatus' => ['middleware' => 'multiPermission:subsidiaries.edit'],
        'resetPassword' => ['middleware' => 'multiPermission:subsidiaries.edit'],
        'delete' => ['middleware' => 'multiPermission:subsidiaries.delete'],
        'destroy' => ['middleware' => 'multiPermission:subsidiaries.delete'],
    ]);

    role_protected_routes([], 'admin/subsidiary', AdminSubsidiaryController::class, $subsidiaryRoutes, 'subsidiary');

    // Índice Inertia (listado de sucursales) — extraído del grupo genérico
    // role:admin,superadmin de AdminController de arriba porque ahora debe
    // ser accesible también a commerce (multiPermission:subsidiaries.view),
    // mismo criterio que admin/external-players (línea de abajo).
    Route::middleware('multiPermission:subsidiaries.view')
        ->get('admin/subsidiaries', [AdminController::class, 'subsidiaries'])
        ->name('admin.subsidiaries');

    /**
     * Rutas de jugadores: array propio (copia de $adminCrudRoutes + export/
     * bulk) en vez de reusar el compartido — agregar acciones directamente a
     * $adminCrudRoutes afectaría también a commerce/customer/treasurer, que
     * no las necesitan. Mismo patrón de bulk que admin/user (throttle
     * dedicado, uri 'bulk/status' y 'bulk/delete' sin conflicto con '{id}'
     * porque Laravel resuelve por segmento literal antes que por parámetro).
     */
    $playerRoutes = $adminCrudRoutes + [
        'export' => ['method' => 'get', 'uri' => 'export',      'action' => 'export'],
        'toggleStatusBulk' => ['method' => 'put',  'uri' => 'bulk/status', 'action' => 'toggleStatusBulk', 'middleware' => 'throttle:admin-users-bulk'],
        'deleteBulk' => ['method' => 'put',  'uri' => 'bulk/delete', 'action' => 'deleteBulk', 'middleware' => 'throttle:admin-users-bulk'],
    ];
    role_protected_routes(['role:admin'], 'admin/player', AdminPlayerController::class, $playerRoutes, 'player');

    // Rutas de usuarios (sin store/show — la creación se delega al flujo del rol).
    // Protegidas por multiPermission:users.* por acción (no role:admin plano) —
    // mismo patrón ya usado por Commerce/KYC/external-players. users.view/create/
    // edit/delete/manage ya estaban sembrados en PermissionSeeder pero nunca se
    // usaban como middleware real.
    $userRoutes = [
        'index' => ['method' => 'get',    'uri' => '',              'action' => 'index', 'middleware' => 'multiPermission:users.view'],
        'fetch-list' => ['method' => 'get',    'uri' => 'fetch-list',     'action' => 'fetchList', 'middleware' => 'multiPermission:users.view'],
        'export' => ['method' => 'get',    'uri' => 'export',        'action' => 'export', 'middleware' => 'multiPermission:users.view'],
        'show' => ['method' => 'get',    'uri' => '{user}/show',   'action' => 'show', 'middleware' => 'multiPermission:users.view'],
        'auditHistory' => ['method' => 'get',    'uri' => '{user}/audit-history', 'action' => 'auditHistory', 'middleware' => 'multiPermission:users.view'],
        'walletHistory' => ['method' => 'get',    'uri' => '{user}/wallet-history', 'action' => 'walletHistory', 'middleware' => 'multiPermission:users.view'],
        'update' => ['method' => 'patch',  'uri' => '{user}',        'action' => 'update', 'middleware' => 'multiPermission:users.edit'],
        'toggleStatus' => ['method' => 'put',    'uri' => '{user}/status',  'action' => 'toggleStatus', 'middleware' => 'multiPermission:users.edit'],
        'toggleStatusBulk' => ['method' => 'put', 'uri' => 'bulk/status',   'action' => 'toggleStatusBulk', 'middleware' => ['throttle:admin-users-bulk', 'multiPermission:users.edit']],
        'deleteBulk' => ['method' => 'put',    'uri' => 'bulk/delete',   'action' => 'deleteBulk', 'middleware' => ['throttle:admin-users-bulk', 'multiPermission:users.delete']],
        'delete' => ['method' => 'put',    'uri' => '{user}/delete',  'action' => 'delete', 'middleware' => 'multiPermission:users.delete'],
        'destroy' => ['method' => 'delete', 'uri' => '{user}',              'action' => 'destroy', 'middleware' => 'multiPermission:users.delete'],
        'resetPassword' => ['method' => 'put',    'uri' => '{user}/reset-password', 'action' => 'resetPassword', 'middleware' => 'multiPermission:users.edit'],
        'loadWalletBalance' => ['method' => 'put', 'uri' => '{user}/load-wallet-balance', 'action' => 'loadWalletBalance', 'middleware' => 'multiPermission:users.edit'],
        'getWalletBalance' => ['method' => 'get', 'uri' => '{user}/wallet-balance', 'action' => 'getWalletBalance', 'middleware' => 'multiPermission:users.view'],
        'grantXp' => ['method' => 'put',    'uri' => '{user}/grant-xp', 'action' => 'grantXp', 'middleware' => ['throttle:admin-grant-xp', 'multiPermission:users.edit']],
    ];
    // Rol vacío ([]): role_protected_routes ya antepone 'auth' siempre, y cada
    // entrada de $userRoutes ya trae su propio multiPermission — un permiso a
    // nivel de grupo lo apilaría sobre el de cada acción (ej. update exigiría
    // view Y edit a la vez), redundante.
    role_protected_routes([], 'admin/user', AdminUserController::class, $userRoutes, 'user');

    /*
     * =========================================================
     * JUGADORES EXTERNOS (permisos external-players.*)
     * =========================================================
     * Compradores sin cuenta en la plataforma captados por un vendedor —
     * no exclusivo de Bingo, external_players.game_id admite cualquier
     * juego del catálogo (ver App\Traits\Users\PlayerLinkTrait).
     *
     * Middleware por permiso (no por rol fijo) para que admin/commerce/seller
     * puedan acceder según lo que RolePermissionSeeder les otorgue; superadmin
     * pasa siempre vía RoleAuthorizationService. La autorización fina por
     * acción vive en ExternalPlayerPolicy.
     */
    role_protected_routes(['multiPermission:external-players.view'], 'admin/external-players', ExternalPlayerAdminController::class, [
        'index' => ['method' => 'get', 'uri' => '',              'action' => 'index'],
        'fetchList' => ['method' => 'get', 'uri' => 'fetch-list',    'action' => 'fetchList'],
        'export' => ['method' => 'get', 'uri' => 'export',       'action' => 'export', 'middleware' => 'multiPermission:external-players.export'],
        // Rutas literales (fetch-list/export) van ANTES que cualquier ruta
        // con {externalPlayer} — un registro al revés hace que Laravel
        // capture "fetch-list"/"export" como valor del parámetro en vez de
        // matchear la ruta literal (ya mordió al proyecto 2 veces, ver
        // memoria feedback_route_order_literal_vs_param).
        'show' => ['method' => 'get', 'uri' => '{externalPlayer}',                   'action' => 'show'],
        'purchaseHistory' => ['method' => 'get', 'uri' => '{externalPlayer}/purchase-history',   'action' => 'purchaseHistory'],
        'prizeHistory' => ['method' => 'get', 'uri' => '{externalPlayer}/prize-history',      'action' => 'prizeHistory'],
        'auditHistory' => ['method' => 'get', 'uri' => '{externalPlayer}/audit-history',      'action' => 'auditHistory'],
        'toggleBlock' => ['method' => 'put', 'uri' => '{externalPlayer}/toggle-block', 'action' => 'toggleBlock', 'middleware' => 'multiPermission:external-players.block'],
        'regenerateLink' => ['method' => 'put', 'uri' => '{externalPlayer}/regenerate-link', 'action' => 'regenerateLink', 'middleware' => 'multiPermission:external-players.manage'],
        'reactivate' => ['method' => 'put', 'uri' => '{externalPlayer}/reactivate', 'action' => 'reactivate', 'middleware' => 'multiPermission:external-players.manage'],
        'convertToUser' => ['method' => 'put', 'uri' => '{externalPlayer}/convert-to-user', 'action' => 'convertToUser', 'middleware' => 'multiPermission:external-players.manage'],
    ], 'externalPlayer');

    /*
     * =========================================================
     * PERMISOS ESPECÍFICOS POR USUARIO (SOLO SUPERADMIN)
     * =========================================================
     * Distinto del grupo admin/user de arriba (multiPermission:users.*):
     * otorgar/revocar permisos directos a una persona es más sensible que
     * las operaciones CRUD normales de usuario, así que exige superadmin
     * explícitamente.
     */
    role_protected_routes(['role:superadmin'], 'admin/user', UserPermissionController::class, [
        // Nombres de ruta distintos a los de $userRoutes (arriba, mismo
        // prefijo admin/user.*): 'index'/'update' ya existen ahí y
        // route_group() reasignaría el nombre a la última ruta definida,
        // rompiendo route('admin.user.index') del listado de usuarios.
        'permissions.show' => ['method' => 'get', 'uri' => '{user}/permissions', 'action' => 'index'],
        'permissions.update' => ['method' => 'put', 'uri' => '{user}/permissions', 'action' => 'update'],
    ], 'user');

    /*
     * =========================================================
     * GESTIÓN DE ADMINISTRADORES (SOLO SUPERADMIN)
     * =========================================================
     */
    role_protected_routes(['role:superadmin'], 'admins', AdministratorController::class, [
        'index' => ['method' => 'get',    'uri' => '',                    'action' => 'index'],
        'fetchList' => ['method' => 'get',    'uri' => 'fetch-list',          'action' => 'fetchList'],
        'store' => ['method' => 'post',   'uri' => '',                     'action' => 'store'],
        'update' => ['method' => 'patch',  'uri' => '{user}',               'action' => 'update'],
        'toggleStatus' => ['method' => 'patch', 'uri' => '{user}/toggle-status',  'action' => 'toggleStatus'],
        'delete' => ['method' => 'delete', 'uri' => '{user}',               'action' => 'delete'],
        'destroy' => ['method' => 'delete', 'uri' => '{user}/force-delete',     'action' => 'destroy'],
        'resetPassword' => ['method' => 'put',    'uri' => '{user}/reset-password', 'action' => 'resetPassword'],
    ], 'user');

    /*
     * =========================================================
     * PERMISOS (Solo SuperAdmin)
     * =========================================================
     */
    role_protected_routes(['role:superadmin'], 'permissions', PermissionController::class, [
        'index' => ['method' => 'get',    'uri' => '',                          'action' => 'index'],
        'fetchList' => ['method' => 'get',    'uri' => 'fetch-list',                'action' => 'fetchList'],
        'getRolePermissions' => ['method' => 'get',    'uri' => 'roles/{role}/permissions',  'action' => 'getRolePermissions'],
        'assignPermissions' => ['method' => 'post',   'uri' => 'roles/{role}/assign',       'action' => 'assignPermissions'],
        'revokePermissions' => ['method' => 'post',   'uri' => 'roles/{role}/revoke',       'action' => 'revokePermissions'],
        'store' => ['method' => 'post',   'uri' => '',                          'action' => 'store'],
        'getRolesWithPermissions' => ['method' => 'get', 'uri' => 'roles-permissions',       'action' => 'getRolesWithPermissions'],
        // saveMatrix (PATCH 'matrix') debe declararse ANTES que update/delete
        // (PATCH/DELETE '{permission}') — {permission} es un wildcard que
        // matchea literalmente "matrix" también, y Laravel toma la primera
        // ruta que coincide por orden de declaración. Con el orden anterior,
        // PATCH /permissions/matrix se resolvía como update() con
        // {permission}="matrix" (nunca existe) -> 404 en vez de saveMatrix().
        'saveMatrix' => ['method' => 'patch',  'uri' => 'matrix',                    'action' => 'saveMatrix', 'middleware' => 'throttle:admin-permissions-matrix'],
        'update' => ['method' => 'patch',  'uri' => '{permission}',             'action' => 'update'],
        'delete' => ['method' => 'delete', 'uri' => '{permission}',             'action' => 'delete'],
        'copyToRole' => ['method' => 'post',   'uri' => 'roles/{role}/copy-from/{sourceRole}', 'action' => 'copyToRole'],
    ], 'permission');

    /*
     * =========================================================
     * MÓDULOS DEL SISTEMA (Solo SuperAdmin)
     * =========================================================
     */
    role_protected_routes(['role:superadmin'], 'modules', ModuleController::class, [
        'fetchList' => ['method' => 'get', 'uri' => 'fetch-list',     'action' => 'fetchList'],
        'toggleStatus' => ['method' => 'put', 'uri' => '{module}/status', 'action' => 'toggleStatus'],
    ], 'module');

    /*
     * =========================================================
     * VISIBILIDAD DEL SIDEBAR POR ROL
     * =========================================================
     * Migrado de role:superadmin a multiPermission:sidebar-menu.* — hoy
     * solo 'superadmin' tiene esos permisos (ver migración
     * 2026_09_27_000001_add_sidebar_and_dashboard_visibility_permissions),
     * mismo nivel de acceso de antes, pero delegable a futuro sin tocar
     * código. permissions/modules/roles-admin se quedan deliberadamente en
     * role:superadmin puro (riesgo de escalada de privilegios).
     */
    role_protected_routes(['multiPermission:sidebar-menu.view,sidebar-menu.manage'], 'sidebar-menu', SidebarMenuVisibilityController::class, [
        'index' => ['method' => 'get',  'uri' => '',           'action' => 'index'],
        'fetchList' => ['method' => 'get',  'uri' => 'fetch-list', 'action' => 'fetchList'],
        'saveChanges' => ['method' => 'post', 'uri' => 'save',       'action' => 'saveChanges', 'middleware' => 'multiPermission:sidebar-menu.manage'],
    ], 'sidebar-menu');

    /*
     * =========================================================
     * VISIBILIDAD DE WIDGETS DEL DASHBOARD POR ROL
     * =========================================================
     * Mismo criterio que sidebar-menu arriba.
     */
    role_protected_routes(['multiPermission:dashboard-widgets.view,dashboard-widgets.manage'], 'dashboard-widgets', DashboardWidgetVisibilityController::class, [
        'index' => ['method' => 'get',  'uri' => '',           'action' => 'index'],
        'fetchList' => ['method' => 'get',  'uri' => 'fetch-list', 'action' => 'fetchList'],
        'saveChanges' => ['method' => 'post', 'uri' => 'save',       'action' => 'saveChanges', 'middleware' => 'multiPermission:dashboard-widgets.manage'],
    ], 'dashboard-widgets');

    /*
     * =========================================================
     * CONTENIDO EDITORIAL DE LA LANDING PÚBLICA (Admin y SuperAdmin)
     * Sin create/destroy: el catálogo de 8 section_key es fijo por diseño,
     * solo se edita contenido, se activa/desactiva y se reordena.
     * 'reorder' se declara antes de {landingSection} para prevenir que un
     * segmento literal fijo colisione con el binding por section_key.
     * =========================================================
     */
    role_protected_routes(['role:admin,superadmin'], 'admin/landing-sections', \App\Http\Controllers\Content\LandingSectionController::class, [
        'reorder' => ['method' => 'put', 'uri' => 'reorder',              'action' => 'reorder'],
        'index' => ['method' => 'get', 'uri' => ''],
        'update' => ['method' => 'put', 'uri' => '{landingSection}/update'],
        'toggleStatus' => ['method' => 'put', 'uri' => '{landingSection}/status', 'action' => 'toggleStatus'],
    ], 'landingSection');

    /*
     * =========================================================
     * ROLES (SuperAdmin y Admin — Admin no puede tocar 'superadmin' ni
     * 'admin' mismo, ver RolePolicy::guardMutation())
     * =========================================================
     */
    role_protected_routes(['role:superadmin,admin'], 'roles-admin', RoleController::class, [
        'fetchList' => ['method' => 'get',   'uri' => 'fetch-list',    'action' => 'fetchList'],
        'hierarchy' => ['method' => 'get',   'uri' => 'hierarchy',     'action' => 'hierarchy'],
        'update' => ['method' => 'patch', 'uri' => '{role}',        'action' => 'update'],
        'toggleStatus' => ['method' => 'put',   'uri' => '{role}/status', 'action' => 'toggleStatus'],
    ], 'role');

    // Generación de llave de conexión para Customer
    Route::middleware('role:admin')
        ->post('admin/customer/generate-key', [AdminCustomerController::class, 'generateKey'])
        ->name('admin.customer.generate-key');

    /*
     * =========================================================
     * AUDITORÍA (SuperAdmin y Admin)
     * =========================================================
     */
    role_protected_routes(['multiPermission:audit.view'], 'audit', AuditController::class, [
        'index' => ['method' => 'get', 'uri' => '',                    'action' => 'index'],
        'fetchList' => ['method' => 'get', 'uri' => 'fetch-list',          'action' => 'fetchList'],
        'export' => ['method' => 'get', 'uri' => 'export',              'action' => 'export', 'middleware' => 'multiPermission:audit.export'],
        'mostChangedFields' => ['method' => 'get', 'uri' => 'most-changed-fields', 'action' => 'mostChangedFields'],
        'show' => ['method' => 'get', 'uri' => '{id}/show',           'action' => 'show'],
    ]);

    /*
     * =========================================================
     * LOGS DEL SISTEMA (SuperAdmin — permiso protegido system.logs)
     * =========================================================
     */
    role_protected_routes(['multiPermission:system.logs'], 'logs', SystemLogController::class, [
        'index' => ['method' => 'get', 'uri' => '',                'action' => 'index'],
        'fetchList' => ['method' => 'get', 'uri' => 'fetch-list',      'action' => 'fetchList'],
        'export' => ['method' => 'get', 'uri' => 'export',         'action' => 'export'],
        'checkNew' => ['method' => 'get', 'uri' => 'check-new',      'action' => 'checkNew'],
        'show' => ['method' => 'get', 'uri' => '{id}/show',       'action' => 'show'],
        'relatedLogs' => ['method' => 'get', 'uri' => '{id}/related',    'action' => 'relatedLogs'],
        'toggleReviewed' => ['method' => 'put', 'uri' => '{id}/reviewed',   'action' => 'toggleReviewed'],
        'toggleIgnored' => ['method' => 'put', 'uri' => '{id}/ignored',    'action' => 'toggleIgnored'],
        'assign' => ['method' => 'put', 'uri' => '{id}/assign',    'action' => 'assign'],
    ]);

    /*
     * =========================================================
     * KYC (verificación de identidad de jugadores) — view/manage separados:
     * cualquiera con kyc.view o kyc.manage puede ver la cola/detalle, pero
     * solo kyc.manage puede aprobar/rechazar (mismo criterio que
     * wallet-movements.approve/reject en routes/modules/wallets.php).
     * =========================================================
     */
    role_protected_routes(['multiPermission:kyc.view,kyc.manage'], 'admin/kyc', KycAdminController::class, [
        'index' => ['method' => 'get', 'uri' => '',                 'action' => 'index'],
        'fetchList' => ['method' => 'get', 'uri' => 'fetch-list',       'action' => 'fetchList'],
        'export' => ['method' => 'get', 'uri' => 'export',          'action' => 'export'],
        'show' => ['method' => 'get', 'uri' => '{document}/show',  'action' => 'show'],
        'image' => ['method' => 'get', 'uri' => '{document}/image/{type}', 'action' => 'image'],
    ], 'document');

    Route::middleware(['auth', 'multiPermission:kyc.manage', 'throttle:30,1'])
        ->prefix('admin/kyc')
        ->group(function() {
            Route::post('{document}/approve', [KycAdminController::class, 'approve'])->name('admin.kyc.approve');
            Route::post('{document}/reject', [KycAdminController::class, 'reject'])->name('admin.kyc.reject');
        });

    /*
     * =========================================================
     * ANTI-CHEAT (flags y suspensiones de jugadores) — mismo criterio
     * view/manage que KYC: cualquiera con anticheat.view puede ver flags/
     * suspensión activa, solo anticheat.manage puede resolver/suspender.
     * =========================================================
     */
    Route::middleware(['auth', 'multiPermission:anticheat.view,anticheat.manage'])
        ->prefix('admin/anticheat')
        ->group(function() {
            Route::get('users/{user}/flags', [AntiCheatAdminController::class, 'flagsForUser'])->name('admin.anticheat.flags');
        });

    Route::middleware(['auth', 'multiPermission:anticheat.manage', 'throttle:30,1'])
        ->prefix('admin/anticheat')
        ->group(function() {
            Route::post('suspensions/{suspension}/resolve', [AntiCheatAdminController::class, 'resolveSuspension'])->name('admin.anticheat.resolveSuspension');
            Route::post('users/{user}/suspend', [AntiCheatAdminController::class, 'suspendManually'])->name('admin.anticheat.suspendManually');
        });

    /*
     * =========================================================
     * IMPERSONACIÓN (soporte a jugadores/seller/commerce/subsidiary) — Solo
     * SuperAdmin puede INICIARLA (mismo criterio que /logs: role:superadmin
     * en vez de un permiso dedicado, acción de alto riesgo no delegable a un
     * admin cualquiera). 'stop' es deliberadamente solo 'auth', sin
     * role:superadmin: mientras la impersonación está activa, Auth::user()
     * ES el target (seller/commerce/...), no el superadmin real — exigir
     * role:superadmin aquí dejaba al superadmin sin forma de terminar su
     * propia sesión de soporte (RoleMiddleware::forbiddenResponse() lo
     * redirigía a /dashboard antes de llegar al controlador). La única
     * autorización real que stop() necesita es "¿hay una sesión de soporte
     * activa en esta sesión HTTP?", que ImpersonationService::stop() ya
     * valida internamente (throw NOT_IMPERSONATING si no la hay).
     * =========================================================
     */
    role_protected_routes(['role:superadmin'], 'admin/impersonation', ImpersonationController::class, [
        'start' => ['method' => 'post', 'uri' => '{player}/start', 'action' => 'start'],
    ], 'player');

    Route::middleware(['auth'])
        ->post('admin/impersonation/stop', [ImpersonationController::class, 'stop'])
        ->name('admin.impersonation.stop');

    Route::middleware(['auth', 'role:superadmin'])->prefix('admin/impersonation')->group(function() {
        Route::get('history', [ImpersonationController::class, 'historyPage'])->name('admin.impersonation.history.page');
        Route::get('history/data', [ImpersonationController::class, 'history'])->name('admin.impersonation.history');
    });

    /*
     * =========================================================
     * JOBS Y SCHEDULER (SuperAdmin — permiso protegido system.jobs)
     * =========================================================
     * {id} en scheduler/{id}/toggle es el snowflake de ScheduledCommand (no
     * el signature del comando, que contiene ':' y complicaría el binding).
     * {command} en scheduler/{command}/history sí es el signature — el
     * frontend debe encodeURIComponent() ese segmento.
     */
    role_protected_routes(['multiPermission:system.jobs'], 'system/jobs', JobsCenterController::class, [
        'index' => ['method' => 'get',    'uri' => '',                            'action' => 'index'],
        'scheduledCommands' => ['method' => 'get',    'uri' => 'scheduler',                   'action' => 'scheduledCommands'],
        'toggleScheduledCommand' => ['method' => 'put',    'uri' => 'scheduler/{id}/toggle',        'action' => 'toggleScheduledCommand'],
        'runScheduledCommand' => ['method' => 'post',   'uri' => 'scheduler/{id}/run',           'action' => 'runScheduledCommand'],
        'updateScheduledCommandFrequency' => ['method' => 'put', 'uri' => 'scheduler/{id}/frequency', 'action' => 'updateScheduledCommandFrequency'],
        'resetScheduledCommandFrequency' => ['method' => 'delete', 'uri' => 'scheduler/{id}/frequency', 'action' => 'resetScheduledCommandFrequency'],
        'previewScheduledCommandFrequency' => ['method' => 'post', 'uri' => 'scheduler/{id}/frequency/preview', 'action' => 'previewScheduledCommandFrequency'],
        'scheduledCommandHistory' => ['method' => 'get',    'uri' => 'scheduler/{command}/history',  'action' => 'scheduledCommandHistory'],
        'pendingJobs' => ['method' => 'get',    'uri' => 'pending',                      'action' => 'pendingJobs'],
        'pendingJobsByQueue' => ['method' => 'get',    'uri' => 'pending/{queue}',              'action' => 'pendingJobsByQueue'],
        'failedJobs' => ['method' => 'get',    'uri' => 'failed',                       'action' => 'failedJobs'],
        'retryFailedJob' => ['method' => 'post',   'uri' => 'failed/{uuid}/retry',          'action' => 'retryFailedJob'],
        'retryAllFailedJobs' => ['method' => 'post',   'uri' => 'failed/retry-all',             'action' => 'retryAllFailedJobs'],
        'forgetFailedJob' => ['method' => 'delete', 'uri' => 'failed/{uuid}',                'action' => 'forgetFailedJob'],
    ]);

    /*
     * =========================================================
     * EVENTOS DE GANANCIA — registro manual (premios físicos/objetos/servicios)
     * =========================================================
     */
    role_protected_routes(['role:superadmin,admin'], 'admin/win-event', AdminWinEventController::class, [
        'index' => ['method' => 'get',  'uri' => '',            'action' => 'index'],
        'store' => ['method' => 'post', 'uri' => '',            'action' => 'store'],
        'fetchList' => ['method' => 'get',  'uri' => 'fetch-list',  'action' => 'fetchList'],
        'show' => ['method' => 'get',  'uri' => '{id}/show',   'action' => 'show'],
    ]);
});
