<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Services\Game\BingoRoomHealthCheckService;
use Illuminate\Console\Command;

final class BingoRoomHealthCheckCommand extends Command
{
    protected $description = 'Verifica si una sala de bingo está lista para iniciar el sorteo';
    protected $signature = 'bingo:health-check {room : ID de la sala a verificar}';

    public function handle(BingoRoomHealthCheckService $healthCheck) : int
    {
        $roomId = (string) $this->argument('room');
        $room = BingoRoom::with(['rounds', 'config', 'status'])->find($roomId);

        if (! $room) {
            $this->error("Sala #{$roomId} no encontrada.");

            return self::FAILURE;
        }

        $this->line("Verificando sala <comment>#{$room->id}</comment> — Estado: <comment>{$room->status?->code}</comment>");

        $errors = $healthCheck->collectErrors($room);

        if (empty($errors)) {
            $this->info('La sala está lista para sortear. Todas las verificaciones pasaron.');

            return self::SUCCESS;
        }

        $this->error('La sala NO está lista para sortear. Se encontraron los siguientes problemas:');
        foreach ($errors as $i => $error) {
            $this->line('  [' . ($i + 1) . '] ' . $error);
        }

        return self::FAILURE;
    }
}
