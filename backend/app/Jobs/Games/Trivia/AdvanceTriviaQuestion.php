<?php

declare(strict_types=1);

namespace App\Jobs\Games\Trivia;

use App\Domains\Games\Trivia\Contracts\GameEngineInterface;
use App\Domains\Games\Trivia\Contracts\GameStateRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Job: Avanza a la siguiente pregunta si no todos los jugadores respondieron
 * antes de que expirase el timer de la pregunta actual.
 *
 * Se despacha con delay = time_limit_seconds + buffer (2 s).
 * Antes de actuar comprueba:
 *   1. El juego sigue activo.
 *   2. La pregunta activa en Redis es la misma que cuando se despachó el job
 *      (si ya cambió, otro jugador ya desencadenó el avance → no hacer nada).
 */
class AdvanceTriviaQuestion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 10;

    public function __construct(
        public readonly string $gameId,
        public readonly string $questionId,
    ) {}

    public function handle(
        GameStateRepositoryInterface $stateRepository,
        GameEngineInterface          $gameEngine,
    ): void {
        $state = $stateRepository->getState($this->gameId);

        // Juego ya no activo → no hacer nada
        if (! $state || ! $state->isActive()) {
            return;
        }

        // Pregunta activa en Redis
        $current = $stateRepository->getCurrentQuestion($this->gameId);
        $currentId = (string) ($current['question_id'] ?? $current['id'] ?? '');

        // Si la pregunta ya avanzó (por las respuestas de los jugadores) → no tocar
        if ($currentId !== $this->questionId) {
            return;
        }

        // Verificar cuántos jugadores respondieron para esta pregunta
        $answered = DB::connection('pgsql_trivia')
            ->table('trivia_answers')
            ->where('game_id', $this->gameId)
            ->where('question_id', $this->questionId)
            ->count();

        $total = count($state->playerIds);

        // Si ya respondieron todos, TriviaAnswerController ya habrá avanzado → salir
        if ($answered >= $total) {
            return;
        }

        Log::info('[AdvanceTriviaQuestion] Timeout — avanzando pregunta por expiración', [
            'game_id'      => $this->gameId,
            'question_id'  => $this->questionId,
            'answered'     => $answered,
            'total'        => $total,
        ]);

        // Avanzar: los jugadores que no respondieron quedan con 0 puntos para esta ronda
        try {
            $gameEngine->sendNextQuestion($this->gameId, $this->questionId);
        } catch (\Throwable $e) {
            Log::error('[AdvanceTriviaQuestion] Error al avanzar pregunta', [
                'game_id' => $this->gameId,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
