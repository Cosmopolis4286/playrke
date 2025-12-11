<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

/**
 * Class Kernel
 *
 * Clase central que gestiona todos los middleware de la aplicación.
 * Permite definir middleware globales, por grupos y de ruta.
 *
 * @package App\Http
 */
class Kernel extends HttpKernel
{
    /**
     * Middleware global de la aplicación.
     *
     * Se ejecuta en **todas las peticiones HTTP**.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        // Proxy y HTTPS
        \App\Http\Middleware\TrustProxies::class,
        // Manejo de CORS
        \Illuminate\Http\Middleware\HandleCors::class,
        // Mantenimiento
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        // Validación de tamaño de request
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        // Trim de strings
        \App\Http\Middleware\TrimStrings::class,
        // Convertir strings vacíos a null
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * Grupos de middleware.
     *
     * Se pueden asignar a rutas completas.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            // Encriptar cookies
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            // Iniciar sesión
            \Illuminate\Session\Middleware\StartSession::class,
            // Compartir errores de sesión con vistas
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            // Protección CSRF
            \App\Http\Middleware\VerifyCsrfToken::class,
            // Binding de rutas
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * Middleware de ruta.
     *
     * Se pueden usar directamente en rutas individuales o grupos de rutas.
     *
     * @var array<string, class-string|string>
     */
    protected $routeMiddleware = [
        // Middleware de autenticación
        'auth' => \App\Http\Middleware\Authenticate::class,

        // Middleware genérico de roles
        'role' => \App\Http\Middleware\RoleMiddleware::class,

        // Middleware avanzado de roles múltiples
        'multi-role' => \App\Http\Middleware\MultiRoleMiddleware::class,

        // Otros middleware que puedas tener
        // 'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        // 'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
    ];
}
