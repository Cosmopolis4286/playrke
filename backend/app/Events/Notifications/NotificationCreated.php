<?php

namespace App\Events\Notifications;

use App\Models\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

/**
 * Emitido inmediatamente cuando se persiste una nueva notificación.
 * El frontend la recibe con todos sus datos (incluyendo el ID real de DB)
 * y la renderiza directamente sin necesidad de hacer un API call de recarga.
 */
class NotificationCreated implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public readonly Notification $notification,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("App.Models.User.{$this->notification->user_id}");
    }

    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    public function broadcastWith(): array
    {
        $n = $this->notification;

        return [
            'id'             => (string) $n->id,
            'type'           => $n->type,
            'title'          => $n->title,
            'message'        => $n->message,
            'data'           => $n->data ?? [],
            'is_read'        => false,
            'created_at'     => $n->created_at->toISOString(),
            'formatted_time' => $n->created_at->diffForHumans(),
            'type_color'     => $n->getTypeColor(),
            'type_icon'      => $n->getTypeIcon(),
        ];
    }
}
