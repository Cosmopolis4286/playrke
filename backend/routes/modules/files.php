<?php

use App\Http\Controllers\Utils\IframeController;

/*
|--------------------------------------------------------------------------
| Rutas de Manejo de Archivos
|--------------------------------------------------------------------------
| Gestión de archivos protegidos, imágenes e iframes.
*/

// ====================================
// IFRAMES PROTEGIDOS
// ====================================
route_group('iframe', 'iframe.', ['auth'], IframeController::class, [
    'index' => ['method' => 'get', 'uri' => ''],
    'game-rooms-route' => ['method' => 'get', 'uri' => 'get/route/game-rooms/{userToken}'],
]);
