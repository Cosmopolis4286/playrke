<?php

use App\Http\Controllers\Payments\DirectoryController;
use App\Http\Controllers\ExchangeRate\ExchangeRateController;
use App\Http\Controllers\Payments\Brasil\PixController;
use App\Http\Controllers\Payments\Colombia\PaymentAccountController;
use App\Http\Controllers\Payments\Venezuela\PagoMovilController;
use App\Http\Controllers\Payments\PaymentController;
use App\Http\Controllers\User\ReportsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Métodos de Pago
|--------------------------------------------------------------------------
| Gestión de todos los métodos de pago: Pago Móvil, PIX, Colombia,
| tasas de cambio, directorios de datos y comprobantes de pago.
*/

// ====================================
// DIRECTORIO BANCARIO POR PAÍSES
// ====================================
route_group('directory', 'directory.', ['auth'], DirectoryController::class, [
    'index' => ['method' => 'get', 'uri' => 'directory'],
    'country' => ['method' => 'get', 'uri' => '{countryCode}'],
]);

// ====================================
// COMPROBANTES DE PAGO
// ====================================
Route::middleware('auth')
    ->get('payment/proof/{fileName}', [PaymentController::class, 'showPaymentProof'])
    ->name('payment.proof');

// ====================================
// TASAS DE CAMBIO
// ====================================
route_group('exchange-rate', 'exchange-rate.', [], ExchangeRateController::class, [
    'index' => ['method' => 'get', 'uri' => '{creatorType}/{creatorId?}'],
    'create' => ['method' => 'post', 'uri' => ''],
    'show' => ['method' => 'get', 'uri' => '{id}'],
    'update' => ['method' => 'put', 'uri' => '{id}'],
    'destroy' => ['method' => 'delete', 'uri' => '{id}'],
    'changeStatus' => ['method' => 'put', 'uri' => '{id}/status'],
    'get-rates' => ['method' => 'get', 'uri' => 'rates'],
    'last-value' => ['method' => 'get', 'uri' => 'last-value/{currency}'],
    'historical' => ['method' => 'get', 'uri' => 'historical/{currency?}/{creatorType}/{creatorId}'],
]);

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
