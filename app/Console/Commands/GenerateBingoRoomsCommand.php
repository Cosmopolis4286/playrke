<?php

namespace App\Console\Commands;

use App\Jobs\GenerateBingoRoomsJob;
use Illuminate\Console\Command;

class GenerateBingoRoomsCommand extends Command
{
    protected $signature = 'generate:bingo-rooms';
    protected $description = 'Crea nuevas salas de bingo';

    public function handle()
    {
        // Disparar la Job directamente
        GenerateBingoRoomsJob::dispatch();

        $this->info('La tarea de generación de salas de bingo se ha ejecutado.');
    }
}
