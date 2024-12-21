<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BingoRoom;
use App\Jobs\BroadcastRoomNumbersJob;

class BroadcastDrawNumbers extends Command
{
    protected $signature = 'bingo-draw:broadcast';
    protected $description = 'Manejador de los sorteos de bingo';

    public function handle()
    {
        $rooms = BingoRoom::where('status', 3)->get();

        foreach ($rooms as $room) {
            // Despacha todos los trabajos de forma asíncrona
            BroadcastRoomNumbersJob::dispatch($room)->onQueue('bingo-draw');
            $this->info("Job despachado para la sala: {$room->id}");
        }

        $this->info('Todos los trabajos han sido despachados.');
    }
}
