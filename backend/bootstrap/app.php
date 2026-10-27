<?php

use App\Exceptions\Domain\DomainException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

return Application::configure(
    basePath: dirname(__DIR__),
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
    ->withMiddleware(function(Middleware $middleware) {
        /*
         * Alias de middleware personalizados
         * Permiten usarlos de forma sencilla en las rutas (web/api)
         */
        $middleware->alias([
            'multiPermission' => App\Http\Middleware\MultiPermissionMiddleware::class,
            'role' => App\Http\Middleware\RoleMiddleware::class,
            'publicToken' => App\Http\Middleware\PublicTokenMiddleware::class,
            'validate.token' => \App\Http\Middleware\ValidateToken::class,
            'tos' => \App\Http\Middleware\RequireTosAcceptance::class,
            'block-during-impersonation' => \App\Http\Middleware\BlockDuringImpersonation::class,
        ]);

        /*
         * Habilitar sesiones en rutas API para autenticación SPA con Sanctum
         * Esto permite que las rutas con middleware 'web' en api.php
         * tengan acceso a la sesión
         */
        $middleware->statefulApi();

        /*
         * Configurar el grupo de middleware 'api' para incluir Sanctum
         * EnsureFrontendRequestsAreStateful permite autenticación basada en cookies
         */
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \App\Http\Middleware\SetUserTimezone::class,
        ]);

        /* Habiltar Middleware de Inetia */
        $middleware->web(append: [
            \App\Http\Middleware\EnforceImpersonationTimeout::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\ShareNotifications::class,
            \App\Http\Middleware\SetUserTimezone::class,
        ]);

        /*
         * Cabeceras de seguridad HTTP (X-Frame-Options, HSTS, etc.) en TODAS
         * las respuestas, web y api — ver App\Http\Middleware\SecurityHeaders.
         */
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        /*
         * Habilitar credenciales en CORS para permitir cookies cross-origin
         */
        $middleware->validateCsrfTokens(except: [
            // Excluir rutas si es necesario, pero generalmente no se necesita
        ]);
    })
    ->withExceptions(function(Exceptions $exceptions) {
        /*
         * Manejo global de excepciones.
         *
         * Tres categorías, cada una con su propia rama, en orden de
         * especificidad (Laravel prueba los render() en orden hasta que uno
         * devuelve una respuesta):
         *
         *   1. DomainException custom (App\Exceptions\Domain\DomainException)
         *      — regla de negocio con error-code + status propios.
         *   2. \DomainException NATIVO de PHP — regla de negocio con mensaje
         *      humano directo (~34 archivos, ej. WalletService), sin
         *      error-code ni status propio: siempre 422.
         *   3. Cualquier otro Throwable — código roto o fallo de
         *      infraestructura (QueryException, TypeError, excepción de
         *      librería de terceros...). Antes de este fix, exponía
         *      $e->getMessage() crudo en producción sin ninguna
         *      sanitización — para una QueryException real eso es el SQL
         *      completo, los bindings, y el nombre de tabla/columna que
         *      violó una constraint, servidos tal cual al cliente en el
         *      JSON de respuesta. Ahora: se loguea completo (mensaje +
         *      stack) y al cliente solo llega un mensaje genérico fijo en
         *      producción; en dev/testing se sigue mostrando el mensaje real
         *      para depurar.
         *
         * Cada rama además bifurca por tipo de respuesta esperada
         * (JSON para API/fetch, Inertia con la página Errors/Error para
         * navegación normal) — antes, una DomainException disparada durante
         * una navegación Inertia normal devolvía JSON puro sin el header
         * X-Inertia que el cliente espera, rompiendo el layout SPA en vez de
         * mostrar un error dentro de la app.
         */
        $exceptions->dontReport(DomainException::class);

        $wantsJson = function($request) : bool {
            return $request->is('api/*') || $request->expectsJson();
        };

        $renderInertiaError = function($request, int $status, ?string $message) {
            // Este render() se dispara DESDE el exception handler, fuera del
            // pipeline normal de middlewares 'web' — ShareErrorsFromSession
            // (que comparte $errors a TODAS las vistas Blade) nunca llega a
            // ejecutarse para esta respuesta. app.blade.php (raíz de Inertia)
            // siempre monta <x-shared.modal> con el form de cambio de
            // contraseña (auth-layout.blade.php), cuyo x-form.input usa
            // $errors->has() sin fallback: sin este share manual, CUALQUIER
            // error real (500/403/404/503) provocaba un segundo error fatal
            // ("Undefined variable $errors") al intentar mostrar la página
            // de error, ocultando el error original tras un crash en cascada.
            if (! view()->shared('errors')) {
                view()->share('errors', session()->get('errors') ?? new \Illuminate\Support\ViewErrorBag);
            }

            return Inertia::render('Errors/Error', [
                'status' => $status,
                'message' => $message,
                'url' => $request->fullUrl(),
            ])->toResponse($request)->setStatusCode($status);
        };

        // 1. DomainException custom — status + mensaje declarados por la
        // propia excepción (ver DomainException::$httpStatus y
        // App\Exceptions\Handler::resolveMessage()).
        $exceptions->render(function(DomainException $e, $request) use ($wantsJson, $renderInertiaError) {
            $handler = app(\App\Exceptions\Handler::class);
            $errorCode = $e->getMessage();
            $message = $handler->resolveMessage($errorCode, $e->replacements());
            $status = $e->httpStatus();

            if ($wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'error_code' => $errorCode,
                    'message' => $message,
                ], $status);
            }

            return $renderInertiaError($request, $status, $message);
        });

        // 2. \DomainException nativo de PHP — mismo status fijo (422) que ya
        // usaba este patrón, mensaje humano directo (nunca es un error-code
        // a traducir). get_class() exacto (no instanceof) para no capturar
        // subclases de terceros que hereden de \DomainException con otra
        // semántica.
        $exceptions->render(function(Throwable $e, $request) use ($wantsJson, $renderInertiaError) {
            if (get_class($e) !== \DomainException::class) {
                return null;
            }

            if ($wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return $renderInertiaError($request, 422, $e->getMessage());
        });

        // Loguea TODA excepción no capturada por las ramas de negocio
        // (DomainException custom y nativo están en dontReport / ya
        // manejadas arriba) con su mensaje y stack completos — esta es la
        // única fuente de verdad para diagnosticar un bug real, ya que el
        // cliente en producción ya no recibe el mensaje crudo (rama 3, abajo).
        // Log::critical (no ->error) a propósito: el canal slack de
        // config/logging.php solo dispara desde 'critical' hacia arriba
        // (LOG_SLACK_LEVEL) — si algo llega hasta aquí es código roto o
        // infraestructura caída en producción, exactamente lo que amerita
        // una alerta en tiempo real, no solo una línea en el log de archivo.
        $exceptions->report(function(Throwable $e) {
            Log::critical('Excepción no capturada: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        });

        // 3. Cualquier otra excepción — código roto o fallo de
        // infraestructura, nunca una regla de negocio esperada.
        $exceptions->render(function(Throwable $e, $request) use ($wantsJson, $renderInertiaError) {
            $status = match (true) {
                $e instanceof \Illuminate\Auth\AuthenticationException => 401,
                $e instanceof \Illuminate\Auth\Access\AuthorizationException => 403,
                $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException => 404,
                $e instanceof \Illuminate\Validation\ValidationException => 422,
                $e instanceof \Symfony\Component\HttpKernel\Exception\HttpException => $e->getStatusCode(),
                default => 500,
            };

            // Mensaje crudo SOLO fuera de producción (dev/testing) — en
            // producción, cualquier status ≥500 (fallo real, no un rechazo
            // HTTP legítimo tipo 401/403/404/422 que YA trae su propio
            // mensaje seguro desde Laravel) se sustituye por un mensaje
            // genérico fijo. El mensaje real siempre queda en el log (rama
            // report() de arriba), nunca se pierde — solo deja de viajar al
            // cliente.
            $exposeRawMessage = ! app()->environment('production') || $status < 500;
            $message = $exposeRawMessage
                ? ($e->getMessage() ?: 'Error interno del servidor.')
                : 'Ocurrió un error inesperado. Por favor intenta nuevamente.';

            if ($wantsJson($request)) {
                $response = [
                    'success' => false,
                    'message' => $message,
                ];

                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    $response['errors'] = $e->errors();
                }

                return response()->json($response, $status);
            }

            if (in_array($status, [403, 404, 500, 503], strict: true)) {
                return $renderInertiaError($request, $status, $message);
            }

            return null;
        });
    })
    ->create();
