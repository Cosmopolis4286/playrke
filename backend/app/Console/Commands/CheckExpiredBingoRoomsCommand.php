<?php

namespace App\Console\Commands;

use App\Services\Commands\CommandService;
use App\Services\Games\Bingo\Room\Utils\CheckExpiredBingoRoomsService;
use Illuminate\Console\Command;
use Throwable;

class CheckExpiredBingoRoomsCommand extends Command
{
    protected $signature = 'check:expired-bingo-rooms';

    protected $description = 'Marca salas de bingo expiradas como INACTIVE (sin jugadores) o IN_PROGRESS (con jugadores)';

    public function handle(): void
    {
        $status = 'SUCCESS';

        try {
            app(CheckExpiredBingoRoomsService::class)->handle();
            $output = 'Verificación de salas de bingo expiradas ejecutada exitosamente.';
        } catch (Throwable $e) {
            $status = 'ERROR';
            $output = sprintf(
                'Error al ejecutar la tarea: %s en archivo %s línea %d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );
            $this->error($output);
        }

        app(CommandService::class)->logCommandExecution($this->signature, $output, $status);

        if ($status === 'SUCCESS') {
            $this->info($output);
        }
    }
}
