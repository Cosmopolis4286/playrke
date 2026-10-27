<?php

namespace App\Providers;

use App\Services\NotificationService;
use Exception;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot() : void
    {
        // Inyecta en todas las vistas las últimas 25 notificaciones (leídas + no leídas)
        // y el unread_count. El navbar las renderiza inline sin API call al primer abrir.
        View::composer('*', function($view) {
            if (! auth()->check()) {
                $view->with('navNotifications', []);
                $view->with('navUnreadCount', 0);

                return;
            }

            try {
                $notificationService = app(NotificationService::class);
                $recent = $notificationService->getUserNotifications([], 25);
                $view->with('navNotifications', $notificationService->formatNotifications($recent));
                $view->with('navUnreadCount', $notificationService->getUnreadCount());
            } catch (Exception $e) {
                $view->with('navNotifications', []);
                $view->with('navUnreadCount', 0);
            }
        });
    }

    /**
     * Register services.
     */
    public function register() : void
    {
    }
}
