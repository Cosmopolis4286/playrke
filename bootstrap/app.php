<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(
    basePath: dirname(__DIR__)
)
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php', // 👈 Añadido para APIs
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up', // Ruta health-check para monitoreo
    )
    ->withMiddleware(function (Middleware $middleware) {
        /**
         * Alias de middleware personalizados
         * Permiten usarlos de forma sencilla en las rutas (web/api)
         */
        $middleware->alias([
            'multiRole' => App\Http\Middleware\MultiRoleMiddleware::class,
            'role' => App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        /**
         * Manejo global de excepciones
         *
         * Aquí puedes registrar reportes personalizados,
         * logging adicional o vistas JSON para API.
         */

        // Ejemplo: loguear todas las excepciones en un canal custom
        $exceptions->report(function (Throwable $e) {
            // Puedes usar Log::channel('slack')->error($e->getMessage());
        });

        // Ejemplo: respuesta JSON para APIs en modo producción
        $exceptions->render(function (Throwable $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 500);
            }
        });
    })
    ->create();
