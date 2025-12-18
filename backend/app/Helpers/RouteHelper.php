<?php

use Illuminate\Support\Facades\Route;

/**
 * =======================================================
 * Rutas helper modernas para Laravel
 * =======================================================
 * Permiten definir grupos de rutas, CRUDs, API y rutas
 * con middleware de manera consistente y DRY.
 *
 * Convención:
 * - Key del array        → nombre de la ruta (kebab-case permitido)
 * - action (opcional)   → método real del controlador (camelCase)
 */

/**
 * Define un grupo de rutas con un controlador específico
 *
 * @param string $prefix Prefijo de la URI
 * @param string $name Prefijo de nombres de rutas
 * @param array $middleware Middlewares a aplicar
 * @param string $controller Controlador de las rutas
 * @param array $routes Lista de rutas
 *   [
 *     'route-name' => [
 *        'method' => 'get',
 *        'uri' => 'foo',
 *        'action' => 'methodName', // opcional
 *        'middleware' => []
 *     ]
 *   ]
 */
function route_group(
    string $prefix,
    string $name,
    array $middleware,
    string $controller,
    array $routes
): void {
    Route::prefix($prefix)
        ->as($name)
        ->middleware($middleware)
        ->controller($controller)
        ->group(function () use ($routes) {
            foreach ($routes as $routeName => $config) {
                $method = strtolower($config['method'] ?? 'get');
                $uri = $config['uri'] ?? $routeName;
                $action = $config['action'] ?? $routeName;
                $routeMiddlewares = $config['middleware'] ?? [];

                $route = Route::$method($uri, $action)->name($routeName);

                if (!empty($routeMiddlewares)) {
                    $route->middleware($routeMiddlewares);
                }
            }
        });
}

/**
 * Define un CRUD estándar para un controlador (convención Laravel)
 *
 * @param string $prefix Prefijo de la URI
 * @param string $controller Nombre del controlador
 * @param array $middleware Middlewares a aplicar
 * @param array $except Rutas a excluir ['index', 'store', ...]
 */
function crud_routes(
    string $prefix,
    string $controller,
    array $middleware = ['auth'],
    array $except = []
): void {
    $routes = [
        'index'   => ['method' => 'get',    'uri' => '',      'action' => 'index'],
        'store'   => ['method' => 'post',   'uri' => '',      'action' => 'store'],
        'show'    => ['method' => 'get',    'uri' => '{id}',  'action' => 'show'],
        'update'  => ['method' => 'put',    'uri' => '{id}',  'action' => 'update'],
        'destroy' => ['method' => 'delete', 'uri' => '{id}',  'action' => 'destroy'],
    ];

    // Filtrar rutas excluidas
    $routes = array_diff_key($routes, array_flip($except));

    route_group($prefix, "{$prefix}.", $middleware, $controller, $routes);
}

/**
 * Define rutas con múltiples middlewares organizados
 *
 * @param string $prefix Prefijo de la URI
 * @param string $name Prefijo de nombres de rutas
 * @param array $groups
 *   [
 *     [
 *       'controller' => Controller::class,
 *       'middleware' => [],
 *       'routes' => []
 *     ]
 *   ]
 */
function multi_middleware_routes(string $prefix, string $name, array $groups): void
{
    Route::prefix($prefix)->as($name)->group(function () use ($groups) {
        foreach ($groups as $group) {
            $controller = $group['controller'];
            $middlewares = $group['middleware'] ?? [];
            $routes = $group['routes'] ?? [];

            Route::middleware($middlewares)
                ->controller($controller)
                ->group(function () use ($routes) {
                    foreach ($routes as $routeName => $config) {
                        $method = strtolower($config['method'] ?? 'get');
                        $uri = $config['uri'] ?? $routeName;
                        $action = $config['action'] ?? $routeName;

                        $route = Route::$method($uri, $action)->name($routeName);

                        if (!empty($config['middleware'])) {
                            $route->middleware($config['middleware']);
                        }
                    }
                });
        }
    });
}

/**
 * Define rutas de API RESTful
 *
 * @param string $resource Nombre del recurso
 * @param string $controller Controlador
 * @param array $middleware Middlewares a aplicar
 */
function api_resource(
    string $resource,
    string $controller,
    array $middleware = ['auth:sanctum']
): void {
    Route::prefix("api/{$resource}")
        ->as("api.{$resource}.")
        ->middleware($middleware)
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
 *
 * @param array $roles Middlewares de roles
 * @param string $prefix Prefijo de la URI
 * @param string $controller Controlador
 * @param array $routes Rutas del grupo
 */
function role_protected_routes(
    array $roles,
    string $prefix,
    string $controller,
    array $routes
): void {
    $middleware = array_merge(['auth'], $roles);
    route_group($prefix, "{$prefix}.", $middleware, $controller, $routes);
}

/**
 * Define rutas condicionales por entorno
 *
 * @param array $environments Entornos permitidos ['local','staging']
 * @param callable $routeDefinition Closure que define las rutas
 */
function env_routes(array $environments, callable $routeDefinition): void
{
    if (app()->environment($environments)) {
        $routeDefinition();
    }
}

/**
 * Helper para generar nombres de rutas consistentes a partir de URIs
 *
 * @param string $uri URI de la ruta
 * @param string|null $prefix Prefijo opcional
 * @return string Nombre de ruta generado
 */
function route_name(string $uri, ?string $prefix = null): string
{
    $name = str_replace('/', '.', trim($uri, '/'));
    return $prefix ? "{$prefix}.{$name}" : $name;
}
