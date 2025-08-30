<?php
// app/Helpers/RouteHelper.php

use Illuminate\Support\Facades\Route;

/**
 * Define rutas de un controlador usando un “mapa de rutas”.
 *
 * @param string $prefix        Prefijo de ruta
 * @param string $as            Nombre base de la ruta
 * @param array  $groups        Array de grupos con middleware y rutas
 */
function mapControllerRoutes(string $prefix, string $as, array $groups): void
{
    Route::prefix($prefix)->as($as)->group(function () use ($groups) {
        foreach ($groups as $group) {
            Route::middleware($group['middleware'])
                ->controller($group['controller'])
                ->group(function () use ($group) {
                    foreach ($group['routes'] as $name => $route) {
                        Route::{$route['method']}($route['uri'], $name)->name($name);
                    }
                });
        }
    });
}
