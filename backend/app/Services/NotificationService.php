<?php

namespace App\Services;

use App\Contracts\NotificationRepositoryInterface;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Servicio optimizado para gestión de notificaciones de usuario
 *
 * Proporciona una capa de abstracción robusta para manejar notificaciones
 * persistentes con caching, validación y mejor rendimiento.
 */
final class NotificationService
{
    private const CACHE_TTL = 300; // 5 minutos
    private const MAX_LIMIT = 100;
    private const DEFAULT_LIMIT = 20;

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
     * @throws InvalidArgumentException
     */
    public function create(
        string $userId,
        string $type,
        string $title,
        string $message,
        array $data = []
    ): Notification {
        $this->validateNotificationData($userId, $type, $title, $message);

        $notification = $this->notificationRepository->create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'is_read' => false,
        ]);

        $this->clearUserNotificationCache($userId);
        
        Log::info('Notification created', [
            'notification_id' => $notification->id,
            'user_id' => $userId,
            'type' => $type,
        ]);

        return $notification;
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
    public function getUserNotifications(array $filters = [], int $limit = self::DEFAULT_LIMIT): Collection
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            return collect([]);
        }

        $limit = min($limit, self::MAX_LIMIT);
        $cacheKey = "user_notifications_{$userId}_" . md5(serialize($filters) . $limit);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $filters, $limit) {
            return $this->notificationRepository->getByUser($userId, $filters, $limit);
        });
    }

    /**
     * Obtener notificaciones no leídas del usuario autenticado
     *
     * @param int $limit
     * @return Collection
     */
    public function getUnreadNotifications(int $limit = self::DEFAULT_LIMIT): Collection
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            return collect([]);
        }

        $limit = min($limit, self::MAX_LIMIT);
        $cacheKey = "unread_notifications_{$userId}_{$limit}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $limit) {
            return $this->notificationRepository->getUnreadByUser($userId, $limit);
        });
    }

    /**
     * Contar notificaciones no leídas del usuario autenticado
     *
     * @return int
     */
    public function getUnreadCount(): int
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            return 0;
        }

        $cacheKey = "unread_count_{$userId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
            return $this->notificationRepository->countUnreadByUser($userId);
        });
    }

    /**
     * Marcar una notificación como leída
     *
     * @param string $notificationId
     * @return bool
     */
    public function markAsRead(string $notificationId): bool
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            return false;
        }

        $notification = $this->notificationRepository->findById($notificationId);
        
        if (!$notification || $notification->user_id !== $userId) {
            Log::warning('Unauthorized notification access attempt', [
                'notification_id' => $notificationId,
                'user_id' => $userId,
            ]);
            return false;
        }

        $result = $this->notificationRepository->markAsRead($notificationId);
        
        if ($result) {
            $this->clearUserNotificationCache($userId);
        }

        return $result;
    }

    /**
     * Marcar todas las notificaciones del usuario como leídas
     *
     * @return int Número de notificaciones actualizadas
     */
    public function markAllAsRead(): int
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            return 0;
        }

        $result = $this->notificationRepository->markAllAsReadForUser($userId);
        
        if ($result > 0) {
            $this->clearUserNotificationCache($userId);
        }

        return $result;
    }

    /**
     * Eliminar una notificación
     *
     * @param string $notificationId
     * @return bool
     */
    public function delete(string $notificationId): bool
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            return false;
        }

        $notification = $this->notificationRepository->findById($notificationId);
        
        if (!$notification || $notification->user_id !== $userId) {
            Log::warning('Unauthorized notification deletion attempt', [
                'notification_id' => $notificationId,
                'user_id' => $userId,
            ]);
            return false;
        }

        $result = $this->notificationRepository->delete($notificationId);
        
        if ($result) {
            $this->clearUserNotificationCache($userId);
        }

        return $result;
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

    /**
     * Notificación al usuario cuando su depósito es aprobado o rechazado.
     */
    public function createDepositResultNotification(
        string $userId,
        string $status,       // COMPLETED | REJECTED
        float  $amount,
        string $currencySymbol,
        ?string $adminNotes = null,
    ): Notification {
        $approved = $status === 'COMPLETED';

        $title   = $approved ? '✅ Depósito Aprobado'   : '❌ Depósito Rechazado';
        $message = $approved
            ? "Tu depósito de {$currencySymbol}{$amount} ha sido acreditado a tu billetera."
            : "Tu depósito de {$currencySymbol}{$amount} fue rechazado." . ($adminNotes ? " Motivo: {$adminNotes}" : '');

        return $this->create(
            userId:  $userId,
            type:    'WALLET_UPDATED',
            title:   $title,
            message: $message,
            data:    [
                'movement_type'   => 'DEPOSIT',
                'movement_status' => $status,
                'amount'          => $amount,
                'currency_symbol' => $currencySymbol,
                'admin_notes'     => $adminNotes,
            ]
        );
    }

    /**
     * Notificación al usuario cuando su retiro es aprobado o rechazado.
     */
    public function createWithdrawalResultNotification(
        string $userId,
        string $status,       // COMPLETED | REJECTED
        float  $amount,
        string $currencySymbol,
        ?string $adminNotes = null,
    ): Notification {
        $approved = $status === 'COMPLETED';

        $title   = $approved ? '✅ Retiro Aprobado'   : '↩️ Retiro Rechazado — Saldo Reintegrado';
        $message = $approved
            ? "Tu retiro de {$currencySymbol}{$amount} fue procesado exitosamente."
            : "Tu retiro de {$currencySymbol}{$amount} fue rechazado y el saldo fue devuelto a tu billetera." . ($adminNotes ? " Motivo: {$adminNotes}" : '');

        return $this->create(
            userId:  $userId,
            type:    'WALLET_UPDATED',
            title:   $title,
            message: $message,
            data:    [
                'movement_type'   => 'WITHDRAWAL',
                'movement_status' => $status,
                'amount'          => $amount,
                'currency_symbol' => $currencySymbol,
                'admin_notes'     => $adminNotes,
            ]
        );
    }

    /**
     * Crear notificaciones masivas para múltiples usuarios
     *
     * @param array $userIds
     * @param string $type
     * @param string $title
     * @param string $message
     * @param array $data
     * @return Collection
     */
    public function createBulk(
        array $userIds,
        string $type,
        string $title,
        string $message,
        array $data = []
    ): Collection {
        $this->validateNotificationData('', $type, $title, $message);

        $notifications = collect();
        
        foreach ($userIds as $userId) {
            try {
                $notification = $this->create($userId, $type, $title, $message, $data);
                $notifications->push($notification);
            } catch (\Exception $e) {
                Log::error('Failed to create bulk notification', [
                    'user_id' => $userId,
                    'type' => $type,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $notifications;
    }

    /**
     * Obtener el ID del usuario autenticado
     *
     * @return string|null
     */
    private function getCurrentUserId(): ?string
    {
        $userId = Auth::id();
        return $userId ? (string) $userId : null;
    }

    /**
     * Validar datos de notificación
     *
     * @param string $userId
     * @param string $type
     * @param string $title
     * @param string $message
     * @throws InvalidArgumentException
     */
    private function validateNotificationData(
        string $userId,
        string $type,
        string $title,
        string $message
    ): void {
        if (!empty($userId) && !is_string($userId)) {
            throw new InvalidArgumentException('User ID must be a string');
        }

        if (empty($type) || !is_string($type)) {
            throw new InvalidArgumentException('Type is required and must be a string');
        }

        if (empty($title) || !is_string($title)) {
            throw new InvalidArgumentException('Title is required and must be a string');
        }

        if (empty($message) || !is_string($message)) {
            throw new InvalidArgumentException('Message is required and must be a string');
        }

        if (!array_key_exists($type, Notification::TYPES)) {
            Log::warning('Unknown notification type', ['type' => $type]);
        }
    }

    /**
     * Limpiar cache de notificaciones de usuario
     *
     * @param string $userId
     */
    private function clearUserNotificationCache(string $userId): void
    {
        $patterns = [
            "user_notifications_{$userId}_",
            "unread_notifications_{$userId}_",
            "unread_count_{$userId}",
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }
}
