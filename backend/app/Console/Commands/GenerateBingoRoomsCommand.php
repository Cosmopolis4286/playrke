<?php

namespace App\Console\Commands;

use App\Domains\Games\Bingo\Services\Room\Automatic\AutoGenerateBingoRoomsService;
use App\Services\Commands\CommandService;
use Illuminate\Console\Command;
use Throwable;

class GenerateBingoRoomsCommand extends Command
{
    protected $description = 'Crea nuevas salas de bingo a partir de plantillas automáticas activas';
    protected $signature = 'generate:bingo-rooms';

    public function handle() : void
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
                $e->getLine(),
            );
            $this->error($output);
        }

        // Envuelto en try/catch: si logCommandExecution() fallara por
        // cualquier motivo, el error visible al operador debe ser el
        // original de la tarea, no uno de logging que lo enmascare.
        try {
            app(CommandService::class)->logCommandExecution($this->signature, $output, $status);
        } catch (Throwable $logEx) {
            $this->error('No se pudo registrar la ejecución del comando: ' . $logEx->getMessage());
        }

        if ($status === 'SUCCESS') {
            $this->info($output);
        }
    }
}
