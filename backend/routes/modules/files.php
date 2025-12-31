<?php

use App\Http\Controllers\Utils\FileController;
use App\Http\Controllers\Utils\IframeController;

/*
|--------------------------------------------------------------------------
| Rutas de Manejo de Archivos
|--------------------------------------------------------------------------
| Gestión de archivos protegidos, imágenes e iframes.
*/

// ====================================
// IMÁGENES PROTEGIDAS
// ====================================
route_group('image', 'image.', ['auth'], FileController::class, [
    'payment-prizes' => ['method' => 'get', 'uri' => 'payments/prizes/{filename}'],
    'payments' => ['method' => 'get', 'uri' => 'payments/{filename}'],
]);

// ====================================
// IFRAMES PROTEGIDOS
// ====================================
route_group('iframe', 'iframe.', ['auth'], IframeController::class, [
    'index' => ['method' => 'get', 'uri' => ''],
    'game-rooms-route' => ['method' => 'get', 'uri' => 'get/route/game-rooms/{userToken}'],
]);
