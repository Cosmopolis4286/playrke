<?php

/*
|--------------------------------------------------------------------------
| Notificaciones Routes
|--------------------------------------------------------------------------
| Rutas para gestión de notificaciones persistentes
*/

use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

// Middleware de autenticación y verificación CSRF
Route::middleware(['auth', 'web'])->prefix('notifications')->group(function () {
    
    // Obtener todas las notificaciones del usuario
    Route::get('/', [NotificationController::class, 'index'])->name('notifications.index');
    
    // Obtener notificaciones no leídas
    Route::get('unread', [NotificationController::class, 'unread'])->name('notifications.unread');
    
    // Obtener contador de notificaciones no leídas
    Route::get('unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    
    // Marcar notificación como leída
    Route::patch('{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    
    // Marcar todas las notificaciones como leídas
    Route::patch('read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    
    // Eliminar notificación
    Route::delete('{id}', [NotificationController::class, 'destroy'])->name('notifications.delete');
});
