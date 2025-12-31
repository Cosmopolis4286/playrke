<?php

use App\Http\Controllers\Shared\InformationController;

/*
|--------------------------------------------------------------------------
| Rutas de Contenido e Información
|--------------------------------------------------------------------------
| Contenido estático, información legal, ayuda, etc.
*/

// ====================================
// INFORMACIÓN LEGAL Y CORPORATIVA
// ====================================
route_group('info', 'info.', ['auth'], InformationController::class, [
    'terms' => ['method' => 'get', 'uri' => 'terms'],
    'privacy' => ['method' => 'get', 'uri' => 'privacy'],
    'about' => ['method' => 'get', 'uri' => 'about'],
    'help-center' => ['method' => 'get', 'uri' => 'help-center'],
    'power-of-knowledge' => ['method' => 'get', 'uri' => 'power-of-knowledge'],
]);
