<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Roles\AdminController;
use App\Http\Controllers\Roles\Admin\AdminCommerceController;
use App\Http\Controllers\Roles\Admin\AdminCustomerController;
use App\Http\Controllers\Roles\Admin\AdminPlayerController;
use App\Http\Controllers\Roles\Admin\AdminTreasurerController;
use App\Http\Controllers\Roles\Admin\AdminUserController;
use App\Http\Controllers\Roles\Admin\ExternalPlayerAdminController;
use App\Http\Controllers\Roles\Admin\User\UserPermissionController;
use App\Http\Controllers\Roles\AdministratorController;
use App\Http\Controllers\Roles\PermissionController;
use App\Http\Controllers\Roles\ModuleController;
use App\Http\Controllers\Roles\SidebarMenuVisibilityController;
use App\Http\Controllers\Roles\RoleController;
use App\Http\Controllers\Roles\Treasurer\OtherTreasurersController;
use App\Http\Controllers\Audit\AuditController;
use App\Http\Controllers\Roles\Admin\AdminWinEventController;

/*
|--------------------------------------------------------------------------
| Rutas de Administración del Sistema
|--------------------------------------------------------------------------
| Gestión completa del panel administrativo.
| Todas protegidas por auth + role:admin salvo excepciones.
*/

Route::middleware('auth')->group(function () {

    /**
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
                'uri'    => 'customers',
                'action' => 'customers',
            ],
            'commerces' => [
                'method' => 'get',
                'uri'    => 'commerces',
                'action' => 'commerces',
            ],
            'players' => [
                'method' => 'get',
                'uri'    => 'players',
                'action' => 'players',
            ],
            'sellers' => [
                'method' => 'get',
                'uri'    => 'sellers',
                'action' => 'sellers',
            ],
            'treasurers' => [
                'method' => 'get',
                'uri'    => 'treasurers',
                'action' => 'treasurers',
            ],
            'subsidiaries' => [
                'method' => 'get',
                'uri'    => 'subsidiaries',
                'action' => 'subsidiaries',
            ],
            'users' => [
                'method' => 'get',
                'uri'    => 'users',
                'action' => 'users',
            ],
            'getUserInfo' => [
                'method' => 'get',
                'uri'    => 'get-user-info/{userRole}/{exclude?}',
                'action' => 'getUserInfo',
            ],
        ]
    );

    /**
     * =========================================================
     * CRUDs ADMINISTRATIVOS
     * =========================================================
     */
    $adminCrudRoutes = [
        'store' => [
            'method' => 'post',
            'uri'    => '',
            'action' => 'store',
        ],
        'fetchList' => [
            'method' => 'get',
            'uri'    => 'fetch-list',
            'action' => 'fetchList',
        ],
        'show' => [
            'method' => 'get',
            'uri'    => '{id}/show',
            'action' => 'show',
        ],
        'update' => [
            'method' => 'put',
            'uri'    => '{id}/update',
            'action' => 'update',
        ],
        'delete' => [
            'method' => 'put',
            'uri'    => '{id}/delete',
            'action' => 'delete',
        ],
        'destroy' => [
            'method' => 'delete',
            'uri'    => '{id}',
            'action' => 'destroy',
        ],
        'toggleStatus' => [
            'method' => 'put',
            'uri'    => '{id}/status',
            'action' => 'toggleStatus',
        ],
        'resetPassword' => [
            'method' => 'put',
            'uri'    => '{id}/reset-password',
            'action' => 'resetPassword',
        ]
    ];

    role_protected_routes(['role:admin'], 'admin/commerce',  AdminCommerceController::class,  $adminCrudRoutes, 'commerce');
    role_protected_routes(['role:admin'], 'admin/customer',  AdminCustomerController::class,  $adminCrudRoutes, 'customer');
    role_protected_routes(['role:admin'], 'admin/player',    AdminPlayerController::class,    $adminCrudRoutes, 'player');
    role_protected_routes(['role:admin'], 'admin/treasurer', AdminTreasurerController::class, $adminCrudRoutes, 'treasurer');

    // Rutas de usuarios (sin store/show — la creación se delega al flujo del rol)
    $userRoutes = [
        'index'         => ['method' => 'get',    'uri' => '',              'action' => 'index'],
        'fetch-list'    => ['method' => 'get',    'uri' => 'fetch-list',     'action' => 'fetchList'],
        'export'        => ['method' => 'get',    'uri' => 'export',        'action' => 'export'],
        'show'          => ['method' => 'get',    'uri' => '{user}/show',   'action' => 'show'],
        'update'        => ['method' => 'patch',  'uri' => '{user}',        'action' => 'update'],
        'toggleStatus'  => ['method' => 'put',    'uri' => '{user}/status',  'action' => 'toggleStatus'],
        'toggleStatusBulk' => ['method' => 'put', 'uri' => 'bulk/status',   'action' => 'toggleStatusBulk', 'middleware' => 'throttle:admin-users-bulk'],
        'deleteBulk'    => ['method' => 'put',    'uri' => 'bulk/delete',   'action' => 'deleteBulk', 'middleware' => 'throttle:admin-users-bulk'],
        'delete'        => ['method' => 'put',    'uri' => '{user}/delete',  'action' => 'delete'],
        'destroy'       => ['method' => 'delete', 'uri' => '{user}',              'action' => 'destroy'],
        'resetPassword' => ['method' => 'put',    'uri' => '{user}/reset-password','action' => 'resetPassword'],
        'loadWalletBalance' => ['method' => 'put', 'uri' => '{user}/load-wallet-balance','action' => 'loadWalletBalance'],
        'getWalletBalance' => ['method' => 'get', 'uri' => '{user}/wallet-balance','action' => 'getWalletBalance'],
        'grantXp'       => ['method' => 'put',    'uri' => '{user}/grant-xp', 'action' => 'grantXp', 'middleware' => 'throttle:admin-grant-xp'],
    ];
    role_protected_routes(['role:admin'], 'admin/user', AdminUserController::class, $userRoutes, 'user');

    /**
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
        'index'          => ['method' => 'get', 'uri' => '',              'action' => 'index'],
        'fetchList'      => ['method' => 'get', 'uri' => 'fetch-list',    'action' => 'fetchList'],
        'export'         => ['method' => 'get', 'uri' => 'export',       'action' => 'export', 'middleware' => 'multiPermission:external-players.export'],
        'toggleBlock'    => ['method' => 'put', 'uri' => '{externalPlayer}/toggle-block', 'action' => 'toggleBlock', 'middleware' => 'multiPermission:external-players.block'],
        'regenerateLink' => ['method' => 'put', 'uri' => '{externalPlayer}/regenerate-link', 'action' => 'regenerateLink', 'middleware' => 'multiPermission:external-players.manage'],
        'reactivate'     => ['method' => 'put', 'uri' => '{externalPlayer}/reactivate', 'action' => 'reactivate', 'middleware' => 'multiPermission:external-players.manage'],
        'convertToUser'  => ['method' => 'put', 'uri' => '{externalPlayer}/convert-to-user', 'action' => 'convertToUser', 'middleware' => 'multiPermission:external-players.manage'],
    ], 'externalPlayer');

    /**
     * =========================================================
     * PERMISOS ESPECÍFICOS POR USUARIO (SOLO SUPERADMIN)
     * =========================================================
     * Distinto del grupo admin/user de arriba (role:admin): otorgar/revocar
     * permisos directos a una persona es más sensible que las operaciones
     * CRUD normales de usuario, así que exige superadmin explícitamente.
     */
    role_protected_routes(['role:superadmin'], 'admin/user', UserPermissionController::class, [
        // Nombres de ruta distintos a los de $userRoutes (arriba, mismo
        // prefijo admin/user.*): 'index'/'update' ya existen ahí y
        // route_group() reasignaría el nombre a la última ruta definida,
        // rompiendo route('admin.user.index') del listado de usuarios.
        'permissions.show'   => ['method' => 'get', 'uri' => '{user}/permissions', 'action' => 'index'],
        'permissions.update' => ['method' => 'put', 'uri' => '{user}/permissions', 'action' => 'update'],
    ], 'user');

    /**
     * =========================================================
     * GESTIÓN DE ADMINISTRADORES (SOLO SUPERADMIN)
     * =========================================================
     */
    role_protected_routes(['role:superadmin'], 'admins', AdministratorController::class, [
        'index'         => ['method' => 'get',    'uri' => '',                    'action' => 'index'],
        'fetchList'     => ['method' => 'get',    'uri' => 'fetch-list',          'action' => 'fetchList'],
        'toggleStatus'  => ['method' => 'patch', 'uri' => '{user}/toggle-status',  'action' => 'toggleStatus'],
        'delete'        => ['method' => 'delete', 'uri' => '{user}',               'action' => 'delete'],
        'destroy'       => ['method' => 'delete', 'uri' => '{user}/force-delete',     'action' => 'destroy'],
        'resetPassword' => ['method' => 'put',    'uri' => '{user}/reset-password', 'action' => 'resetPassword'],
    ], 'user');

    /**
     * =========================================================
     * PERMISOS (Solo SuperAdmin)
     * =========================================================
     */
    role_protected_routes(['role:superadmin'], 'permissions', PermissionController::class, [
        'index'                => ['method' => 'get',    'uri' => '',                          'action' => 'index'],
        'fetchList'            => ['method' => 'get',    'uri' => 'fetch-list',                'action' => 'fetchList'],
        'getRolePermissions'   => ['method' => 'get',    'uri' => 'roles/{role}/permissions',  'action' => 'getRolePermissions'],
        'assignPermissions'    => ['method' => 'post',   'uri' => 'roles/{role}/assign',       'action' => 'assignPermissions'],
        'revokePermissions'    => ['method' => 'post',   'uri' => 'roles/{role}/revoke',       'action' => 'revokePermissions'],
        'store'                => ['method' => 'post',   'uri' => '',                          'action' => 'store'],
        'update'               => ['method' => 'patch',  'uri' => '{permission}',             'action' => 'update'],
        'delete'               => ['method' => 'delete', 'uri' => '{permission}',             'action' => 'delete'],
        'getRolesWithPermissions' => ['method' => 'get', 'uri' => 'roles-permissions',       'action' => 'getRolesWithPermissions'],
        'saveMatrix'           => ['method' => 'patch',  'uri' => 'matrix',                    'action' => 'saveMatrix', 'middleware' => 'throttle:admin-permissions-matrix'],
        'copyToRole'           => ['method' => 'post',   'uri' => 'roles/{role}/copy-from/{sourceRole}', 'action' => 'copyToRole'],
    ], 'permission');

    /**
     * =========================================================
     * MÓDULOS DEL SISTEMA (Solo SuperAdmin)
     * =========================================================
     */
    role_protected_routes(['role:superadmin'], 'modules', ModuleController::class, [
        'fetchList'    => ['method' => 'get', 'uri' => 'fetch-list',     'action' => 'fetchList'],
        'toggleStatus' => ['method' => 'put', 'uri' => '{module}/status', 'action' => 'toggleStatus'],
    ], 'module');

    /**
     * =========================================================
     * VISIBILIDAD DEL SIDEBAR POR ROL (Solo SuperAdmin)
     * =========================================================
     */
    role_protected_routes(['role:superadmin'], 'sidebar-menu', SidebarMenuVisibilityController::class, [
        'index'       => ['method' => 'get',  'uri' => '',           'action' => 'index'],
        'fetchList'   => ['method' => 'get',  'uri' => 'fetch-list', 'action' => 'fetchList'],
        'saveChanges' => ['method' => 'post', 'uri' => 'save',       'action' => 'saveChanges'],
    ], 'sidebar-menu');

    /**
     * =========================================================
     * ROLES (Solo SuperAdmin)
     * =========================================================
     */
    role_protected_routes(['role:superadmin'], 'roles-admin', RoleController::class, [
        'fetchList'    => ['method' => 'get',   'uri' => 'fetch-list',    'action' => 'fetchList'],
        'hierarchy'    => ['method' => 'get',   'uri' => 'hierarchy',     'action' => 'hierarchy'],
        'update'       => ['method' => 'patch', 'uri' => '{role}',        'action' => 'update'],
        'toggleStatus' => ['method' => 'put',   'uri' => '{role}/status', 'action' => 'toggleStatus'],
    ], 'role');

    // Generación de llave de conexión para Customer
    Route::middleware('role:admin')
        ->post('admin/customer/generate-key', [AdminCustomerController::class, 'generateKey'])
        ->name('admin.customer.generate-key');

    /**
     * =========================================================
     * AUDITORÍA (SuperAdmin y Admin)
     * =========================================================
     */
    role_protected_routes(['multiPermission:audit.view'], 'audit', AuditController::class, [
        'index'     => ['method' => 'get', 'uri' => '',            'action' => 'index'],
        'fetchList' => ['method' => 'get', 'uri' => 'fetch-list',  'action' => 'fetchList'],
        'export'    => ['method' => 'get', 'uri' => 'export',      'action' => 'export', 'middleware' => 'multiPermission:audit.export'],
        'show'      => ['method' => 'get', 'uri' => '{id}/show',   'action' => 'show'],
    ]);

    /**
     * =========================================================
     * EVENTOS DE GANANCIA — registro manual (premios físicos/objetos/servicios)
     * =========================================================
     */
    role_protected_routes(['role:superadmin,admin'], 'admin/win-event', AdminWinEventController::class, [
        'index'     => ['method' => 'get',  'uri' => '',            'action' => 'index'],
        'store'     => ['method' => 'post', 'uri' => '',            'action' => 'store'],
        'fetchList' => ['method' => 'get',  'uri' => 'fetch-list',  'action' => 'fetchList'],
    ]);
});
