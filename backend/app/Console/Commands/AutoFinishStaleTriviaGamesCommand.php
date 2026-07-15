<?php

namespace App\Console\Commands;

use App\Domains\Games\Trivia\Actions\FinishGameAction;
use App\Domains\Games\Trivia\Models\TriviaGame;
use App\Services\Commands\CommandService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cierra partidas de trivia que quedaron sin actividad (todas las preguntas
 * respondidas o 5 min sin avanzar — ver FinishGameAction::shouldAutoFinish()).
 *
 * Sin este comando, autoFinishIfNeeded() existe pero nunca se invoca: una
 * partida abandonada (jugadores desconectados, AdvanceTriviaQuestion perdido)
 * queda en estado 'playing' para siempre.
 */
class AutoFinishStaleTriviaGamesCommand extends Command
{
    protected $signature   = 'trivia:auto-finish-stale-games';
    protected $description = 'Finaliza automáticamente partidas de trivia inactivas (sin actividad por 5 min)';

    public function handle(FinishGameAction $finishGameAction): void
    {
        $status   = 'SUCCESS';
        $finished = 0;
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

            $output = "Juegos revisados: {$games->count()} — {$finished} finalizados, {$errors} con errores.";
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
