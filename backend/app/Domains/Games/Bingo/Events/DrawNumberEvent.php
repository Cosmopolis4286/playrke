<?php

namespace App\Domains\Games\Bingo\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class DrawNumberEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $number;

    public function __construct($number)
    {
        $this->number = $number;
    }

    public function broadcastOn()
    {
        return new Channel('draw-room');
    }

    public function broadcastAs()
    {
        return 'number.drawn';
    }
}
