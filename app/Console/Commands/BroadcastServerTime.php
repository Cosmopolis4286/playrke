<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Events\ServerTimeUpdated;
use Illuminate\Support\Facades\Broadcast;

class BroadcastServerTime extends Command
{
    protected $signature = 'broadcast:servertime';
    protected $description = 'Broadcast the server time every second';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        while (true) {
            $time = now()->format('H:i:s');
            event(new ServerTimeUpdated($time));
            sleep(1);
        }
    }
}
