<?php

namespace App\Services;

use App\Contracts\NotificationRepositoryInterface;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio para gestión de notificaciones de usuario
 *
 * Proporciona una capa de abstracción para manejar notificaciones
 * persistentes que no se pierden al refrescar la página.
 */
final class NotificationService
{
    public function __construct(
        private readonly NotificationRepositoryInterface $notificationRepository,
    ) {}

    /**
     * Crear una nueva notificación para un usuario
     *
     * @param string $userId ID del usuario destinatario
     * @param string $type Tipo de notificación
     * @param string $title Título de la notificación
     * @param string $message Mensaje de la notificación
     * @param array $data Datos adicionales
     * @return Notification
     */
    public function create(
        string $userId,
        string $type,
        string $title,
        string $message,
        array $data = []
    ): Notification {
        return $this->notificationRepository->create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'is_read' => false,
        ]);
    }

    /**
     * Crear notificación de actualización de billetera
     *
     * @param string $userId
     * @param float $balance
     * @param string $currencySymbol
     * @param string $movementType
     * @param float $amount
     * @return Notification
     */
    public function createWalletNotification(
        string $userId,
        float $balance,
        string $currencySymbol,
        string $movementType,
        float $amount
    ): Notification {
        $title = match($movementType) {
            'DEPOSIT' => '💰 Depósito Recibido',
            'WITHDRAWAL' => '💸 Retiro Realizado',
            'CARD_PURCHASE' => '🎫 Compra de Cartones',
            default => '💳 Movimiento de Billetera'
        };

        $message = match($movementType) {
            'DEPOSIT' => "Has recibido {$currencySymbol}{$amount}",
            'WITHDRAWAL' => "Has retirado {$currencySymbol}{$amount}",
            'CARD_PURCHASE' => "Has gastado {$currencySymbol}{$amount} en cartones",
            default => "Movimiento de {$currencySymbol}{$amount}"
        };

        return $this->create(
            userId: $userId,
            type: 'WALLET_UPDATED',
            title: $title,
            message: $message,
            data: [
                'balance' => $balance,
                'currency_symbol' => $currencySymbol,
                'movement_type' => $movementType,
                'amount' => $amount,
            ]
        );
    }

    /**
     * Obtener notificaciones del usuario autenticado
     *
     * @param array $filters
     * @param int $limit
     * @return Collection
     */
    public function getUserNotifications(array $filters = [], int $limit = 20): Collection
    {
        $userId = (string) Auth::id();
        return $this->notificationRepository->getByUser($userId, $filters, $limit);
    }

    /**
     * Obtener notificaciones no leídas del usuario autenticado
     *
     * @param int $limit
     * @return Collection
     */
    public function getUnreadNotifications(int $limit = 20): Collection
    {
        $userId = (string) Auth::id();
        return $this->notificationRepository->getUnreadByUser($userId, $limit);
    }

    /**
     * Contar notificaciones no leídas del usuario autenticado
     *
     * @return int
     */
    public function getUnreadCount(): int
    {
        $userId = (string) Auth::id();
        return $this->notificationRepository->countUnreadByUser($userId);
    }

    /**
     * Marcar una notificación como leída
     *
     * @param string $notificationId
     * @return bool
     */
    public function markAsRead(string $notificationId): bool
    {
        // Verificar que la notificación pertenezca al usuario autenticado
        $notification = Notification::find($notificationId);
        
        if (!$notification || $notification->user_id !== Auth::id()) {
            return false;
        }

        return $this->notificationRepository->markAsRead($notificationId);
    }

    /**
     * Marcar todas las notificaciones del usuario como leídas
     *
     * @return int Número de notificaciones actualizadas
     */
    public function markAllAsRead(): int
    {
        $userId = (string) Auth::id();
        return $this->notificationRepository->markAllAsReadForUser($userId);
    }

    /**
     * Eliminar una notificación
     *
     * @param string $notificationId
     * @return bool
     */
    public function delete(string $notificationId): bool
    {
        // Verificar que la notificación pertenezca al usuario autenticado
        $notification = Notification::find($notificationId);
        
        if (!$notification || $notification->user_id !== Auth::id()) {
            return false;
        }

        return $this->notificationRepository->delete($notificationId);
    }

    /**
     * Limpiar notificaciones antiguas del sistema
     *
     * @param int $days Número de días para conservar
     * @return int Número de notificaciones eliminadas
     */
    public function cleanupOldNotifications(int $days = 30): int
    {
        return $this->notificationRepository->deleteOldNotifications($days);
    }

    /**
     * Formatear notificaciones para respuesta JSON
     *
     * @param Collection $notifications
     * @return array
     */
    public function formatNotifications(Collection $notifications): array
    {
        return $notifications->map(function (Notification $notification) {
            return [
                'id' => $notification->id,
                'type' => $notification->type,
                'title' => $notification->title,
                'message' => $notification->message,
                'data' => $notification->data,
                'is_read' => $notification->is_read,
                'read_at' => $notification->read_at?->toISOString(),
                'created_at' => $notification->created_at->toISOString(),
                'formatted_time' => $notification->created_at->diffForHumans(),
                'type_color' => $notification->getTypeColor(),
                'type_icon' => $notification->getTypeIcon(),
            ];
        })->toArray();
    }
}
