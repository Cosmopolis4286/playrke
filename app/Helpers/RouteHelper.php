<?php

use Illuminate\Support\Facades\Route;

/**
 * Helper moderno para organización de rutas
 * Facilita la definición de rutas complejas con patrones consistentes
 */

/**
 * Define un grupo de rutas con un controlador específico
 */
function route_group(string $prefix, string $name, array $middleware, string $controller, array $routes): void
{
    Route::prefix($prefix)
        ->as($name)
        ->middleware($middleware)
        ->controller($controller)
        ->group(function () use ($routes) {
            foreach ($routes as $routeName => $config) {
                $method = $config['method'];
                $uri = $config['uri'];
                $middlewares = $config['middleware'] ?? [];
                
                $route = Route::$method($uri, $routeName)->name($routeName);
                
                if (!empty($middlewares)) {
                    $route->middleware($middlewares);
                }
            }
        });
}

/**
 * Define un CRUD estándar para un controlador
 */
function crud_routes(string $prefix, string $controller, array $middleware = ['auth'], array $except = []): void
{
    $routes = [
        'index' => ['method' => 'get', 'uri' => ''],
        'create' => ['method' => 'post', 'uri' => ''],
        'show' => ['method' => 'get', 'uri' => '{id}'],
        'update' => ['method' => 'put', 'uri' => '{id}'],
        'destroy' => ['method' => 'delete', 'uri' => '{id}'],
    ];

    // Filtrar rutas excluidas
    $routes = array_diff_key($routes, array_flip($except));

    route_group($prefix, "{$prefix}.", $middleware, $controller, $routes);
}

/**
 * Define rutas con middleware múltiples organizados
 */
function multi_middleware_routes(string $prefix, string $name, array $groups): void
{
    Route::prefix($prefix)->as($name)->group(function () use ($groups) {
        foreach ($groups as $group) {
            Route::middleware($group['middleware'])
                ->controller($group['controller'])
                ->group(function () use ($group) {
                    foreach ($group['routes'] as $routeName => $config) {
                        $method = $config['method'];
                        $uri = $config['uri'];
                        
                        Route::$method($uri, $routeName)->name($routeName);
                    }
                });
        }
    });
}

/**
 * Define rutas de API RESTful
 */
function api_resource(string $resource, string $controller, array $middleware = ['auth:sanctum']): void
{
    Route::middleware($middleware)
        ->prefix("api/{$resource}")
        ->as("api.{$resource}.")
        ->controller($controller)
        ->group(function () {
            Route::get('', 'index')->name('index');
            Route::post('', 'store')->name('store');
            Route::get('{id}', 'show')->name('show');
            Route::put('{id}', 'update')->name('update');
            Route::delete('{id}', 'destroy')->name('destroy');
        });
}

/**
 * Define rutas protegidas por roles específicos
 */
function role_protected_routes(array $roles, string $prefix, string $controller, array $routes): void
{
    $middleware = array_merge(['auth'], $roles);
    
    route_group($prefix, "{$prefix}.", $middleware, $controller, $routes);
}

/**
 * Define rutas condicionales por entorno
 */
function env_routes(array $environments, callable $routeDefinition): void
{
    if (app()->environment($environments)) {
        $routeDefinition();
    }
}