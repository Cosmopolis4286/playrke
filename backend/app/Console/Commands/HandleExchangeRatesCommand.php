<?php

namespace App\Console\Commands;

use App\Models\UserPreference;
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
        // Verificar si el cronjob está habilitado usando UserPreference
        $autoUpdateEnabled = UserPreference::getPreference(
            null, // null para preferencia global
            UserPreference::CATEGORY_EXCHANGE_RATE,
            'auto_update_enabled',
            false // valor por defecto
        );

        if (!$autoUpdateEnabled) {
            $this->info('El cronjob de actualización automática está desactivado.');
            return;
        }

        $status = 'SUCCESS';

        try {
            // Ejecutar la lógica principal de actualización de tasas
            $this->updateRates();
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
        $this->comm->logCommandExecution($this->signature, $output, $status);

        // Imprimir resultado final
        if ($status === 'SUCCESS') {
            $this->info($output);
        }
    }

    /**
     * Actualiza las tasas de cambio desde la API externa.
     *
     * @return void
     */
    private function updateRates(): void
    {
        // Aquí va la lógica para obtener y actualizar las tasas
        // Puedes llamar al servicio o factory que ya existe en el sistema
        
        // Ejemplo:
        // $exchangeRateService = app(ExchangeRateService::class);
        // $exchangeRateService->updateFromExternalAPI();
        
        $this->info('Actualizando tasas de cambio desde API externa...');
        // Implementar la lógica de actualización aquí
    }
}
