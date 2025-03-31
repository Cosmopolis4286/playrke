<?php

namespace App\Console\Commands;

use App\Services\Game\Bingo\AutoGeneratePollaDraws;
use App\Services\RKE\HandleCommands;
use Illuminate\Console\Command;
use Throwable;

class GeneratePollaDrawsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:polla-draws';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generar nuevos sorteos de pollas';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $status = 'SUCCESS';

        try {
            // Ejecutar la lógica principal
            app(AutoGeneratePollaDraws::class)->createBingoRooms();
            $output = 'La tarea de generación de sorteos de pollas se ha ejecutado exitosamente.';
        } catch (Throwable $e) {
            // Manejar excepciones con detalles adicionales
            $status = 'ERROR';
            $output = sprintf(
                'Error al ejecutar la tarea: %s en archivo %s línea %d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );

            $this->error($output); // Imprimir mensaje de error en consola
        }

        // Registrar el resultado en la base de datos
        app(HandleCommands::class)->logCommandExecution($this->signature, $output, $status);

        // Imprimir mensaje de éxito si no hubo errores
        if ($status === 'SUCCESS') {
            $this->info($output);
        }
    }
}
