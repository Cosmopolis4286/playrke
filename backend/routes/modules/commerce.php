<?php

use App\Http\Controllers\Roles\CommerceController;
use App\Http\Controllers\Roles\Commerce\CommerceProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Comercio y Gestión Empresarial
|--------------------------------------------------------------------------
| Funciones de comercios: gestión de vendedores, recompensas y operaciones.
*/

// ====================================
// AUTOGESTIÓN DE FICHA PROPIA (COMMERCE)
// ====================================
// Distinto de admin/commerce/* (gestión de terceros, multiPermission
// commerces.*) — aquí un usuario 'commerce' opera EXCLUSIVAMENTE sobre su
// propio registro, resuelto server-side por CommerceProfileService, nunca
// por un {id} de la URL.
Route::middleware(['auth', 'multiPermission:commerces.manage-own'])->group(function () {
    Route::get('commerce/profile', [CommerceProfileController::class, 'show'])->name('commerce.profile.show');
    Route::put('commerce/profile', [CommerceProfileController::class, 'update'])->name('commerce.profile.update');
});

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
