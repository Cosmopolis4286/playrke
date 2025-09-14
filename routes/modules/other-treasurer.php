<?php

use App\Http\Controllers\OtherTreasurersController;
use Illuminate\Support\Facades\Route;

require_once app_path('Helpers/RouteHelper.php');

/*
|--------------------------------------------------------------------------
| Rutas de Tesorería (Otros Tesoreros)
|--------------------------------------------------------------------------
| Funcionalidades específicas para tesoreros (otros tesoreros) y manejo de pagos.
*/

// ====================================
// DETALLES DE PAGO PARA OTROS TESOREROS
// ====================================
Route::middleware('auth')
    ->get('treasurer/payment-details/{treasurer}/{currency}', [OtherTreasurersController::class, 'getPaymentTreasurerDetails'])
    ->name('treasurer.payment-details');
