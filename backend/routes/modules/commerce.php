<?php

use App\Http\Controllers\User\Roles\CommerceController;
use Illuminate\Support\Facades\Route;

require_once app_path('Helpers/RouteHelper.php');

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
            'sellers' => ['method' => 'get', 'uri' => 'sellers'],
            'currency-by-country' => ['method' => 'get', 'uri' => 'seller/currency/{country}/{seller}'],
            'seller-info-by-carton' => ['method' => 'get', 'uri' => 'seller/info/{ctnId}'],
            'carton-lot-payment' => ['method' => 'put', 'uri' => 'bingo/carton-lot/payment'],
        ]
    ]
]);

// ====================================
// RUTA PÚBLICA DE PAGO (SIN AUTH)
// ====================================
Route::get('commerce/payment-details/{commerce}/{currency}', [CommerceController::class, 'getPaymentCommerceDetails'])
    ->name('commerce.payment-details-public');
