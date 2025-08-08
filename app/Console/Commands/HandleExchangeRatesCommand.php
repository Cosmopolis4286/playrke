<?php

namespace App\Console\Commands;

use App\Services\Users\Customer\HandleExchangeRates;
use App\Services\Helpers\HandleCommands;
use Illuminate\Console\Command;
use Throwable;

class HandleExchangeRatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'handle:exchange-rates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manejador de las tasas de cambio USD';

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
            app(HandleExchangeRates::class)->updateExchangeRates();
            $output = 'La tarea de control/manejo de las tasas de cambio de USD se ha ejecutado exitosamente.';
        } catch (Throwable $e) {
            // Manejo de errores con detalles específicos
            $status = 'ERROR';
            $output = sprintf(
                'Error al ejecutar la tarea: %s en archivo %s línea %d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );

            $this->error($output); // Imprime el error en la consola
        }

        // Guardar el registro de ejecución
        app(HandleCommands::class)->logCommandExecution($this->signature, $output, $status);

        // Imprimir resultado final
        if ($status === 'SUCCESS') {
            $this->info($output);
        }
    }
}
