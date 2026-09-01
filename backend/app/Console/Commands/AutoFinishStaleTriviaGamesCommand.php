<?php

namespace App\Console\Commands;

use App\Domains\Games\Trivia\Actions\FinishGameAction;
use App\Domains\Games\Trivia\Models\TriviaGame;
use App\Domains\Games\Trivia\Services\TriviaPauseService;
use App\Services\Commands\CommandService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cierra partidas de trivia que quedaron sin actividad (todas las preguntas
 * respondidas o 5 min sin avanzar — ver FinishGameAction::shouldAutoFinish()),
 * y reintenta reanudar partidas pausadas automáticamente por desconexión
 * masiva si el % de inactivos ya bajó del umbral configurado
 * (ver TriviaPauseService::maybeAutoResume()).
 *
 * Sin este comando, autoFinishIfNeeded() existe pero nunca se invoca: una
 * partida abandonada (jugadores desconectados, AdvanceTriviaQuestion perdido)
 * queda en estado 'playing' para siempre.
 */
class AutoFinishStaleTriviaGamesCommand extends Command
{
    protected $signature   = 'trivia:auto-finish-stale-games';
    protected $description = 'Finaliza automáticamente partidas de trivia inactivas (sin actividad por 5 min) y reintenta reanudar pausas automáticas';

    public function handle(FinishGameAction $finishGameAction, TriviaPauseService $pauseService): void
    {
        $status   = 'SUCCESS';
        $finished = 0;
        $resumed  = 0;
        $errors   = 0;

        try {
            $games = TriviaGame::active()->get(['id']);

            foreach ($games as $game) {
                $gameId = (string) $game->id;

                try {
                    if ($finishGameAction->autoFinishIfNeeded($gameId) !== null) {
                        $finished++;
                        $this->info("  ✓ Juego {$gameId} finalizado automáticamente.");
                    }
                } catch (Throwable $e) {
                    $errors++;
                    Log::error("[Trivia:AutoFinish] Error al finalizar juego {$gameId}.", [
                        'game_id' => $gameId,
                        'error'   => $e->getMessage(),
                    ]);
                    $this->warn("  ✗ Error en juego {$gameId}: {$e->getMessage()}");
                }
            }

            // Partidas pausadas quedan fuera de TriviaGame::active() a propósito
            // (no deben auto-finalizarse mientras están en pausa) — se revisan
            // aparte solo para el intento de reanudación automática.
            $pausedGames = TriviaGame::where('status', 'paused')->get(['id', 'room_id']);

            foreach ($pausedGames as $game) {
                $gameId = (string) $game->id;

                try {
                    $pauseService->maybeAutoResume($gameId, (string) $game->room_id);
                    if (TriviaGame::where('id', $gameId)->value('status') === 'playing') {
                        $resumed++;
                        $this->info("  ▶ Juego {$gameId} reanudado automáticamente.");
                    }
                } catch (Throwable $e) {
                    $errors++;
                    Log::error("[Trivia:AutoFinish] Error al intentar reanudar juego {$gameId}.", [
                        'game_id' => $gameId,
                        'error'   => $e->getMessage(),
                    ]);
                }
            }

            $output = "Juegos revisados: {$games->count()} — {$finished} finalizados, {$resumed} reanudados, {$errors} con errores.";
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

        // Envuelto en try/catch: si logCommandExecution() fallara por
        // cualquier motivo, el error visible al operador debe ser el
        // original de la tarea, no uno de logging que lo enmascare.
        try {
            app(CommandService::class)->logCommandExecution($this->signature, $output, $status);
        } catch (Throwable $logEx) {
            $this->error('No se pudo registrar la ejecución del comando: ' . $logEx->getMessage());
        }

        if ($status === 'SUCCESS') {
            $this->info($output);
        }
    }
}
