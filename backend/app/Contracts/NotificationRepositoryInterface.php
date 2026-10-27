<?php

namespace App\Contracts;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Collection;

interface NotificationRepositoryInterface
{
    /**
     * Contar notificaciones no leídas de un usuario
     */
    public function countUnreadByUser(string $userId) : int;

    /**
     * Crear una nueva notificación
     */
    public function create(array $data) : Notification;

    /**
     * Eliminar una notificación
     */
    public function delete(string $notificationId) : bool;

    /**
     * Eliminar todas las notificaciones de un usuario
     *
     * @return int Número de notificaciones eliminadas
     */
    public function deleteAllForUser(string $userId) : int;

    /**
     * Eliminar notificaciones antiguas
     *
     * @param int $days Número de días para conservar
     *
     * @return int Número de notificaciones eliminadas
     */
    public function deleteOldNotifications(int $days = 30) : int;

    /**
     * Encontrar una notificación por su ID
     */
    public function findById(string $notificationId) : ?Notification;

    /**
     * Obtener notificaciones de un usuario
     */
    public function getByUser(string $userId, array $filters = [], int $limit = 20) : Collection;

    /**
     * Obtener notificaciones no leídas de un usuario
     */
    public function getUnreadByUser(string $userId, int $limit = 20) : Collection;

    /**
     * Marcar todas las notificaciones de un usuario como leídas
     *
     * @return int Número de notificaciones actualizadas
     */
    public function markAllAsReadForUser(string $userId) : int;

    /**
     * Marcar notificación como leída
     */
    public function markAsRead(string $notificationId) : bool;
}
