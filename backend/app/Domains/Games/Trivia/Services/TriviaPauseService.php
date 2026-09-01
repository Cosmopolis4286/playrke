<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Services;

use App\Domains\Games\Trivia\Contracts\GameStateRepositoryInterface;
use App\Domains\Games\Trivia\Models\TriviaGame;
use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Events\Games\Trivia\TriviaGamePaused;
use App\Events\Games\Trivia\TriviaGameResumed;
use App\Jobs\Games\Trivia\AdvanceTriviaQuestion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Pausa y reanuda una partida de Trivia.
 *
 * Dos disparadores de pausa (decisión de producto, ver
 * CHANGELOG_AUDIT_2026_08.md):
 *  - Manual: el host/admin pausa/reanuda a voluntad (TriviaGameController).
 *  - Automática: si >= settings.pause_disconnect_threshold_percent de los
 *    jugadores llevan 2 preguntas consecutivas sin ninguna respuesta (ni
 *    siquiera tardía), se asume desconexión masiva y se pausa sola — el
 *    chequeo corre después de cada avance de pregunta (GameEngineService)
 *    y en el cron AutoFinishStaleTriviaGamesCommand.
 *
 * Reanudación híbrida: el host puede reanudar manualmente en cualquier
 * momento; el mismo cron reintenta reanudar automáticamente si el % de
 * inactivos vuelve a bajar del umbral.
 *
 * server_timestamp_ms de la pregunta activa (Redis) se corre hacia
 * adelante por la duración exacta de la pausa al reanudar — esto evita
 * tocar el cálculo de responseTimeMs en TriviaAnswerController, que sigue
 * confiando ciegamente en ese timestamp como fuente de verdad.
 */
class TriviaPauseService
{
    private const INACTIVITY_QUESTIONS_WINDOW = 2;

    public function __construct(
        private readonly GameStateRepositoryInterface $stateRepository,
    ) {}

    public function pause(string $gameId, string $reason): void
    {
        $state = $this->stateRepository->getState($gameId);
        if (! $state || $state->status !== 'active') {
            throw new RuntimeException('La partida no está activa.');
        }

        $pausedAtMs = microtime(true) * 1000;

        $pausedState = $state->withStatus('paused');
        $this->stateRepository->saveState($gameId, $pausedState);
        $this->stateRepository->updateField($gameId, 'metadata', array_merge(
            $pausedState->metadata,
            ['paused_at_ms' => $pausedAtMs, 'pause_reason' => $reason],
        ));

        TriviaGame::where('id', $gameId)->update(['status' => 'paused']);

        try {
            broadcast(new TriviaGamePaused($state->roomId, $gameId, $reason));
        } catch (\Throwable $e) {
            Log::warning('[TriviaPauseService] broadcast TriviaGamePaused falló: ' . $e->getMessage());
        }
    }

    public function resume(string $gameId): void
    {
        $state = $this->stateRepository->getState($gameId);
        if (! $state || $state->status !== 'paused') {
            throw new RuntimeException('La partida no está pausada.');
        }

        $pausedAtMs = (float) ($state->metadata['paused_at_ms'] ?? 0);
        $pauseDurationMs = $pausedAtMs > 0 ? max(0.0, (microtime(true) * 1000) - $pausedAtMs) : 0.0;

        $resumedState = $state->withStatus('active');
        $this->stateRepository->saveState($gameId, $resumedState);

        TriviaGame::where('id', $gameId)->update(['status' => 'playing']);

        // Correr server_timestamp_ms hacia adelante por la duración exacta
        // de la pausa: para TriviaAnswerController, es como si la pregunta
        // se hubiera enviado más tarde — el jugador no pierde el tiempo que
        // estuvo pausado.
        $questionData = $this->stateRepository->getCurrentQuestion($gameId);
        $remainingSeconds = 0;

        if ($questionData && $pauseDurationMs > 0) {
            $questionData['server_timestamp_ms'] = (float) ($questionData['server_timestamp_ms'] ?? 0) + $pauseDurationMs;
            $this->stateRepository->saveCurrentQuestion($gameId, $questionData);

            $timeLimitMs = (int) ($questionData['time_limit_seconds'] ?? 20) * 1000;
            $elapsedMs   = max(0.0, (microtime(true) * 1000) - $questionData['server_timestamp_ms']);
            $remainingSeconds = (int) max(0, ceil(($timeLimitMs - $elapsedMs) / 1000));

            // Re-despachar el avance automático con el tiempo restante real
            // — el job original despachado antes de pausar ya no sirve (su
            // guarda !$state->isActive() lo neutraliza al ejecutarse, pero
            // en el momento equivocado: sin este redespacho, la pregunta
            // quedaría sin ninguna red de seguridad tras reanudar).
            $questionId = (string) ($questionData['question_id'] ?? $questionData['id'] ?? '');
            if ($questionId !== '' && $remainingSeconds > 0) {
                AdvanceTriviaQuestion::dispatch($gameId, $questionId)
                    ->delay(now()->addSeconds($remainingSeconds + 2));
            }
        }

        try {
            broadcast(new TriviaGameResumed($state->roomId, $gameId, $remainingSeconds));
        } catch (\Throwable $e) {
            Log::warning('[TriviaPauseService] broadcast TriviaGameResumed falló: ' . $e->getMessage());
        }
    }

    /**
     * % de jugadores activos que no respondieron NINGUNA de las últimas
     * INACTIVITY_QUESTIONS_WINDOW preguntas (ni siquiera tarde) — ver
     * docblock de la clase. Devuelve 0 si hay menos preguntas enviadas que
     * la ventana (no hay suficiente historial para juzgar inactividad).
     */
    public function inactivePlayerPercent(string $gameId): float
    {
        $state = $this->stateRepository->getState($gameId);
        if (! $state || empty($state->playerIds)) {
            return 0.0;
        }

        $usedQuestionIds = $state->metadata['used_question_ids'] ?? [];
        $recentQuestionIds = array_slice($usedQuestionIds, -self::INACTIVITY_QUESTIONS_WINDOW);

        if (count($recentQuestionIds) < self::INACTIVITY_QUESTIONS_WINDOW) {
            return 0.0;
        }

        $respondedPlayerIds = DB::connection('pgsql_trivia')
            ->table('trivia_answers')
            ->where('game_id', $gameId)
            ->whereIn('question_id', $recentQuestionIds)
            ->distinct()
            ->pluck('player_id')
            ->all();

        $inactiveCount = count(array_diff($state->playerIds, $respondedPlayerIds));

        return ($inactiveCount / count($state->playerIds)) * 100;
    }

    /**
     * Umbral configurado por la sala (0 = detección automática desactivada).
     */
    public function disconnectThresholdPercent(string $roomId): float
    {
        $room = TriviaRoom::find($roomId);

        return (float) (($room?->settings ?? [])['pause_disconnect_threshold_percent'] ?? 0);
    }

    /**
     * Evalúa si la partida debe pausarse automáticamente ahora, y la pausa
     * si corresponde. Se llama tras cada avance de pregunta.
     */
    public function maybeAutoPause(string $gameId, string $roomId): void
    {
        $threshold = $this->disconnectThresholdPercent($roomId);
        if ($threshold <= 0) {
            return;
        }

        if ($this->inactivePlayerPercent($gameId) >= $threshold) {
            try {
                $this->pause($gameId, 'auto_disconnect');
                Log::info('[TriviaPauseService] Partida pausada automáticamente por desconexión masiva', [
                    'game_id' => $gameId,
                    'room_id' => $roomId,
                ]);
            } catch (RuntimeException) {
                // Ya estaba pausada u otro estado no activo — no hacer nada.
            }
        }
    }

    /**
     * Evalúa si una partida pausada AUTOMÁTICAMENTE puede reanudarse sola
     * (el % de inactivos volvió a bajar del umbral). No reanuda partidas
     * pausadas manualmente por el host — esas solo el propio host las
     * reanuda (ver TriviaGameController::resumeGame()).
     */
    public function maybeAutoResume(string $gameId, string $roomId): void
    {
        $state = $this->stateRepository->getState($gameId);
        if (! $state || $state->status !== 'paused') {
            return;
        }

        if (($state->metadata['pause_reason'] ?? null) !== 'auto_disconnect') {
            return;
        }

        $threshold = $this->disconnectThresholdPercent($roomId);
        if ($threshold <= 0 || $this->inactivePlayerPercent($gameId) < $threshold) {
            try {
                $this->resume($gameId);
            } catch (RuntimeException) {
                // Ya no estaba pausada — no hacer nada.
            }
        }
    }
}
