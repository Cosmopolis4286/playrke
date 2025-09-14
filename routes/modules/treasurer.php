<?php

use App\Http\Controllers\TreasurerController;
use Illuminate\Support\Facades\Route;

require_once app_path('Helpers/RouteHelper.php');

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