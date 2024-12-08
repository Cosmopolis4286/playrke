<?php

namespace App\Console\Commands;

use App\Jobs\GenerateBingoRoomsJob;
use App\Models\CommandLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

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
            Log::info($output); // Registrar en logs de Laravel
        } catch (\Exception $e) {
            // Capturar el mensaje de la excepción
            $output = 'Error al ejecutar la tarea: ' . $e->getMessage();
            $status = 'ERROR'; // Cambiar el estado si ocurre un error
            Log::error($output); // Registrar en logs de Laravel
        }

        // Guardar el registro de ejecución del comando en la base de datos
        CommandLog::create([
            'command' => $this->signature,
            'output' => $output,
            'status' => $status,
            'executed_at' => now()
        ]);

        // Guardar el registro también en el archivo de logs schedule.log
        $logFilePath = storage_path('logs/schedule.log');
        file_put_contents($logFilePath, '[' . now() . "] {$this->signature}: {$output}" . PHP_EOL, FILE_APPEND);

        // Imprimir el resultado en consola
        $this->info($output);
    }
}
