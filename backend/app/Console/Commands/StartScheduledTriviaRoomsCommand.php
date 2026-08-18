<?php

namespace App\Console\Commands;

use App\Domains\Games\Trivia\Actions\StartGameAction;
use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Services\Commands\CommandService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class StartScheduledTriviaRoomsCommand extends Command
{
    protected $signature = 'trivia:start-scheduled-rooms';

    protected $description = 'Inicia automáticamente las salas de trivia cuya scheduled_at ha llegado';

    public function handle(): void
    {
        $status       = 'SUCCESS';
        $startedCount = 0;
        $skippedCount = 0;
        $errorCount   = 0;

        try {
            // Buscar salas que deben iniciarse ahora.
            // now() usa UTC (zona base de la app). scheduled_at debe estar almacenado en UTC.
            $rooms = TriviaRoom::where('status', 'waiting')
                ->whereNotNull('scheduled_at')
                ->where('scheduled_at', '<=', now())
                ->get();

            if ($rooms->isEmpty()) {
                $output = 'No hay salas programadas pendientes de inicio.';
                $this->logExecutionSafely($output, $status);
                $this->info($output);
                return;
            }

            $startAction = app(StartGameAction::class);

            foreach ($rooms as $room) {
                $roomId = (string) $room->id;

                try {
                    // ── Obtener jugadores inscritos desde BD (fuente de verdad) ──
                    // No dependemos de Redis, que puede haber expirado.
                    $playerIds = DB::connection('pgsql_trivia')
                        ->table('trivia_participants')
                        ->where('room_id', $roomId)
                        ->whereNull('deleted_at')
                        ->whereIn('status', ['confirmed', 'registered'])
                        ->pluck('user_id')
                        ->map(fn($id) => (string) $id)
                        ->all();

                    if (empty($playerIds)) {
                        $skippedCount++;
                        Log::info("[Trivia:Scheduled] Sala {$room->code} sin jugadores inscritos — omitida.", [
                            'room_id'      => $roomId,
                            'scheduled_at' => $room->scheduled_at,
                        ]);
                        $this->warn("  ⚠ Sala {$room->code}: sin jugadores, omitida.");
                        continue;
                    }

                    // ── Iniciar via StartGameAction (incluye sendNextQuestion) ───
                    $gameState = $startAction->execute(
                        roomId:    $roomId,
                        hostId:    (string) $room->host_id,
                        playerIds: $playerIds,
                    );

                    $startedCount++;
                    Log::info("[Trivia:Scheduled] Sala {$room->code} iniciada automáticamente.", [
                        'room_id'      => $roomId,
                        'game_id'      => $gameState->gameId,
                        'players'      => count($playerIds),
                        'scheduled_at' => $room->scheduled_at,
                    ]);
                    $this->info("  ✓ Sala {$room->code} iniciada (game: {$gameState->gameId}, {$room->scheduled_at}).");

                } catch (Throwable $e) {
                    $errorCount++;
                    Log::error("[Trivia:Scheduled] Error al iniciar sala {$room->code}.", [
                        'room_id' => $roomId,
                        'error'   => $e->getMessage(),
                        'trace'   => $e->getTraceAsString(),
                    ]);
                    $this->warn("  ✗ Error en sala {$room->code}: {$e->getMessage()}");
                }
            }

            $total  = $rooms->count();
            $output = "Salas programadas: {$total} encontradas — {$startedCount} iniciadas, {$skippedCount} omitidas (sin jugadores), {$errorCount} con errores.";

        } catch (Throwable $e) {
            $status = 'ERROR';
            $output = "Error general: {$e->getMessage()} en {$e->getFile()}:{$e->getLine()}";
            $this->error($output);
            Log::error('[Trivia:Scheduled] ' . $output);
        }

        $this->logExecutionSafely($output, $status);

        if ($status === 'SUCCESS') {
            $this->info($output);
        }
    }

    /**
     * Envuelto en try/catch: si logCommandExecution() fallara por cualquier
     * motivo, el error visible al operador debe ser el original de la
     * tarea, no uno de logging que lo enmascare. Extraído a método propio
     * porque este comando registra el resultado desde 2 puntos distintos
     * (salida temprana sin salas + flujo normal).
     */
    private function logExecutionSafely(string $output, string $status): void
    {
        try {
            app(CommandService::class)->logCommandExecution($this->signature, $output, $status);
        } catch (Throwable $logEx) {
            $this->error('No se pudo registrar la ejecución del comando: ' . $logEx->getMessage());
        }
    }
}
