<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Aquí puedes registrar las rutas de tu API. Estas rutas usan el middleware
| 'api' que ya viene configurado en Kernel.php. Son stateless, perfectas
| para SPA, móviles o consumo por terceros.
|
*/

// Ruta de prueba (opcional)
Route::get('/ping', function () {
    return response()->json(['message' => 'pong']);
});

// ===============================
// Rutas protegidas con token o auth:api
// ===============================
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Ejemplo de rutas modulares
    // Route::prefix('wallet')->group(base_path('routes/modules/api/wallet.php'));
    // Route::prefix('commerce_id')->group(base_path('routes/modules/api/commerce.php'));
});

// ===============================
// Rutas públicas de API
// ===============================
// Route::get('/coins', [CoinController::class, 'index']);
