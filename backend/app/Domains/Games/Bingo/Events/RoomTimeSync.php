<?php

namespace App\Domains\Games\Bingo\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento para sincronizar el tiempo del servidor con los clientes.
 * Se emite periódicamente para mantener el countdown sincronizado.
 */
class RoomTimeSync implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $roomId;
    public int $serverTimestamp;
    public int $unixDate;
    public int $remainingSeconds;

    /**
     * Create a new event instance.
     *
     * @param int $roomId
     * @param int $unixDate Timestamp de inicio del juego
     */
    public function __construct(int $roomId, int $unixDate)
    {
        $this->roomId = $roomId;
        $this->unixDate = $unixDate;
        $this->serverTimestamp = now()->timestamp;
        $this->remainingSeconds = max(0, $unixDate - $this->serverTimestamp);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel("bingo.room.{$this->roomId}"),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'RoomTimeSync';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'room_id' => $this->roomId,
            'server_timestamp' => $this->serverTimestamp,
            'unix_date' => $this->unixDate,
            'remaining_seconds' => $this->remainingSeconds,
        ];
    }
}
