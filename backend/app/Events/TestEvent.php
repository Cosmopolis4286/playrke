<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class TestEvent implements ShouldBroadcast
{
    use SerializesModels;

    public $message;

    public function __construct($message = 'Hola desde Reverb!')
    {
        $this->message = $message;
    }

    // Canal público para pruebas
    public function broadcastOn()
    {
        return new Channel('test-channel');
    }

    public function broadcastAs()
    {
        return 'TestEvent';
    }
}