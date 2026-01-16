<?php

use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\ExchangeRateController;
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
route_group(
    'exchange-rate',
    'exchange-rate.',
    ['auth'],
    ExchangeRateController::class,
    [
        'index'        => ['method' => 'get', 'uri' => ''],
        'store'        => ['method' => 'post', 'uri' => ''],
        'show'         => ['method' => 'get', 'uri' => '{id}'],
        'update'       => ['method' => 'put', 'uri' => '{id}'],
        'destroy'      => ['method' => 'delete', 'uri' => '{id}'],
        'changeStatus' => ['method' => 'put', 'uri' => '{id}/status'],
    ]
);

route_public(
    'exchange-rate/public',
    'exchange-rate.public.',
    ExchangeRateController::class,
    [
        'index' => ['method' => 'get', 'uri' => ''],
    ],
    'exchange_rates:read'
);


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
