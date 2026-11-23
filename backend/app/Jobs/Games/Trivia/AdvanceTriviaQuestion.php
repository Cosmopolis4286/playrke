<?php

declare(strict_types=1);

namespace App\Jobs\Games\Trivia;

use App\Domains\Games\Trivia\Contracts\GameEngineInterface;
use App\Domains\Games\Trivia\Contracts\GameStateRepositoryInterface;
use App\Domains\Games\Trivia\Services\TriviaPowerUpService;
use App\Domains\Games\Trivia\Services\TriviaQuestionAdvanceGuard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Job del ciclo de vida de una pregunta. Dos fases:
 *
 *  - PHASE_CLOSE: se despacha al enviar la pregunta, con delay = tiempo límite
 *    + margen. Si la pregunta sigue abierta, la cierra (revelación). Si algún
 *    jugador que aún no respondió activó extra_time, espera a que venza su
 *    plazo extendido en vez de cerrarle la pregunta en la cara.
 *  - PHASE_ADVANCE: lo despacha closeQuestion() con delay = reveal_seconds;
 *    envía la siguiente pregunta (o termina la partida).
 *
 * En ambas fases comprueba que el juego siga activo y que la pregunta activa
 * sea la misma del despacho: si ya cambió, otro camino ya avanzó y no hace nada.
 */
class AdvanceTriviaQuestion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const PHASE_CLOSE = 'close';
    public const PHASE_ADVANCE = 'advance';

    public int $timeout = 10;

    // Reintentos solo para re-encolarse mientras corre un extra_time ajeno
    // (release()); no hay reintento por fallo (los errores se registran).
    public int $tries = 5;

    public function __construct(
        public readonly string $gameId,
        public readonly string $questionId,
        public readonly string $phase = self::PHASE_CLOSE,
    ) {
    }

    public function handle(
        GameStateRepositoryInterface $stateRepository,
        GameEngineInterface $gameEngine,
        TriviaPowerUpService $powerUps,
    ) : void {
        $state = $stateRepository->getState($this->gameId);

        // En pausa no se cierra ni avanza: TriviaPauseService::resume() vuelve
        // a programar la fase que corresponda.
        if (! $state || ! $state->isActive()) {
            return;
        }

        $current = $stateRepository->getCurrentQuestion($this->gameId);

        if (! TriviaQuestionAdvanceGuard::matchesExpectedQuestion($this->questionId, $current)) {
            return;
        }

        try {
            if ($this->phase === self::PHASE_ADVANCE) {
                $gameEngine->sendNextQuestion($this->gameId, $this->questionId);

                return;
            }

            if (! empty($current['closed_at_ms'])) {
                return;
            }

            $waitMs = $this->pendingExtraTimeMs($current, $state->playerIds, $powerUps);

            if ($waitMs > 0) {
                $this->release((int) ceil($waitMs / 1000) + 1);

                return;
            }

            Log::info('[AdvanceTriviaQuestion] Tiempo vencido — cerrando pregunta', [
                'game_id' => $this->gameId,
                'question_id' => $this->questionId,
            ]);

            $gameEngine->closeQuestion($this->gameId, $this->questionId, 'timeout');
        } catch (Throwable $e) {
            Log::error('[AdvanceTriviaQuestion] Error en el ciclo de la pregunta', [
                'game_id' => $this->gameId,
                'question_id' => $this->questionId,
                'phase' => $this->phase,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Milisegundos que faltan para que venza el plazo extendido más tardío
     * entre los jugadores que activaron extra_time y aún no respondieron.
     */
    private function pendingExtraTimeMs(array $question, array $playerIds, TriviaPowerUpService $powerUps) : float
    {
        $startedAtMs = (float) ($question['server_timestamp_ms'] ?? 0);

        if ($startedAtMs <= 0 || empty($playerIds)) {
            return 0.0;
        }

        $answered = DB::connection('pgsql_trivia')
            ->table('trivia_answers')
            ->where('game_id', $this->gameId)
            ->where('question_id', $this->questionId)
            ->whereNull('deleted_at')
            ->pluck('player_id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $maxBonusMs = 0;
        foreach (array_diff(array_map('strval', $playerIds), $answered) as $playerId) {
            $maxBonusMs = max($maxBonusMs, $powerUps->extraTimeBonusMs($this->gameId, $playerId, $this->questionId));
        }

        if ($maxBonusMs === 0) {
            return 0.0;
        }

        $extendedDeadlineMs = $startedAtMs + ((int) ($question['time_limit_seconds'] ?? 0)) * 1000 + $maxBonusMs;

        return max(0.0, $extendedDeadlineMs - microtime(true) * 1000);
    }
}
