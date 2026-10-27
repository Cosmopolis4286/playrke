<?php

namespace App\Repositories;

use App\Contracts\NotificationRepositoryInterface;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class NotificationRepository implements NotificationRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function countUnreadByUser(string $userId) : int
    {
        return Notification::where('user_id', $userId)
            ->unread()
            ->count();
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data) : Notification
    {
        return Notification::create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $notificationId) : bool
    {
        $notification = Notification::find($notificationId);

        if (! $notification) {
            return false;
        }

        return $notification->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAllForUser(string $userId) : int
    {
        return Notification::where('user_id', $userId)->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteOldNotifications(int $days = 30) : int
    {
        $cutoffDate = Carbon::now()->subDays($days);

        return Notification::where('created_at', '<', $cutoffDate)
            ->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function findById(string $notificationId) : ?Notification
    {
        return Notification::find($notificationId);
    }

    /**
     * {@inheritdoc}
     */
    public function getByUser(string $userId, array $filters = [], int $limit = 20) : Collection
    {
        $query = Notification::where('user_id', $userId);

        // Aplicar filtros
        if (isset($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (isset($filters['is_read'])) {
            $query->where('is_read', $filters['is_read']);
        }

        return $query->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getUnreadByUser(string $userId, int $limit = 20) : Collection
    {
        return Notification::where('user_id', $userId)
            ->unread()
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function markAllAsReadForUser(string $userId) : int
    {
        return Notification::where('user_id', $userId)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function markAsRead(string $notificationId) : bool
    {
        $notification = Notification::find($notificationId);

        if (! $notification) {
            return false;
        }

        return $notification->markAsRead();
    }
}
