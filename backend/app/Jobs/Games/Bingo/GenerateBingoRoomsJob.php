<?php

namespace App\Jobs\Games\Bingo;

use App\Services\Games\Bingo\Room\Automatic\AutoGenerateBingoRoomsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class GenerateBingoRoomsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected mixed $param;

    public function __construct(mixed $param = null)
    {
        $this->param = $param;
    }

    public function handle(AutoGenerateBingoRoomsService $service): void
    {
        try {
            $service->createBingoRooms($this->param);
            Log::info('[GenerateBingoRoomsJob] Salas de bingo generadas exitosamente.', [
                'param' => $this->param,
            ]);
        } catch (\Throwable $e) {
            Log::error('[GenerateBingoRoomsJob] Error al generar salas: ' . $e->getMessage(), [
                'param' => $this->param,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
