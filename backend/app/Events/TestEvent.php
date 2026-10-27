<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TestEvent implements ShouldBroadcast
{
    use SerializesModels;

    public $message;

    public function __construct($message = 'Hola desde Reverb!')
    {
        $this->message = $message;
        Log::info('TestEvent creado', ['message' => $message]);
    }

    public function broadcastAs()
    {
        return 'TestEvent';
    }

    // Canal público para pruebas
    public function broadcastOn()
    {
        Log::info('TestEvent broadcastOn llamado', ['channel' => 'test-channel']);

        return new Channel('test-channel');
    }

    public function broadcastWith()
    {
        return [
            'message' => $this->message,
            'timestamp' => now()->toISOString(),
        ];
    }
}
