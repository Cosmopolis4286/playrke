<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador para gestión de notificaciones de usuario
 */
final class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {
    }

    /**
     * Eliminar todas las notificaciones del usuario
     */
    public function clearAll() : JsonResponse
    {
        $deletedCount = $this->notificationService->clearAll();

        return response()->json([
            'message' => 'Notificaciones eliminadas',
            'deleted_count' => $deletedCount,
            'unread_count' => 0,
        ]);
    }

    /**
     * Eliminar una notificación
     */
    public function destroy(string $id) : JsonResponse
    {
        $success = $this->notificationService->delete($id);

        if (! $success) {
            return response()->json([
                'message' => 'Notificación no encontrada o no autorizada',
            ], 404);
        }

        return response()->json([
            'message' => 'Notificación eliminada',
            'unread_count' => $this->notificationService->getUnreadCount(),
        ]);
    }

    /**
     * Obtener notificaciones del usuario autenticado
     */
    public function index(Request $request) : JsonResponse
    {
        $filters = $request->only(['type', 'is_read']);
        $limit = min($request->get('limit', 20), 50); // máximo 50

        $notifications = $this->notificationService->getUserNotifications($filters, $limit);
        $formatted = $this->notificationService->formatNotifications($notifications);

        return response()->json([
            'data' => $formatted,
            'unread_count' => $this->notificationService->getUnreadCount(),
        ]);
    }

    /**
     * Marcar todas las notificaciones como leídas
     */
    public function markAllAsRead() : JsonResponse
    {
        $markedCount = $this->notificationService->markAllAsRead();

        return response()->json([
            'message' => 'Todas las notificaciones marcadas como leídas',
            'marked_count' => $markedCount,
            'unread_count' => $this->notificationService->getUnreadCount(),
        ]);
    }

    /**
     * Marcar una notificación como leída
     */
    public function markAsRead(string $id) : JsonResponse
    {
        $success = $this->notificationService->markAsRead($id);

        if (! $success) {
            return response()->json([
                'message' => 'Notificación no encontrada o no autorizada',
            ], 404);
        }

        return response()->json([
            'message' => 'Notificación marcada como leída',
            'unread_count' => $this->notificationService->getUnreadCount(),
        ]);
    }

    /**
     * Obtener notificaciones no leídas
     */
    public function unread(Request $request) : JsonResponse
    {
        $limit = min($request->get('limit', 20), 50);

        $notifications = $this->notificationService->getUnreadNotifications($limit);
        $formatted = $this->notificationService->formatNotifications($notifications);

        return response()->json([
            'data' => $formatted,
            'unread_count' => $this->notificationService->getUnreadCount(),
        ]);
    }

    /**
     * Obtener contador de notificaciones no leídas
     */
    public function unreadCount() : JsonResponse
    {
        return response()->json([
            'unread_count' => $this->notificationService->getUnreadCount(),
        ]);
    }
}
