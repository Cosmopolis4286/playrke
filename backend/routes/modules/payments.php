<?php

use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Métodos de Pago
|--------------------------------------------------------------------------
| Gestión de todos los métodos de pago: Pago Móvil, PIX, Colombia,
| directorios de datos y comprobantes de pago.
*/

// ====================================
// DIRECTORIO BANCARIO POR PAÍSES
// ====================================
route_group('directory', 'directory.', ['auth', 'role:admin,superadmin,commerce,customer,seller,player'], DirectoryController::class, [
    'index' => ['method' => 'get', 'uri' => '', 'action' => 'index'],
    'store' => ['method' => 'post', 'uri' => '', 'action' => 'store'],
    'fetch-list' => ['method' => 'get', 'uri' => 'fetch-list', 'action' => 'fetchList'],
    'fetch-context' => ['method' => 'get', 'uri' => 'fetch-context', 'action' => 'fetchContext'],
    'fetch-brazil-banks' => ['method' => 'get', 'uri' => 'fetch-brazil-banks', 'action' => 'fetchBrazilBanks'],
    'show' => ['method' => 'get', 'uri' => '{room}/show', 'action' => 'show'],
    'update' => ['method' => 'patch',  'uri' => '{room}/update', 'action' => 'update'],
    'delete' => ['method' => 'delete', 'uri' => '{room}/delete', 'action' => 'delete'],
    'destroy' => ['method' => 'delete', 'uri' => '{room}/force-delete', 'action' => 'destroy'],
    'toggle-status' => ['method' => 'patch', 'uri' => '{room}/status', 'action' => 'toggleStatus'],
]);

// ====================================
// COMPROBANTES DE PAGO
// ====================================
// Route::middleware('auth')
//     ->get('payment/proof/{fileName}', [PaymentController::class, 'showPaymentProof'])
//     ->name('payment.proof');

// // ====================================
// // MÉTODOS DE PAGO COLOMBIA
// // ====================================
// crud_routes('payment_accounts_co', PaymentAccountController::class, ['auth'], ['index', 'destroy']);
// Route::middleware('auth')->group(function () {
//     Route::delete('payment-accounts-co/{id}', [PaymentAccountController::class, 'destroy'])->name('payment_accounts_co.destroy');
//     Route::get('payment-accounts-\co/{id}/info', [PaymentAccountController::class, 'show'])->name('payment_accounts_co.show');
// });
