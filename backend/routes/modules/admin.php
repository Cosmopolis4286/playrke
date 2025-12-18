<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\Roles\{
    AdminController,
    CommerceController,
    CustomerController
};
use App\Http\Controllers\User\Roles\Treasurer\{
    TreasurerController,
    OtherTreasurersController
};

require_once app_path('Helpers/RouteHelper.php');

/*
|--------------------------------------------------------------------------
| Rutas de Administración del Sistema
|--------------------------------------------------------------------------
| Gestión completa del panel administrativo, usuarios, comercios,
| clientes y tesorería. Todas protegidas por rol admin salvo excepciones.
*/

/**
 * =========================================================
 * PANEL ADMINISTRATIVO PRINCIPAL
 * =========================================================
 */
role_protected_routes(
    ['role:admin'],
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
        'other-treasurers' => [
            'method' => 'get',
            'uri'    => 'other-treasurers',
            'action' => 'otherTreasurers',
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
 * CRUDs ADMINISTRATIVOS ESTÁNDAR
 * =========================================================
 * Incluye: store, show, update, destroy, password y status
 */
$adminCrudRoutes = [
    'store' => [
        'method' => 'post',
        'uri'    => '',
        'action' => 'store',
    ],
    'show' => [
        'method' => 'get',
        'uri'    => '{id}',
        'action' => 'show',
    ],
    'update' => [
        'method' => 'put',
        'uri'    => '{id}',
        'action' => 'update',
    ],
    'destroy' => [
        'method' => 'delete',
        'uri'    => '{id}',
        'action' => 'destroy',
    ],
    'changePassword' => [
        'method' => 'put',
        'uri'    => '{id}/password',
        'action' => 'changePassword',
    ],
    'changeStatus' => [
        'method' => 'put',
        'uri'    => '{id}/status',
        'action' => 'changeStatus',
    ],
];

role_protected_routes(['role:admin'], 'commerce', CommerceController::class, $adminCrudRoutes);
role_protected_routes(['role:admin'], 'customer', CustomerController::class, $adminCrudRoutes);
role_protected_routes(['role:admin'], 'treasurer', TreasurerController::class, $adminCrudRoutes);
role_protected_routes(['role:admin'], 'other-treasurer', OtherTreasurersController::class, $adminCrudRoutes);

/**
 * =========================================================
 * RUTA ESPECIAL TESORERO (SIN ROL ADMIN)
 * =========================================================
 * Necesaria para consultas operativas autenticadas
 */
Route::middleware('auth')
    ->get(
        'treasurer/payment-details/{treasurer}/{currency}',
        [TreasurerController::class, 'getPaymentTreasurerDetails']
    )
    ->name('treasurer.payment-details');
