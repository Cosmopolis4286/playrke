<?php

namespace App\Jobs;

use App\Events\DrawNumberEvent;
use App\Models\BingoDraws;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BroadcastRoomNumbersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $room;

    public function __construct($room)
    {
        $this->room = $room;
    }

    public function handle()
    {
        $startTime = Carbon::parse($this->room->start_time);
        $currentTime = Carbon::now();

        if ($currentTime->lt($startTime)) {
            return;
        }

        $elapsedTime = $currentTime->diffInSeconds($startTime);

        $numbers = BingoDraws::where('room', $this->room->id)->where('status', 0)
            ->orderBy('time', 'asc')->get(['id','chip', 'time', 'status']);

        foreach ($numbers as $index => $number) {
            $expectedTime = 9 + ($index * 8);

            if ($elapsedTime >= $expectedTime) {
                broadcast(new DrawNumberEvent($number->chip));
                $number->update(['status' => 1]);
            } else {
                sleep($expectedTime - $elapsedTime);
                broadcast(new DrawNumberEvent($number->chip));
                $number->update(['status' => 1]);
            }
        }
    }
}
