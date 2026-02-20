<?php

use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\Directories\Fiat\Brazil\PixController;
use App\Http\Controllers\Directories\Fiat\Colombia\PaymentAccountController;
use App\Http\Controllers\Directories\Fiat\Venezuela\PagoMovilController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Users\ReportsController;
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
Route::middleware('auth')->group(function () {
    // Vista principal (Inertia)
    Route::get('directory/{countryCode?}', [DirectoryController::class, 'index'])->name('directory.index');

    // API CRUD
    Route::get('admin/directory/fetch-list', [DirectoryController::class, 'fetchList'])->name('directory.fetch-list');
    Route::get('admin/directory/fetch-context', [DirectoryController::class, 'fetchContext'])->name('directory.fetch-context');
    Route::get('admin/directory/{id}', [DirectoryController::class, 'show'])->name('directory.show');
    Route::post('admin/directory', [DirectoryController::class, 'store'])->name('directory.store');
    Route::put('admin/directory/{id}', [DirectoryController::class, 'update'])->name('directory.update');
    Route::put('admin/directory/{id}/delete', [DirectoryController::class, 'delete'])->name('directory.delete');
    Route::put('admin/directory/{id}/status', [DirectoryController::class, 'toggleStatus'])->name('directory.toggle-status');
});

// ====================================
// COMPROBANTES DE PAGO
// ====================================
Route::middleware('auth')
    ->get('payment/proof/{fileName}', [PaymentController::class, 'showPaymentProof'])
    ->name('payment.proof');

// ====================================
// PAGO MÓVIL (VENEZUELA)
// ====================================
crud_routes('pagomovil', PagoMovilController::class, ['auth'], ['destroy']);
Route::middleware('auth')->group(function () {
    Route::delete('pagomovil/{id}', [PagoMovilController::class, 'destroy'])->name('pagomovil.destroy');
    Route::get('pagomovil/{id}/info', [PagoMovilController::class, 'show'])->name('pagomovil.show');
});

// ====================================
// PIX (BRASIL)
// ====================================
crud_routes('pix', PixController::class, ['auth'], ['destroy']);
Route::middleware('auth')->group(function () {
    Route::delete('pix/{id}', [PixController::class, 'destroy'])->name('pix.destroy');
    Route::get('pix/{id}/info', [PixController::class, 'show'])->name('pix.show');
    Route::get('pix/banks', [PixController::class, 'getBanks'])->name('pix.banks');
});

// ====================================
// MÉTODOS DE PAGO COLOMBIA
// ====================================
crud_routes('payment_accounts_co', PaymentAccountController::class, ['auth'], ['index', 'destroy']);
Route::middleware('auth')->group(function () {
    Route::delete('payment-accounts-co/{id}', [PaymentAccountController::class, 'destroy'])->name('payment_accounts_co.destroy');
    Route::get('payment-accounts-\co/{id}/info', [PaymentAccountController::class, 'show'])->name('payment_accounts_co.show');
});

// ====================================
// REPORTES DE PAGOS
// ====================================
route_group('report', 'report.', ['auth'], ReportsController::class, [
    'index' => ['method' => 'get', 'uri' => ''],
    'store' => ['method' => 'post', 'uri' => ''],
]);
