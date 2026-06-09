<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Roles\AdminController;
use App\Http\Controllers\Roles\Admin\AdminCommerceController;
use App\Http\Controllers\Roles\Admin\AdminCustomerController;
use App\Http\Controllers\Roles\Admin\AdminPlayerController;
use App\Http\Controllers\Roles\Admin\AdminTreasurerController;
use App\Http\Controllers\Roles\Admin\AdminUserController;
use App\Http\Controllers\Roles\AdministratorController;
use App\Http\Controllers\Roles\PermissionController;
use App\Http\Controllers\Roles\Treasurer\OtherTreasurersController;
use App\Http\Controllers\Audit\AuditController;

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
            'dashboard' => [
                'method' => 'get',
                'uri'    => '',
                'action' => 'dashboard',
            ],
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
    role_protected_routes(['role:admin'], 'admin/treasurer', AdminTreasurerController::class, $adminCrudRoutes, 'commerce');

    // Rutas de usuarios (sin store/show/update — la creación se delega al flujo del rol)
    $userRoutes = [
        'index'         => ['method' => 'get',    'uri' => '',              'action' => 'index'],
        'fetch-list'    => ['method' => 'get',    'uri' => 'fetch-list',     'action' => 'fetchList'],
        'toggleStatus'  => ['method' => 'put',    'uri' => '{user}/status',  'action' => 'toggleStatus'],
        'delete'        => ['method' => 'put',    'uri' => '{user}/delete',  'action' => 'delete'],
        'destroy'       => ['method' => 'delete', 'uri' => '{user}',              'action' => 'destroy'],
        'resetPassword' => ['method' => 'put',    'uri' => '{user}/reset-password','action' => 'resetPassword'],
        'loadWalletBalance' => ['method' => 'put', 'uri' => '{user}/load-wallet-balance','action' => 'loadWalletBalance'],
        'getWalletBalance' => ['method' => 'get', 'uri' => '{user}/wallet-balance','action' => 'getWalletBalance'],
    ];
    role_protected_routes(['role:admin'], 'admin/user', AdminUserController::class, $userRoutes, 'user');

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
    ], 'permission');

    // Generación de llave de conexión para Customer
    Route::middleware('role:admin')
        ->post('admin/customer/generate-key', [AdminCustomerController::class, 'generateKey'])
        ->name('admin.customer.generate-key');

    /**
     * =========================================================
     * AUDITORÍA (SuperAdmin y Admin)
     * =========================================================
     */
    role_protected_routes(['role:superadmin,admin'], 'audit', AuditController::class, [
        'index'     => ['method' => 'get', 'uri' => '',            'action' => 'index'],
        'fetchList' => ['method' => 'get', 'uri' => 'fetch-list',  'action' => 'fetchList'],
        'show'      => ['method' => 'get', 'uri' => '{id}/show',   'action' => 'show'],
    ]);
});
