<?php

namespace App\Console\Commands;

use App\Models\CommandLog;
use App\Services\AutoGenerateBingoRooms;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateBingoRoomsCommand extends Command
{
    protected $signature = 'generate:bingo-rooms';
    protected $description = 'Crea nuevas salas de bingo';

    public function handle()
    {
        $output = '';
        $status = 'SUCCESS';

        try {
            $service = new AutoGenerateBingoRooms();
            $response = $service->createBingoRooms();

            $output = $response['Info'] ?? 'Salas de bingo generadas exitosamente.';
            Log::info($output);
        } catch (\Exception $e) {
            $status = 'ERROR';
            $output = 'Error al ejecutar la tarea: ' . $e->getMessage();
            Log::error($output);
            report($e);
        }

        $this->logCommandExecution($output, $status);
        $this->infoOrError($output, $status === 'ERROR');
    }

    private function logCommandExecution(string $output, string $status): void
    {
        // Guardar en la base de datos
        CommandLog::create([
            'command' => $this->signature,
            'output' => $output,
            'status' => $status,
            'executed_at' => now(),
        ]);
    }

    private function infoOrError(string $message, bool $isError): void
    {
        $isError ? $this->error($message) : $this->info($message);
    }
}
