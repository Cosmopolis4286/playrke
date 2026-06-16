<?php

namespace App\Events\Games\Bingo;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

/**
 * Evento emitido cuando cambia la cantidad de cartones que un jugador
 * tiene comprados en una sala (tras una compra exitosa).
 *
 * Canal: bingo.player.{userId} (privado, requiere autenticación)
 * Evento: PlayerCardsUpdated
 *
 * Permite que la vista /bingo/game-rooms actualice `players_room`,
 * `card_price` y `currency_symbol` en tiempo real, sin recargar la página.
 */
final class BingoPlayerRoomCardsUpdated implements ShouldBroadcast
{
    use SerializesModels;

    public function __construct(
        /** ID del usuario propietario del canal privado */
        public readonly string $userId,
        /** ID Snowflake de la sala (coincide con BingoGameRoom.id) */
        public readonly string $roomId,
        /** Total acumulado de cartones del usuario en esta sala */
        public readonly int $ctns,
        /** Precio del cartón en la moneda del usuario (null para commerce/seller) */
        public readonly ?float $cardPrice = null,
        /** Símbolo de la moneda del usuario (null para commerce/seller) */
        public readonly ?string $currencySymbol = null,
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel("bingo.player.{$this->userId}");
    }

    public function broadcastAs(): string
    {
        return 'PlayerCardsUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'room_id'         => $this->roomId,
            'ctns'            => $this->ctns,
            'card_price'      => $this->cardPrice,
            'currency_symbol' => $this->currencySymbol,
        ];
    }

    public function broadcastConnection()
    {
        return 'reverb';
    }
}
