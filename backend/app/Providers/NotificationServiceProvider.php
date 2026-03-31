<?php

namespace App\Providers;

use App\Services\NotificationService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Compartir notificaciones globalmente en todas las vistas
        View::composer('*', function ($view) {
            // Solo para usuarios autenticados
            if (auth()->check()) {
                try {
                    $notificationService = app(NotificationService::class);
                    
                    // Obtener notificaciones no leídas
                    $unreadNotifications = $notificationService->getUnreadNotifications(10);
                    $unreadCount = $notificationService->getUnreadCount();
                    $formattedNotifications = $notificationService->formatNotifications($unreadNotifications);

                    // Compartir con la vista
                    $view->with('unreadNotifications', $unreadNotifications);
                    $view->with('unreadCount', $unreadCount);
                    $view->with('formattedNotifications', $formattedNotifications);

                } catch (\Exception $e) {
                    \Log::error('NotificationServiceProvider: Error al cargar notificaciones', [
                        'view' => $view->getName(),
                        'error' => $e->getMessage(),
                        'user_id' => auth()->id(),
                    ]);

                    // Compartir valores vacíos en caso de error
                    $view->with('unreadNotifications', collect([]));
                    $view->with('unreadCount', 0);
                    $view->with('formattedNotifications', []);
                }
            } else {
                // Usuario no autenticado
                $view->with('unreadNotifications', collect([]));
                $view->with('unreadCount', 0);
                $view->with('formattedNotifications', []);
            }
        });
    }
}
