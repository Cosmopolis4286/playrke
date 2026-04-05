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
        // Solo para usuarios autenticados
        if (auth()->check()) {
            try {
                // Obtener notificaciones no leídas
                $unreadNotifications = $this->notificationService->getUnreadNotifications(10);
                $unreadCount = $this->notificationService->getUnreadCount();

                // Compartir con todas las vistas
                View::share('unreadNotifications', $unreadNotifications);
                View::share('unreadCount', $unreadCount);
                View::share('formattedNotifications', $this->notificationService->formatNotifications($unreadNotifications));

            } catch (\Exception $e) {
                \Log::error('ShareNotifications: Error al cargar notificaciones', [
                    'error' => $e->getMessage(),
                    'user_id' => auth()->id(),
                ]);

                // Compartir valores vacíos en caso de error
                View::share('unreadNotifications', collect([]));
                View::share('unreadCount', 0);
                View::share('formattedNotifications', []);
            }
        } else {
            // Usuario no autenticado
            View::share('unreadNotifications', collect([]));
            View::share('unreadCount', 0);
            View::share('formattedNotifications', []);
        }

        return $next($request);
    }
}
