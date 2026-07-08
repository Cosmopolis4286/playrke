<?php

namespace App\Domains\Games\Bingo\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class BingoPlayersProgress implements ShouldBroadcastNow
{
    use SerializesModels;

    public int $roomId;

    /** @var array<int, array{user_id: int, user_name: string, missing: int}> */
    public array $players;

    public function __construct(int $roomId, array $players)
    {
        $this->roomId  = $roomId;
        $this->players = $players;
    }

    public function broadcastOn()
    {
        return new Channel("bingo.room.{$this->roomId}");
    }

    public function broadcastAs()
    {
        return 'PlayersProgress';
    }

    public function broadcastWith()
    {
        return [
            'room_id' => $this->roomId,
            'players' => $this->players,
        ];
    }
}
