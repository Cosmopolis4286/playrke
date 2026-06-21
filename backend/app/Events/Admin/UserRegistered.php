<?php

namespace App\Events\Admin;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast cuando se registra un nuevo usuario (auto-registro o creado por admin).
 *
 * Canal: admins.activity  (privado para admin/superadmin)
 * Evento: .user.registered
 */
class UserRegistered implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public readonly string $userId,
        public readonly string $username,
        public readonly ?string $email,
        public readonly string $roleName,
        public readonly string $createdAt,
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('admins.activity');
    }

    public function broadcastAs(): string
    {
        return 'user.registered';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id'    => $this->userId,
            'username'   => $this->username,
            'email'      => $this->email,
            'role_name'  => $this->roleName,
            'created_at' => $this->createdAt,
        ];
    }
}
