<?php

namespace App\Contracts;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Collection;

interface NotificationRepositoryInterface
{
    /**
     * Crear una nueva notificación
     *
     * @param array $data
     * @return Notification
     */
    public function create(array $data): Notification;

    /**
     * Obtener notificaciones de un usuario
     *
     * @param string $userId
     * @param array $filters
     * @param int $limit
     * @return Collection
     */
    public function getByUser(string $userId, array $filters = [], int $limit = 20): Collection;

    /**
     * Obtener notificaciones no leídas de un usuario
     *
     * @param string $userId
     * @param int $limit
     * @return Collection
     */
    public function getUnreadByUser(string $userId, int $limit = 20): Collection;

    /**
     * Marcar notificación como leída
     *
     * @param string $notificationId
     * @return bool
     */
    public function markAsRead(string $notificationId): bool;

    /**
     * Marcar todas las notificaciones de un usuario como leídas
     *
     * @param string $userId
     * @return int Número de notificaciones actualizadas
     */
    public function markAllAsReadForUser(string $userId): int;

    /**
     * Contar notificaciones no leídas de un usuario
     *
     * @param string $userId
     * @return int
     */
    public function countUnreadByUser(string $userId): int;

    /**
     * Eliminar notificaciones antiguas
     *
     * @param int $days Número de días para conservar
     * @return int Número de notificaciones eliminadas
     */
    public function deleteOldNotifications(int $days = 30): int;

    /**
     * Eliminar una notificación
     *
     * @param string $notificationId
     * @return bool
     */
    public function delete(string $notificationId): bool;

    /**
     * Eliminar todas las notificaciones de un usuario
     *
     * @param string $userId
     * @return int Número de notificaciones eliminadas
     */
    public function deleteAllForUser(string $userId): int;

    /**
     * Encontrar una notificación por su ID
     *
     * @param string $notificationId
     * @return Notification|null
     */
    public function findById(string $notificationId): ?Notification;
}
