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
        // Las notificaciones se cargan desde la API en el navbar (datos siempre frescos).
        // Solo compartimos el unread_count para el badge inicial antes de que cargue JS.
        if (auth()->check()) {
            try {
                $unreadCount = $this->notificationService->getUnreadCount();
                View::share('unreadCount', $unreadCount);
            } catch (\Exception $e) {
                View::share('unreadCount', 0);
            }
        } else {
            View::share('unreadCount', 0);
        }

        return $next($request);
    }
}
