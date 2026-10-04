<?php

namespace App\Domains\Games\Bingo\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast cuando se vende un cartón de bingo (compra de seller/commerce o player).
 *
 * Canales:
 * - admins.activity                 (privado, admin/superadmin)
 * - creators.activity.{userId}      (privado, solo el seller/commerce propietario de la sala)
 * - subsidiary.activity.{userId}    (privado, solo la Subsidiary dueña del Seller que vendió)
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
        /** user_id de la Subsidiary dueña del Seller que vendió; null si el seller reporta directo al commerce, o no hay seller involucrado */
        public readonly ?string $subsidiaryUserId = null,
        /**
         * user_id del VENDEDOR que hizo la venta. Su dashboard escucha
         * creators.activity.{su user_id}; antes el evento solo iba al
         * comercio y a la agencia, y el indicador "en vivo" del vendedor
         * nunca recibía sus propias ventas.
         */
        public readonly ?string $sellerUserId = null,
    ) {}

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('admins.activity')];

        $creatorChannels = array_unique(array_filter([$this->creatorUserId, $this->sellerUserId]));
        foreach ($creatorChannels as $userId) {
            $channels[] = new PrivateChannel("creators.activity.{$userId}");
        }

        if ($this->subsidiaryUserId) {
            $channels[] = new PrivateChannel("subsidiary.activity.{$this->subsidiaryUserId}");
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
