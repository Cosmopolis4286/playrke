<?php

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
// multi_middleware_routes('commerce', 'commerce.', [
//     [
//         'middleware' => ['auth', 'role:commerce'],
//         'controller' => CommerceController::class,
//         'routes' => [
//             'prizes' => ['method' => 'get', 'uri' => 'prizes'],
//             'currency-by-country' => ['method' => 'get', 'uri' => 'seller/currency/{country}/{seller}'],
//             'seller-info-by-carton' => ['method' => 'get', 'uri' => 'seller/info/{ctnId}'],
//             'carton-lot-payment' => ['method' => 'put', 'uri' => 'bingo/carton-lot/payment'],
//         ]
//     ]
// ]);

// // ====================================
// // RUTA PÚBLICA DE PAGO (SIN AUTH)
// // ====================================
// Route::get('commerce/payment-details/{commerce}/{currency}', [CommerceController::class, 'getPaymentCommerceDetails'])
//     ->name('commerce.payment-details-public');
