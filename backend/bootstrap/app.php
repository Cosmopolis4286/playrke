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
            'block-during-impersonation' => \App\Http\Middleware\BlockDuringImpersonation::class,
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
            \App\Http\Middleware\EnforceImpersonationTimeout::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\ShareNotifications::class,
            \App\Http\Middleware\SetUserTimezone::class,
        ]);

        /**
         * Cabeceras de seguridad HTTP (X-Frame-Options, HSTS, etc.) en TODAS
         * las respuestas, web y api — ver App\Http\Middleware\SecurityHeaders.
         */
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

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
         * 'ADMINISTRATOR_NOT_FOUND') o, en varias clases, texto humano
         * directo — ambos casos se resuelven vía App\Exceptions\Handler::
         * resolveMessage() (trans('errors.{code}') con fallback al propio
         * string, que para texto humano ya es el mensaje correcto).
         *
         * El status HTTP YA NO se resuelve desde un mapa centralizado
         * (Handler::resolveStatusCode(), un match plano que se desincronizó
         * silenciosamente de las clases reales — 44 de ~65 códigos usados
         * quedaron sin mapear y caían al 400 genérico sin que nada lo
         * detectara). Cada excepción declara su propio status al construirse
         * (ver DomainException::$httpStatus) — imposible que se desincronice
         * porque vive junto al código que lo dispara.
         *
         * Antes de este fix, DomainException no estaba registrada en ningún
         * lado de bootstrap/app.php (Laravel 11 ya NO auto-registra
         * app/Exceptions/Handler.php como hacían las versiones ≤10) y
         * cualquier `throw AlgoException::...()` cayó siempre al 500
         * genérico de abajo, perdiendo el código HTTP real y el mensaje
         * traducido.
         */
        $exceptions->dontReport(DomainException::class);

        $exceptions->render(function (DomainException $e, $request) {
            $handler = app(\App\Exceptions\Handler::class);
            $errorCode = $e->getMessage();

            return response()->json([
                'success'    => false,
                'error_code' => $errorCode,
                'message'    => $handler->resolveMessage($errorCode),
            ], $e->httpStatus());
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
                } elseif (get_class($e) === \DomainException::class) {
                    // \DomainException NATIVO de PHP (distinto de la clase
                    // custom App\Exceptions\Domain\DomainException, manejada
                    // arriba con su propio mapa de error-codes). Patrón usado
                    // en ~17 archivos del proyecto (WalletService y otros)
                    // para "regla de negocio violada" — el mensaje YA es
                    // texto humano listo para mostrar (no un código a
                    // traducir), así que va directo a la respuesta. Antes
                    // caía al fallback 500 genérico, mostrando "Error
                    // inesperado" en vez del motivo real (ej. "Saldo
                    // insuficiente", "Este lote ya tiene premio pagado").
                    // get_class() exacto (no instanceof) para no capturar
                    // subclases de terceros que hereden de \DomainException
                    // con otra semántica.
                    $status = 422;
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
