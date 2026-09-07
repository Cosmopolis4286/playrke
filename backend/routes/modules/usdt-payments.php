<?php

use App\Http\Controllers\Payments\Admin\PaymentOrderAdminController;
use App\Http\Controllers\Payments\PaymentOrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Pagos USDT-TRC20 (compra de packs/consumibles)
|--------------------------------------------------------------------------
| Ver PAYMENTS_USDT_TRC20_DESIGN.md. Dominio distinto de modules/payments.php
| (métodos de pago fiat: Pago Móvil, PIX, directorios bancarios) — este
| módulo es exclusivamente USDT-TRC20 sobre TRON, ver App\Domains\Payments.
|
| throttle:5,1 en la creación de orden: cada orden deriva una dirección
| nueva (derivation_index nunca reutilizado) — mismo criterio de límite que
| marketplace.listings.buy, que también mueve a un recurso finito por
| request.
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('payments/orders/{order}', [PaymentOrderController::class, 'page'])
        ->name('payments.orders.page');
    Route::get('payments/orders/{order}/show', [PaymentOrderController::class, 'show'])
        ->name('payments.orders.show');
    Route::get('payments/orders/{order}/status', [PaymentOrderController::class, 'status'])
        ->name('payments.orders.status');
    Route::get('payments/orders/{order}/qr', [PaymentOrderController::class, 'qr'])
        ->name('payments.orders.qr');
});

Route::middleware(['auth', 'verified', 'tos', 'throttle:5,1'])->group(function () {
    Route::post('payments/orders', [PaymentOrderController::class, 'store'])
        ->name('payments.orders.store');
});

// ====================================
// PANEL DE ADMINISTRACIÓN (ADMIN/SUPERADMIN)
// Nombre de recurso distinto ("payments-orders", no "payments/orders") para
// no competir con el wildcard payments/orders/{order} de arriba.
// ====================================
Route::middleware(['auth', 'multiPermission:payments.manage'])->group(function () {
    Route::get('payments-orders',           [PaymentOrderAdminController::class, 'index'])     ->name('payments-orders.index');
    Route::get('payments-orders/fetch-list', [PaymentOrderAdminController::class, 'fetchList']) ->name('payments-orders.fetch-list');
    Route::get('payments-orders/{order}',   [PaymentOrderAdminController::class, 'show'])       ->name('payments-orders.show');

    Route::middleware('throttle:30,1')->group(function () {
        Route::post('payments-orders/{order}/reevaluate', [PaymentOrderAdminController::class, 'reevaluate'])
            ->name('payments-orders.reevaluate');
    });
});
