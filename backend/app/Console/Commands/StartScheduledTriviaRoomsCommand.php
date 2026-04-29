<?php

namespace App\Console\Commands;

use App\Domains\Games\Trivia\Contracts\GameEngineInterface;
use App\Domains\Games\Trivia\Contracts\RoomServiceInterface;
use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Services\Commands\CommandService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class StartScheduledTriviaRoomsCommand extends Command
{
    protected $signature = 'trivia:start-scheduled-rooms';

    protected $description = 'Inicia automáticamente las salas de trivia cuya fecha programada (scheduled_at) ha llegado';

    public function handle(): void
    {
        $status = 'SUCCESS';
        $startedCount = 0;
        $skippedCount = 0;

        try {
            $rooms = TriviaRoom::where('status', 'waiting')
                ->whereNotNull('scheduled_at')
                ->where('scheduled_at', '<=', now())
                ->get();

            $roomService = app(RoomServiceInterface::class);
            $gameEngine = app(GameEngineInterface::class);

            foreach ($rooms as $room) {
                try {
                    $roomDto = $roomService->getRoom((string) $room->id);
                    $playerIds = $roomDto->playerIds;

                    if (count($playerIds) < 1) {
                        $skippedCount++;
                        Log::info("Trivia: Sala {$room->code} programada pero sin jugadores, se omite.", [
                            'room_id' => $room->id,
                            'scheduled_at' => $room->scheduled_at,
                        ]);
                        continue;
                    }

                    $gameEngine->startGame((string) $room->id, $playerIds);
                    $startedCount++;

                    $this->info("Sala {$room->code} iniciada automáticamente ({$room->scheduled_at})");
                } catch (Throwable $e) {
                    Log::error("Trivia: Error al iniciar sala programada {$room->code}", [
                        'room_id' => $room->id,
                        'error' => $e->getMessage(),
                    ]);
                    $this->warn("Error al iniciar sala {$room->code}: {$e->getMessage()}");
                }
            }

            $output = "Salas de trivia programadas: {$startedCount} iniciadas, {$skippedCount} omitidas (sin jugadores), " . ($rooms->count() - $startedCount - $skippedCount) . " con errores.";
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
