<?php

namespace App\Events\Games\Bingo;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast cuando se vende un cartón de bingo (compra de seller/commerce o player).
 *
 * Canales:
 * - admins.activity                (privado, admin/superadmin)
 * - creators.activity.{userId}     (privado, solo el seller/commerce propietario de la sala)
 *
 * Evento: .card.sold
 */
class BingoCardSold implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public readonly string $roomId,
        public readonly string $roomName,
        public readonly int $quantity,
        public readonly float $amountUsd,
        /** user_id del seller/commerce dueño de la sala; null en compra directa del player */
        public readonly ?string $creatorUserId = null,
    ) {}

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('admins.activity')];

        if ($this->creatorUserId) {
            $channels[] = new PrivateChannel("creators.activity.{$this->creatorUserId}");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'card.sold';
    }

    public function broadcastWith(): array
    {
        return [
            'room_id'    => $this->roomId,
            'room_name'  => $this->roomName,
            'quantity'   => $this->quantity,
            'amount_usd' => $this->amountUsd,
        ];
    }
}
