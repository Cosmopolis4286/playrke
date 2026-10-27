<?php

/*
|--------------------------------------------------------------------------
| APIs Públicas (sin autenticación)
|--------------------------------------------------------------------------
| Endpoints públicos para integraciones externas y servicios básicos.
*/

// API de ping para monitoreo

use Illuminate\Support\Facades\Route;

Route::get('api/ping', function() {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0',
    ]);
})->name('api.ping');

// TODO: Agregar más APIs públicas según necesidad
// Route::get('api/exchange-rates/public', [ExchangeRateController::class, 'publicRates']);
// Route::get('api/games/public-rooms', [BingoController::class, 'publicRooms']);
