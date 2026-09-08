<?php

/*
|--------------------------------------------------------------------------
| Rutas de Desarrollo y Testing
|--------------------------------------------------------------------------
| Solo disponibles en entornos local y staging.
*/

// Demo del nuevo frontend

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\DemoCredentialsController;

// Credenciales demo para el componente DemoCredentials en Login.
// throttle:30,1 — gateado además por config('app.demo_credentials_enabled')
// + isLocal() en el controller, pero un límite barato evita que quede como
// vector de enumeración/fuerza bruta si algún día el gate falla.
Route::get('dev/demo-credentials', [DemoCredentialsController::class, 'index'])
    ->middleware('throttle:30,1')
    ->name('dev.demo-credentials');

Route::get('demo', function () {
    return view('demo');
})->name('demo');

// Ruta de prueba para websockets
Route::get('websocket-test', function () {
    return view('websocket-test');
})->name('websocket.test');

// Debug de rutas
Route::get('debug/routes', function () {
    $routes = collect(Route::getRoutes())->map(function ($route) {
        return [
            'method' => implode('|', $route->methods()),
            'uri' => $route->uri(),
            'name' => $route->getName(),
            'action' => $route->getActionName(),
        ];
    });
    
    return response()->json($routes, 200, [], JSON_PRETTY_PRINT);
})->name('debug.routes');