<?php

use App\Exceptions\Domain\DomainException;
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
    ->withProviders([
        \App\Providers\NotificationServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        /**
         * Alias de middleware personalizados
         * Permiten usarlos de forma sencilla en las rutas (web/api)
         */
        $middleware->alias([
            'multiPermission' => App\Http\Middleware\MultiPermissionMiddleware::class,
            'role'            => App\Http\Middleware\RoleMiddleware::class,
            'publicToken'     => App\Http\Middleware\PublicTokenMiddleware::class,
            'validate.token'  => \App\Http\Middleware\ValidateToken::class,
            'tos'             => \App\Http\Middleware\RequireTosAcceptance::class,
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
            \App\Http\Middleware\SetUserTimezone::class,
        ]);

        /** Habiltar Middleware de Inetia*/
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\ShareNotifications::class,
            \App\Http\Middleware\SetUserTimezone::class,
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
         * Manejo global de excepciones.
         *
         * DomainException (App\Exceptions\Domain\DomainException) se maneja
         * PRIMERO y aparte: su `message` es un ERROR CODE (ej.
         * 'ADMINISTRATOR_NOT_FOUND'), no texto para el usuario — se resuelve
         * a status HTTP + mensaje traducido vía App\Exceptions\Handler
         * (resolveStatusCode()/resolveMessage()), reutilizado aquí en vez de
         * duplicar el mapa de códigos. Antes de este fix, DomainException no
         * estaba registrada en ningún lado de bootstrap/app.php (Laravel 11
         * ya NO auto-registra app/Exceptions/Handler.php como hacían las
         * versiones ≤10) y cualquier `throw AlgoException::...()` en
         * cualquier service cayó siempre al 500 genérico de abajo, perdiendo
         * el código HTTP real (403/404/409/422) y el mensaje traducido.
         */
        $exceptions->dontReport(DomainException::class);

        $exceptions->render(function (DomainException $e, $request) {
            $handler = app(\App\Exceptions\Handler::class);
            $errorCode = $e->getMessage();

            return response()->json([
                'success'    => false,
                'error_code' => $errorCode,
                'message'    => $handler->resolveMessage($errorCode),
            ], $handler->resolveStatusCode($errorCode));
        });

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

                $response = [
                    'success' => false,
                    'message' => $e->getMessage(),
                ];

                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    $response['errors'] = $e->errors();
                }

                return response()->json($response, $status);
            }
        });
    })
    ->create();
