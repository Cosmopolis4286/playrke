<?php

use App\Http\Controllers\Roles\Admin\AdminCommerceController;
use App\Http\Controllers\Roles\AdminController;
use App\Http\Controllers\Roles\CustomerController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Roles\Treasurer\{
    TreasurerController,
    OtherTreasurersController
};

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
     * CRUDs ADMINISTRATIVOS
     * =========================================================
     */
    $adminCrudRoutes = [
        'store' => [
            'method' => 'post',
            'uri'    => '',
            'action' => 'store',
        ],
        'listFiltered' => [
            'method' => 'get',
            'uri'    => 'list-filtered',
            'action' => 'listFiltered',
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

    role_protected_routes(['role:admin'], 'admin/commerce', AdminCommerceController::class, $adminCrudRoutes, 'commerce');
    role_protected_routes(['role:admin'], 'admin/customer', CustomerController::class, $adminCrudRoutes, 'customer');
    role_protected_routes(['role:admin'], 'admin/treasurer', TreasurerController::class, $adminCrudRoutes, 'treasurer');
    role_protected_routes(['role:admin'], 'admin/other-treasurers', OtherTreasurersController::class, $adminCrudRoutes, 'otherTreasurer');
});

/**
 * =========================================================
 * RUTA TESORERO (NO ADMIN)
 * =========================================================
 */
Route::middleware('auth')
    ->get(
        'treasurer/payment-details/{treasurer}/{currency}',
        [TreasurerController::class, 'getPaymentTreasurerDetails']
    )
    ->name('treasurer.payment-details');
