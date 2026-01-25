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
            'publicToken' => App\Http\Middleware\PublicTokenMiddleware::class,
        ]);

        /**
         * Habilitar sesiones en rutas API para autenticación SPA con Sanctum
         * Esto permite que las rutas con middleware 'web' en api.php
         * tengan acceso a la sesión
         */
        $middleware->statefulApi();

        /**
         * Configurar el grupo de middleware 'api' para incluir Sanctum
         * EnsureFrontendRequestsAreStateful permite autenticación basada en cookies
         */
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        /** Habiltar Middleware de Inetia*/
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

        /**
         * Habilitar credenciales en CORS para permitir cookies cross-origin
         */
        $middleware->validateCsrfTokens(except: [
            // Excluir rutas si es necesario, pero generalmente no se necesita
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

        // Respuesta JSON para APIs
        $exceptions->render(function (Throwable $e, $request) {
            // Para peticiones API, siempre devolver JSON
            if ($request->is('api/*') || $request->expectsJson()) {
                // Determinar el status code según el tipo de excepción
                $status = 500;

                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    $status = 401;
                } elseif ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                    $status = 403;
                } elseif ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                    $status = 404;
                } elseif ($e instanceof \Illuminate\Validation\ValidationException) {
                    $status = 422;
                } elseif ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                    $status = $e->getStatusCode();
                }

                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], $status);
            }
        });
    })
    ->create();
