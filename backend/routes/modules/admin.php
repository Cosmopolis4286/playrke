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

    role_protected_routes(['role:admin'], 'admin/commerce', CommerceController::class, $adminCrudRoutes);
    role_protected_routes(['role:admin'], 'admin/customer', CustomerController::class, $adminCrudRoutes);
    role_protected_routes(['role:admin'], 'admin/treasurer', TreasurerController::class, $adminCrudRoutes);
    role_protected_routes(['role:admin'], 'admin/other-treasurers', OtherTreasurersController::class, $adminCrudRoutes);
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
