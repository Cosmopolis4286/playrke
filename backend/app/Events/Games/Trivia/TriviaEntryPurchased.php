<?php

namespace App\Events\Games\Trivia;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast cuando un jugador paga la entrada a una sala de Trivia.
 * Cierra la brecha de visibilidad que existía frente a Bingo (BingoCardSold):
 * antes, el cobro de entrada solo notificaba al canal privado del propio
 * jugador (WalletUpdated), sin ninguna señal para admins en tiempo real.
 *
 * Canal: admins.activity (privado, admin/superadmin)
 * Evento: .trivia.entry
 */
class TriviaEntryPurchased implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public readonly string $roomId,
        public readonly string $roomName,
        public readonly float  $amount,
        public readonly string $currencySymbol,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('admins.activity');
    }

    public function broadcastAs(): string
    {
        return 'trivia.entry';
    }

    public function broadcastWith(): array
    {
        return [
            'room_id'         => $this->roomId,
            'room_name'       => $this->roomName,
            'amount'          => $this->amount,
            'currency_symbol' => $this->currencySymbol,
        ];
    }
}
