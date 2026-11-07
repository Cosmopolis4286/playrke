<?php

namespace App\\Domains\\Games\\Bingo\\Events;

use Illuminate\\Broadcasting\\Channel;
use Illuminate\\Contracts\\Broadcasting\\ShouldBroadcastNow;
use Illuminate\\Queue\\SerializesModels;

class BingoPlayersProgress implements ShouldBroadcastNow
{
    use SerializesModels;

    /** @var array<int, array{user_id: int, user_name: string, missing: int}> */
    public array $players;

    public int $roomId;

    public function __construct(int $roomId, array $players)
    {
        $this->roomId = $roomId;
        $this->players = $players;
    }

    public function broadcastAs()
    {
        return 'PlayersProgress';
    }

    public function broadcastOn()
    {
        return new Channel("bingo.room.{$this->roomId}");
    }

    public function broadcastWith()
    {
        // El canal es público. Mantener el ranking visual de proximidad al
        // bingo, pero sustituir IDs y nombres reales por alias de esta emisión.
        $players = array_values(array_map(
            static fn (array $player, int $index) => [
                'user_id' => $index + 1,
                'user_name' => 'Jugador ' . ($index + 1),
                'missing' => (int) ($player['missing'] ?? 0),
            ],
            $this->players,
            array_keys($this->players),
        ));

        return [
            'room_id' => $this->roomId,
            'players' => $players,
        ];
    }
}
