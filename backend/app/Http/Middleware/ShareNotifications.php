<?php

namespace App\Http\Middleware;

use App\Services\NotificationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para compartir notificaciones globalmente en todas las vistas
 */
class ShareNotifications
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // navNotifications y navUnreadCount son inyectados por NotificationServiceProvider.
        // Este middleware es un no-op mantenido por compatibilidad.
        return $next($request);
    }
}
