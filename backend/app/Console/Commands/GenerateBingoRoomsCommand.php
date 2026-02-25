<?php

namespace App\Console\Commands;

use App\Services\Games\Bingo\Room\Automatic\AutoGenerateBingoRoomsService;
use App\Services\Helpers\HandleCommands;
use Illuminate\Console\Command;
use Throwable;

class GenerateBingoRoomsCommand extends Command
{
    protected $signature = 'generate:bingo-rooms';

    protected $description = 'Crea nuevas salas de bingo a partir de plantillas automáticas activas';

    public function handle(): void
    {
        $status = 'SUCCESS';

        try {
            app(AutoGenerateBingoRoomsService::class)->createBingoRooms();
            $output = 'La tarea de generación de salas de bingo se ha ejecutado exitosamente.';
        } catch (Throwable $e) {
            $status = 'ERROR';
            $output = sprintf(
                'Error al ejecutar la tarea: %s en archivo %s línea %d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );
            $this->error($output);
        }

        app(HandleCommands::class)->logCommandExecution($this->signature, $output, $status);

        if ($status === 'SUCCESS') {
            $this->info($output);
        }
    }
}
