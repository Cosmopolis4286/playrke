<?php

namespace App\Console\Commands;

use App\Domains\Games\Bingo\Models\Draw\BingoDraw;
use App\Domains\Games\Bingo\Models\Draw\BingoDrawCode;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser;
use App\Jobs\Games\Bingo\StartBingoDrawJob;
use App\Services\Commands\CommandService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Detecta salas de bingo cuya hora de inicio ya llegó y que cuentan con al
 * menos un jugador registrado, y dispara su sorteo de forma autónoma —
 * sin depender de que un cliente tenga la página del juego abierta.
 *
 * Antes de este comando, el sorteo solo se iniciaba vía un fetch() lanzado
 * por el navegador cuando el countdown llegaba a cero (ver Index.vue), lo que
 * dejaba la sala congelada indefinidamente si nadie la visitaba a tiempo.
 */
class StartPendingBingoDrawsCommand extends Command
{
    protected $signature   = 'bingo:start-pending-draws';
    protected $description = 'Inicia automáticamente el sorteo de salas de bingo listas (hora cumplida + jugadores) sin depender de clientes conectados';

    public function handle(): void
    {
        $status = 'SUCCESS';
        $started = 0;

        try {
            $now = now()->timestamp;

            $rooms = BingoRoom::query()
                ->whereHas('status', fn($q) => $q->whereIn('code', ['ACTIVE', 'IN_PROGRESS']))
                ->whereHas('config', fn($q) => $q->where('unix_date', '<=', $now))
                ->whereHas('players', fn($q) => $q->whereIn('role', [
                    BingoRoomUser::ROLE_PLAYER,
                    BingoRoomUser::ROLE_PLAYER_SELLER,
                ]))
                ->withCount(['players as registered_players_count' => fn($q) => $q->whereIn('role', [
                    BingoRoomUser::ROLE_PLAYER,
                    BingoRoomUser::ROLE_PLAYER_SELLER,
                ])])
                ->with('config:id,room_id,unix_date')
                ->get();

            foreach ($rooms as $room) {
                $drawnCount = BingoDraw::where('room_id', (string) $room->id)->count();
                $sequenceCount = BingoDrawCode::where('room_id', (string) $room->id)->count();

                // Sorteo ya completo (todas las fichas de la secuencia fueron cantadas) — nada que hacer.
                if ($sequenceCount > 0 && $drawnCount >= $sequenceCount) {
                    continue;
                }

                // Sin sorteo iniciado, o interrumpido a mitad (p.ej. el worker se reinició):
                // despachamos — BingoDrawService::startDraw() detecta el caso interrumpido
                // y retoma desde la última ficha persistida en vez de reiniciar.
                StartBingoDrawJob::dispatch((string) $room->id)->onQueue('bingo-draw');
                $started++;

                $this->info("Sorteo despachado para sala #{$room->id} (jugadores: {$room->registered_players_count})");
            }

            $output = "Revisión completada. Sorteos despachados: {$started}.";
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
