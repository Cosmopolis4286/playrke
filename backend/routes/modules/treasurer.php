<?php

use App\Http\Controllers\Roles\Treasurer\TreasurerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Tesorería
|--------------------------------------------------------------------------
| Funcionalidades específicas para tesoreros y manejo de pagos.
*/

// ====================================
// DETALLES DE PAGO PARA TESOREROS
// ====================================
Route::middleware('auth')
    ->get('treasurer/payment-details/{treasurer}/{currency}', [TreasurerController::class, 'getPaymentTreasurerDetails'])
    ->name('treasurer.payment-details');

    // Administración Tesorero (Admin + Auth)
Route::group(['middleware' => ['auth', 'admin'], 'prefix' => 'treasurer', 'as' => 'treasurer.'], function () {
    Route::post('create', [TreasurerController::class, 'create'])->name('create');
    Route::get('getInfo/{id}', [TreasurerController::class, 'getInfo'])->name('getInfo');
    Route::put('update/{id}', [TreasurerController::class, 'update'])->name('update');
    Route::delete('delete/{id}', [TreasurerController::class, 'delete'])->name('delete');
    Route::put('changePassw/{id}', [TreasurerController::class, 'resetPassword'])->name('changePassw');
    Route::put('changeStatus/{id}', [TreasurerController::class, 'changeStatus'])->name('changeStatus');
});
