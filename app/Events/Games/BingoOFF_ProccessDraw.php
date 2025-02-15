<?php

namespace App\Events\Games;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BingoOFF_ProccessDraw implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public $encrypted;
    public $chip;
    public $infoGame;

    public function __construct($encrypted, $chip, $infoGame)
    {
        $this->encrypted = $encrypted;
        $this->chip = $chip;
        $this->infoGame = $infoGame;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel("bingo.{$this->encrypted}"),
        ];
    }
}
