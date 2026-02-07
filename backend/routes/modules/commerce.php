<?php

use App\Http\Controllers\Roles\Commerce\CommerceSellerController;
use App\Http\Controllers\Roles\CommerceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Comercio y Gestión Empresarial
|--------------------------------------------------------------------------
| Funciones de comercios: gestión de vendedores, recompensas y operaciones.
*/

// ====================================
// GESTIÓN DE COMERCIOS (ADMIN + COMMERCE)
// ====================================
multi_middleware_routes('commerce', 'commerce.', [
    [
        'middleware' => ['auth', 'role:commerce'],
        'controller' => CommerceController::class,
        'routes' => [
            'rewards' => ['method' => 'get', 'uri' => 'rewards'],
            'currency-by-country' => ['method' => 'get', 'uri' => 'seller/currency/{country}/{seller}'],
            'seller-info-by-carton' => ['method' => 'get', 'uri' => 'seller/info/{ctnId}'],
            'carton-lot-payment' => ['method' => 'put', 'uri' => 'bingo/carton-lot/payment'],
        ]
    ]
]);

// GESTIÓN DE VENDEDORES (COMMERCE => SELLER)
// ====================================
multi_middleware_routes('commerce', 'commerce.', [
    [
        'middleware' => ['auth', 'role:commerce'],
        'controller' => CommerceSellerController::class,
        'routes' => [
            'sellers' => ['method' => 'get', 'uri' => 'sellers'],
            'create' => ['method' => 'post', 'uri' => ''],
            'show' => ['method' => 'get', 'uri' => '{id}'],
            'update' => ['method' => 'put', 'uri' => '{id}'],
            'destroy' => ['method' => 'delete', 'uri' => '{id}'],
            'resetPassword' => ['method' => 'put', 'uri' => '{id}/password'],
            'changeStatus' => ['method' => 'put', 'uri' => '{id}/status'],
        ]
    ]
]);

// ====================================
// RUTA PÚBLICA DE PAGO (SIN AUTH)
// ====================================
Route::get('commerce/payment-details/{commerce}/{currency}', [CommerceController::class, 'getPaymentCommerceDetails'])
    ->name('commerce.payment-details-public');
