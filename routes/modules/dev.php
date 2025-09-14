<?php

/*
|--------------------------------------------------------------------------
| Rutas de Desarrollo y Testing
|--------------------------------------------------------------------------
| Solo disponibles en entornos local y staging.
*/

// Demo del nuevo frontend

use Illuminate\Support\Facades\Route;

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