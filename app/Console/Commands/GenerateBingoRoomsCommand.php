<?php

namespace App\Console\Commands;

use App\Jobs\GenerateBingoRoomsJob;
use App\Models\CommandLog;
use Illuminate\Console\Command;

class GenerateBingoRoomsCommand extends Command
{
    protected $signature = 'generate:bingo-rooms';
    protected $description = 'Crea nuevas salas de bingo';

    public function handle()
    {
        $output = '';
        $status = 'SUCCESS'; // Valor predeterminado

        try {
            // Disparar el Job
            GenerateBingoRoomsJob::dispatch();

            // Si no hay errores, se considera una ejecución exitosa
            $output = 'La tarea de generación de salas de bingo se ha ejecutado exitosamente';
        } catch (\Exception $e) {
            // Si ocurre un error, capturar el mensaje de la excepción
            $output = 'Error al ejecutar la tarea: ' . $e->getMessage();
            $status = 'ERROR'; // Si ocurre un error, cambiar el estado
        }

        // Guardar el registro de ejecucion del comando 
        CommandLog::created([
            'command' => 'generate:bingo-rooms',
            'output' => $output,
            'status' => $status,
            'executed_at' => now()
        ]);

        // Imprimir el resultado en consola
        $this->info($output);
    }
}
