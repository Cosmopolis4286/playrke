<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

require_once app_path('Helpers/RouteHelper.php');

/*
|--------------------------------------------------------------------------
| API Routes - Sistema RESTful Moderno
|--------------------------------------------------------------------------
| APIs organizadas por recursos con autenticación Sanctum.
| Perfectas para SPAs, móviles y consumo por terceros.
*/

// ====================================
// APIS PÚBLICAS (sin autenticación)
// ====================================
Route::prefix('api/v1')->group(function () {
    // Health check
    Route::get('ping', function () {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'version' => 'v1.0.0'
        ]);
    })->name('api.ping');
    
    // Futuras APIs públicas
    // Route::get('currencies', [CurrencyController::class, 'public']);
    // Route::get('exchange-rates/latest', [ExchangeRateController::class, 'latest']);
});

// ====================================
// APIS PROTEGIDAS (requieren token)
// ====================================
Route::middleware('auth:sanctum')->prefix('api/v1')->group(function () {
    // Usuario actual
    Route::get('user', function (Request $request) {
        return response()->json($request->user());
    })->name('api.user');
    
    // Recursos RESTful
    // api_resource('wallets', WalletController::class);
    // api_resource('payments', PaymentController::class);
    // api_resource('bingo-rooms', BingoController::class);
    
    // Módulos de API
    // Route::prefix('gaming')->group(base_path('routes/modules/api/gaming.php'));
    // Route::prefix('commerce')->group(base_path('routes/modules/api/commerce.php'));
});

// ====================================
// WEBHOOK ENDPOINTS (verificación externa)
// ====================================
Route::prefix('webhooks')->group(function () {
    // Route::post('payments/notify', [PaymentWebhookController::class, 'handle']);
    // Route::post('games/result', [GameWebhookController::class, 'result']);
});
