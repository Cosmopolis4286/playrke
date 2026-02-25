<?php

namespace App\Jobs\Games\Bingo;

use App\Services\Games\Bingo\AutoGenerateBingoRoomsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class GenerateBingoRoomsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Parámetro opcional para personalizar la generación de salas.
     *
     * @var mixed|null
     */
    protected mixed $param;

    /**
     * Crear una nueva instancia del Job.
     *
     * @param mixed|null $param
     */
    public function __construct(mixed $param = null)
    {
        $this->param = $param;
    }

    /**
     * Ejecuta el Job.
     *
     * @param AutoGenerateBingoRoomsService $service
     * @return void
     */
    public function handle(AutoGenerateBingoRoomsService $service): void
    {
        try {
            // Lógica de generación encapsulada en el service
            $service->createBingoRooms($this->param);

            // Solo log de confirmación
            Log::info('Salas de bingo generadas exitosamente.', [
                'param' => $this->param
            ]);
        } catch (\Throwable $e) {
            // Log detallado del error sin mezclar lógica de negocio
            Log::error('Error al generar salas de bingo: ' . $e->getMessage(), [
                'param' => $this->param,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
