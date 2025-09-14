<?php

use App\Http\Controllers\{InformationController, DirectoryController};
use Illuminate\Support\Facades\Route;

require_once app_path('Helpers/RouteHelper.php');

/*
|--------------------------------------------------------------------------
| Rutas de Contenido e Información
|--------------------------------------------------------------------------
| Contenido estático, información legal, ayuda y directorios de datos.
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

// ====================================
// SERVICIOS DE TIEMPO DEL SERVIDOR
// ====================================
Route::middleware('auth')
    ->get('time-server/{timeZone?}', [InformationController::class, 'getTimeServer'])
    ->name('info.time-server');

// ====================================
// DIRECTORIO BANCARIO POR PAÍSES
// ====================================
route_group('directory', 'directory.', ['auth'], DirectoryController::class, [
    'country' => ['method' => 'get', 'uri' => '{countryCode}'],
]);