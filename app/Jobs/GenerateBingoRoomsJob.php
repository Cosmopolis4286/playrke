<?php

namespace App\Jobs;

use App\Services\AutoGenerateBingoRooms;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateBingoRoomsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $param; // Propiedad para almacenar el argumento opcional

    /**
     * Crea una nueva instancia del Job.
     *
     * @param mixed $param
     */
    public function __construct($param = null)
    {
        $this->param = $param;
    }

    /**
     * Maneja el procesamiento del Job.
     */
    public function handle(): void
    {
        try {
            // Ejemplo de uso del parámetro
            if ($this->param) {
                Log::info('Parámetro recibido: ' . $this->param);
            }

            // Lógica principal
            $service = new AutoGenerateBingoRooms();
            $service->createBingoRooms();

            Log::info('Salas de bingo generadas exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al generar salas de bingo: ' . $e->getMessage());
        }
    }
}
