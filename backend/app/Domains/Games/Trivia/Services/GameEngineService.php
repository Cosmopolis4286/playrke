<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Services;

use App\Domains\Games\Trivia\Contracts\GameEngineInterface;
use App\Domains\Games\Trivia\Contracts\GameStateRepositoryInterface;
use App\Domains\Games\Trivia\DTOs\AnswerResultDTO;
use App\Domains\Games\Trivia\DTOs\GameStateDTO;
use App\Domains\Games\Trivia\DTOs\QuestionDTO;
use App\Domains\Games\Trivia\Events\GameFinished;
use App\Domains\Games\Trivia\Events\GameStarted;
use App\Domains\Games\Trivia\Events\QuestionSent;
use App\Domains\Games\Trivia\Events\ScoreUpdated;
use App\Domains\Games\Trivia\Exceptions\Game\TriviaGameException;
use App\Domains\Games\Trivia\Models\TriviaGame;
use App\Domains\Games\Trivia\Models\TriviaQuestion;
use App\Domains\Games\Trivia\Models\TriviaRoom;
use App\Events\Games\Trivia\TriviaGameCancelled;
use App\Events\Games\Trivia\TriviaGameFinished;
use App\Events\Games\Trivia\TriviaGameStarted;
use App\Jobs\Games\Trivia\AdvanceTriviaQuestion;
use App\Models\GameParticipation;
use App\Models\User;
use App\Services\Games\GameCompletionService;
use App\Services\User\UserLevelService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Servicio: Motor de Juego de Trivia.
 *
 * Contiene toda la lógica de negocio del juego:
 * - Iniciar juego
 * - Enviar preguntas
 * - Validar respuestas
 * - Calcular puntajes
 * - Manejar estado del juego
 *
 * Este servicio es independiente de la infraestructura
 * y puede ser portado a otros frameworks o microservicios.
 */
class GameEngineService implements GameEngineInterface
{
    public function __construct(
        private readonly GameStateRepositoryInterface $stateRepository,
        private readonly ScoreService $scoreService,
        private readonly PrizeDistributionService $prizeService,
        private readonly TeamScoreService $teamScoreService,
        private readonly SkillRatingService $skillRatingService,
        private readonly GameCompletionService $gameCompletionService,
        private readonly UserLevelService $userLevelService,
        private readonly TriviaPauseService $pauseService,
    ) {
    }

    /**
     * Cancelar juego.
     */
    public function cancelGame(string $gameId, string $reason) : void
    {
        $state = $this->stateRepository->getState($gameId);

        if (! $state) {
            return;
        }

        $cancelledState = $state->withStatus('cancelled');
        $this->stateRepository->saveState($gameId, $cancelledState);

        // Actualizar DB
        TriviaGame::where('id', $gameId)->update([
            'status' => 'cancelled',
            'ended_at' => now(),
            'metadata' => ['cancellation_reason' => $reason],
        ]);

        // Notificar a jugadores
        try {
            broadcast(new TriviaGameCancelled($state->roomId, $gameId, $reason));
        } catch (Throwable $e) {
            Log::warning('[GameEngine] broadcast TriviaGameCancelled falló: ' . $e->getMessage());
        }
    }

    /**
     * Finalizar juego.
     * Sincroniza trivia_participants, actualiza skill ratings, distribuye premios
     * y emite el evento broadcast a todos los jugadores.
     */
    public function finishGame(string $gameId) : GameStateDTO
    {
        $state = $this->stateRepository->getState($gameId);

        if (! $state) {
            throw TriviaGameException::gameNotFound();
        }

        if ($state->isFinished()) {
            return $state;
        }

        $finalState = $state->withStatus('finished');
        $this->stateRepository->saveState($gameId, $finalState);

        $game = TriviaGame::findOrFail($gameId);
        $room = TriviaRoom::findOrFail($state->roomId);

        // ── 1. Construir leaderboard final desde PostgreSQL (trivia_scores + trivia_answers) ──
        $finalRanks = $this->buildFinalLeaderboard($gameId, $state->roomId, $state->totalQuestions);

        // ── 2. Persistir scores finales en BD ───────────────────────────────
        $game->finish($finalRanks);

        foreach ($finalRanks as $rank) {
            \App\Domains\Games\Trivia\Models\TriviaScore::updateOrCreate(
                ['game_id' => $gameId, 'player_id' => $rank['playerId']],
                [
                    'final_score' => $rank['totalScore'],
                    'correct_answers' => $rank['correctAnswers'],
                    'wrong_answers' => $rank['wrongAnswers'],
                    'average_response_time_ms' => (int) round($rank['avgTimeMs']),
                    'position' => $rank['position'],
                    'max_streak' => $rank['maxStreak'],
                    'total_questions' => $rank['totalQuestions'],
                    'total_response_time_ms' => $rank['totalResponseTimeMs'],
                    'is_winner' => $rank['position'] === 1,
                ],
            );
        }

        // ── 3. Sincronizar trivia_participants ──────────────────────────────
        // Actualiza posición final, puntuación y marca como 'played' a todos.
        try {
            foreach ($finalRanks as $rank) {
                DB::connection('pgsql_trivia')
                    ->table('trivia_participants')
                    ->where('room_id', $state->roomId)
                    ->where('user_id', $rank['playerId'])
                    ->update([
                        'final_rank' => $rank['position'],
                        'total_score' => $rank['totalScore'],
                        'correct_answers' => $rank['correctAnswers'],
                        'wrong_answers' => $rank['wrongAnswers'],
                        'total_time' => round($rank['totalResponseTimeMs'] / 1000, 3),
                        'status' => 'playing', // se actualizará a 'winner' en prizeService
                        'updated_at' => now(),
                    ]);
            }
        } catch (Throwable $e) {
            Log::warning('[GameEngine] No se pudo sincronizar trivia_participants: ' . $e->getMessage());
        }

        // ── 4. Scores de equipos (modos teams / duel) ───────────────────────
        $teamLeaderboard = [];
        if ($room->usesTeams()) {
            try {
                $teamLeaderboard = $this->teamScoreService->calculateTeamScores($state->roomId);
            } catch (Throwable $e) {
                Log::warning('[GameEngine] Error calculando team scores: ' . $e->getMessage());
            }
        }

        // ── 5. Actualizar sala ──────────────────────────────────────────────
        TriviaRoom::where('id', $state->roomId)->update([
            'status' => 'closed',
            'ended_at' => now(),
        ]);

        // ── 6. Distribuir premios ───────────────────────────────────────────
        $prizeResults = [];

        try {
            $prizeResults = $this->prizeService->distribute($state->roomId, $finalRanks);
        } catch (Throwable $e) {
            Log::error('[GameEngine] Error distribuyendo premios', [
                'game_id' => $gameId,
                'room_id' => $state->roomId,
                'error' => $e->getMessage(),
            ]);
        }

        // ── 7. Actualizar skill ratings ─────────────────────────────────────
        try {
            $this->skillRatingService->updateAfterGame($finalRanks);
        } catch (Throwable $e) {
            Log::warning('[GameEngine] Error actualizando skill ratings: ' . $e->getMessage());
        }

        // ── 7.5. XP y logros por partida jugada/ganada ──────────────────────
        // Falla contenida: no debe afectar los premios ya distribuidos en el paso 6.
        try {
            $this->reportGameCompletion($finalRanks, $state->roomId);
        } catch (Throwable $e) {
            Log::warning('[GameEngine] Error al otorgar XP/logros: ' . $e->getMessage());
        }

        // ── 8. Construir leaderboard para broadcast ─────────────────────────
        $broadcastLeaderboard = array_map(fn ($rank) => [
            'player_id' => $rank['playerId'],
            'position' => $rank['position'],
            'score' => $rank['totalScore'],
            'name' => '',   // el frontend resuelve nombre desde la lista de jugadores
        ], array_slice($finalRanks, 0, 10));

        $winners = array_slice(array_column($finalRanks, 'playerId'), 0, 3);
        $statistics = [
            'total_players' => count($state->playerIds),
            'total_questions' => $state->totalQuestions,
            'duration_seconds' => time() - ($state->startTime ?? time()),
            'average_score' => array_sum(array_map(fn ($r) => $r['totalScore'], $finalRanks))
                                  / max(1, count($finalRanks)),
            'prizes_paid' => count($prizeResults),
            'teams' => $teamLeaderboard,
        ];

        // ── 9. Eventos ──────────────────────────────────────────────────────
        $finalLeaderboard = new \App\Domains\Games\Trivia\DTOs\LeaderboardDTO(
            gameId: $gameId,
            scores: array_map(fn ($rank) => \App\Domains\Games\Trivia\DTOs\ScoreDTO::fromArray([
                'player_id' => $rank['playerId'],
                'game_id' => $gameId,
                'total_score' => $rank['totalScore'],
                'correct_answers' => $rank['correctAnswers'],
                'wrong_answers' => $rank['wrongAnswers'],
                'average_response_time_ms' => $rank['avgTimeMs'],
                'position' => $rank['position'],
                'max_streak' => $rank['maxStreak'],
            ]), $finalRanks),
            totalPlayers: count($finalRanks),
            updatedAt: time(),
            isFinal: true,
        );

        event(new GameFinished(
            gameState: $finalState,
            finalLeaderboard: $finalLeaderboard,
            winners: $winners,
            statistics: $statistics,
        ));

        try {
            broadcast(new TriviaGameFinished(
                roomId: $state->roomId,
                gameId: $gameId,
                leaderboard: $broadcastLeaderboard,
            ));
        } catch (Throwable $e) {
            Log::warning('[GameEngine] broadcast TriviaGameFinished falló: ' . $e->getMessage());
        }

        return $finalState;
    }

    /**
     * Obtener pregunta actual.
     */
    public function getCurrentQuestion(string $gameId) : ?QuestionDTO
    {
        $questionData = $this->stateRepository->getCurrentQuestion($gameId);

        if (! $questionData) {
            return null;
        }

        return QuestionDTO::fromArray($questionData);
    }

    /**
     * Obtener estado actual.
     */
    public function getGameState(string $gameId) : GameStateDTO
    {
        $state = $this->stateRepository->getState($gameId);

        if (! $state) {
            throw TriviaGameException::gameNotFound();
        }

        return $state;
    }

    /**
     * Verificar si juego está activo.
     */
    public function isGameActive(string $gameId) : bool
    {
        $state = $this->stateRepository->getState($gameId);

        return $state && $state->isActive();
    }

    /**
     * Enviar siguiente pregunta.
     */
    public function sendNextQuestion(string $gameId, ?string $expectedQuestionId = null) : ?QuestionDTO
    {
        // Serialize advances across HTTP requests and timeout jobs. The expected
        // question ID makes the operation idempotent: a caller that waited for
        // another advance cannot accidentally skip the newly sent question.
        $lock = Cache::lock("trivia:game:{$gameId}:advance-question", 30);

        if (! $lock->get()) {
            return null;
        }

        try {
            if ($expectedQuestionId !== null) {
                $current = $this->stateRepository->getCurrentQuestion($gameId);

                if (! TriviaQuestionAdvanceGuard::matchesExpectedQuestion($expectedQuestionId, $current)) {
                    return null;
                }
            }

            return $this->sendNextQuestionLocked($gameId);
        } finally {
            $lock->release();
        }
    }

    /**
     * Iniciar un nuevo juego en una sala.
     */
    public function startGame(string $roomId, array $playerIds) : GameStateDTO
    {
        // lockForUpdate() serializa dos StartGameAction concurrentes sobre la
        // misma sala (doble clic del host, o el cron de scheduled_at
        // corriendo justo cuando el host también inicia manualmente): sin el
        // lock, ambas leían canStart()=true antes de que cualquiera creara
        // el TriviaGame, resultando en dos partidas 'playing' para la misma
        // sala y, más grave, en PrizeDistributionService::distribute()
        // ejecutándose dos veces al finalizar cada una.
        [$room, $game, $playerIds] = DB::connection('pgsql_trivia')->transaction(function() use ($roomId, $playerIds) {
            $room = TriviaRoom::lockForUpdate()->findOrFail($roomId);

            if (! $room->canStart()) {
                throw TriviaGameException::roomCannotStart();
            }

            // Snapshot authoritative participants only after acquiring the room lock.
            // JoinRoomAction takes the same lock, so registrations either commit before
            // this query and are included, or observe status=playing and are rejected.
            $registeredPlayerIds = DB::connection('pgsql_trivia')
                ->table('trivia_participants')
                ->where('room_id', $roomId)
                ->whereNull('deleted_at')
                ->whereIn('status', ['confirmed', 'registered'])
                ->orderBy('registered_at')
                ->pluck('user_id')
                ->map(fn ($id) => (string) $id)
                ->unique()
                ->values()
                ->all();

            // Production room starts have persisted participants and always use
            // that locked snapshot. Keep the explicit playerIds fallback for
            // legacy/direct GameEngineInterface callers that start synthetic games
            // without creating trivia_participants (including isolated unit tests).
            $playerIds = $registeredPlayerIds !== []
                ? $registeredPlayerIds
                : array_values(array_unique(array_map('strval', $playerIds)));

            if ($playerIds === []) {
                throw TriviaGameException::roomCannotStart();
            }

            // total_questions desde la configuración de la sala (banco global, sin room_id)
            $totalQuestions = max(1, (int) $room->question_count);

            // Crear juego en base de datos
            $game = TriviaGame::create([
                'room_id' => $roomId,
                'game_code' => strtoupper(Str::random(6)),
                'status' => 'playing',
                'total_questions' => $totalQuestions,
                'time_per_question' => $room->time_per_question,
                'player_count' => count($playerIds),
                'started_at' => now(),
            ]);

            // Actualizar sala dentro de la misma transacción/lock — evita que
            // otra request vea 'waiting' entre el commit del juego y este update.
            $room->update(['status' => 'playing', 'started_at' => now()]);

            return [$room, $game, $playerIds];
        });

        // Crear estado inicial en Redis
        $gameState = new GameStateDTO(
            gameId: (string) $game->id,
            roomId: $roomId,
            status: 'active',
            currentQuestionIndex: 0,
            totalQuestions: $game->total_questions,
            startTime: time(),
            endTime: null,
            playerIds: $playerIds,
            scores: [],
            metadata: [
                'game_code' => $game->game_code,
                'room_title' => $room->title,
                'last_activity' => time(),
            ],
        );

        $this->stateRepository->saveState($gameState->gameId, $gameState);

        // Inicializar puntuaciones
        foreach ($playerIds as $playerId) {
            $this->scoreService->updateScore($gameState->gameId, $playerId, 0);
        }

        // Emitir evento de dominio
        event(new GameStarted($gameState));

        // Broadcast WebSocket para que WaitingRoom redirija a /play/{gameId}
        try {
            broadcast(new TriviaGameStarted(
                roomId: $roomId,
                roomCode: $room->code,
                gameId: (string) $game->id,
                totalQuestions: $game->total_questions,
                timePerQuestion: $room->time_per_question,
            ));
        } catch (Throwable $e) {
            Log::warning('[GameEngine] broadcast TriviaGameStarted falló: ' . $e->getMessage());
        }

        return $gameState;
    }

    /**
     * Validar respuesta de jugador.
     */
    public function submitAnswer(
        string $gameId,
        string $playerId,
        string $answer,
        float $responseTimeMs,
    ) : AnswerResultDTO {
        $state = $this->stateRepository->getState($gameId);

        if (! $state || ! $state->isActive()) {
            throw TriviaGameException::gameNotActive();
        }

        // Obtener pregunta actual
        $questionDto = $this->getCurrentQuestion($gameId);

        if (! $questionDto) {
            throw TriviaGameException::noActiveQuestion();
        }

        // Rechazar respuesta duplicada ANTES de tocar cualquier estado
        // (Redis o Postgres). Antes de este chequeo, updateScore() (línea
        // más abajo) se ejecutaba primero y el INSERT en trivia_answers
        // (protegido por unique(game_id,player_id,question_id)) después —
        // un reintento de red o doble tap incrementaba el score en Redis y
        // luego fallaba en el INSERT con una excepción de BD sin capturar
        // específicamente, dejando el score con el incremento fantasma sin
        // rollback. Verificar aquí, antes de mutar nada, es más seguro que
        // revertir después de un fallo a mitad de camino.
        $alreadyAnswered = DB::connection('pgsql_trivia')
            ->table('trivia_answers')
            ->where('game_id', $gameId)
            ->where('player_id', $playerId)
            ->where('question_id', $questionDto->questionId)
            ->whereNull('deleted_at')
            ->exists();

        if ($alreadyAnswered) {
            throw TriviaGameException::alreadyAnswered();
        }

        // Validar respuesta
        $isCorrect = $questionDto->isCorrect($answer);
        $basePoints = $questionDto->points ?? 100;

        // Obtener puntuación actual
        $currentScoreDto = $this->scoreService->getPlayerScore($gameId, $playerId);
        $currentScore = $currentScoreDto->totalScore;
        $currentPosition = $currentScoreDto->position;

        // Crear resultado
        $result = AnswerResultDTO::create(
            gameId: $gameId,
            playerId: $playerId,
            questionId: $questionDto->questionId,
            submittedAnswer: $answer,
            isCorrect: $isCorrect,
            basePoints: $basePoints,
            responseTimeMs: $responseTimeMs,
            currentScore: $currentScore,
            currentPosition: $currentPosition,
            correctAnswer: $questionDto->correctAnswer,
        );

        // Actualizar puntuación
        $newScore = $currentScoreDto->addPoints(
            $result->pointsEarned,
            $isCorrect,
            $responseTimeMs,
        );

        $this->scoreService->updateScore($gameId, $playerId, $newScore->totalScore, [
            'correct_answers' => $newScore->correctAnswers,
            'wrong_answers' => $newScore->wrongAnswers,
            'streak' => $newScore->streak,
            'max_streak' => $newScore->maxStreak,
            'average_response_time_ms' => $newScore->averageResponseTimeMs,
        ]);

        // Marcar actividad reciente para que shouldAutoFinish() no cierre el
        // juego por timeout mientras los jugadores siguen respondiendo.
        $this->stateRepository->updateField(
            $gameId,
            'metadata',
            array_merge($state->metadata, ['last_activity' => time()]),
        );

        // Persistir respuesta en DB
        // (Se puede hacer async vía Job para no bloquear)
        \App\Domains\Games\Trivia\Models\TriviaAnswer::create([
            'game_id' => $gameId,
            'player_id' => $playerId,
            'question_id' => $questionDto->questionId,
            'selected_option_index' => (int) $answer,
            'is_correct' => $isCorrect,
            'points_earned' => $result->pointsEarned,
            'response_time_ms' => $responseTimeMs,
        ]);

        // Emitir eventos
        event(new \App\Domains\Games\Trivia\Events\AnswerSubmitted($result));

        // Actualizar leaderboard
        $leaderboard = $this->scoreService->getLeaderboard($gameId);
        event(new ScoreUpdated($leaderboard, $playerId));

        return $result;
    }

    /**
     * Construye el ranking final a partir de los datos reales en PostgreSQL
     * (trivia_scores + trivia_answers), ya que el gameplay real
     * (TriviaAnswerController) persiste ahí y no en el leaderboard de Redis.
     *
     * Orden anti-fraude: respuestas correctas desc → tiempo promedio asc →
     * racha máxima desc → inscripción más temprana asc (desempate final).
     * El último criterio existe solo para hacer el orden determinístico
     * cuando los tres primeros coinciden exactamente (posible con pocas
     * preguntas) — sin él, `usort()` no garantiza el mismo orden entre
     * ejecuciones para elementos "iguales", y PrizeDistributionService
     * paga por posición de array (`$finalRanks[$payout['rank'] - 1]`), así
     * que un orden no determinístico podía repartir el premio de 2°/3°
     * lugar de forma arbitraria entre jugadores con métricas idénticas.
     *
     * @return array<int, array{playerId: string, position: int, totalScore: int,
     *   correctAnswers: int, wrongAnswers: int, avgTimeMs: float, totalQuestions: int,
     *   totalResponseTimeMs: int, maxStreak: int}>
     */
    private function buildFinalLeaderboard(string $gameId, string $roomId, int $defaultTotalQuestions) : array
    {
        $scores = DB::connection('pgsql_trivia')
            ->table('trivia_scores')
            ->where('game_id', $gameId)
            ->get();

        if ($scores->isEmpty()) {
            return [];
        }

        // Rachas máximas calculadas desde el historial de respuestas, ordenado cronológicamente.
        $answers = DB::connection('pgsql_trivia')
            ->table('trivia_answers')
            ->where('game_id', $gameId)
            ->orderBy('created_at')
            ->get(['player_id', 'is_correct']);

        $maxStreaks = [];
        foreach ($answers->groupBy('player_id') as $playerId => $playerAnswers) {
            $streak = 0;
            $max = 0;
            foreach ($playerAnswers as $answer) {
                if ($answer->is_correct) {
                    $streak++;
                    $max = max($max, $streak);
                } else {
                    $streak = 0;
                }
            }
            $maxStreaks[$playerId] = $max;
        }

        // registered_at por jugador — único criterio de desempate final.
        $registeredAt = DB::connection('pgsql_trivia')
            ->table('trivia_participants')
            ->where('room_id', $roomId)
            ->pluck('registered_at', 'user_id');

        $ranked = $scores->map(function($row) use ($maxStreaks, $registeredAt, $defaultTotalQuestions) {
            $correctAnswers = (int) $row->correct_answers;
            $wrongAnswers = (int) $row->wrong_answers;
            $totalAnswered = $correctAnswers + $wrongAnswers;

            return [
                'playerId' => $row->player_id,
                'totalScore' => (int) $row->final_score,
                'correctAnswers' => $correctAnswers,
                'wrongAnswers' => $wrongAnswers,
                'avgTimeMs' => (float) $row->average_response_time_ms,
                'totalQuestions' => $totalAnswered > 0 ? $totalAnswered : $defaultTotalQuestions,
                'totalResponseTimeMs' => (int) round($row->average_response_time_ms * max(1, $totalAnswered)),
                'maxStreak' => $maxStreaks[$row->player_id] ?? (int) $row->max_streak,
                'registeredAt' => $registeredAt[$row->player_id] ?? null,
            ];
        })->all();

        usort($ranked, function($a, $b) {
            if ($b['correctAnswers'] !== $a['correctAnswers']) {
                return $b['correctAnswers'] <=> $a['correctAnswers'];
            }
            if ($a['avgTimeMs'] !== $b['avgTimeMs']) {
                return $a['avgTimeMs'] <=> $b['avgTimeMs'];
            }
            if ($b['maxStreak'] !== $a['maxStreak']) {
                return $b['maxStreak'] <=> $a['maxStreak'];
            }

            // Empate real en los 3 criterios de habilidad: desempata quien
            // se inscribió primero (dato ya disponible, no depende de azar).
            return ($a['registeredAt'] ?? '') <=> ($b['registeredAt'] ?? '');
        });

        return array_map(function($rank, $index) {
            unset($rank['registeredAt']);
            $rank['position'] = $index + 1;

            return $rank;
        }, $ranked, array_keys($ranked));
    }

    /**
     * Otorga XP y evalúa logros para cada participante de la partida.
     * position === 1 se considera victoria (ver TriviaScore::is_winner, misma regla).
     *
     * @param array<int, array{playerId: string, position: int}> $finalRanks
     */
    private function reportGameCompletion(array $finalRanks, string $roomId) : void
    {
        $xpForPlaying = $this->userLevelService->calculateXpReward('game_played');
        $xpForWinning = $this->userLevelService->calculateXpReward('game_won');

        foreach ($finalRanks as $rank) {
            $user = User::find((string) $rank['playerId']);
            if (! $user) {
                continue;
            }

            $won = $rank['position'] === 1;

            $this->gameCompletionService->reportParticipation(
                user: $user,
                game: GameParticipation::GAME_TRIVIA,
                won: $won,
                xp: $won ? $xpForWinning : $xpForPlaying,
                roomId: $roomId,
            );
        }
    }

    /**
     * Perform the state mutation while the per-game advance lock is held.
     */
    private function sendNextQuestionLocked(string $gameId) : ?QuestionDTO
    {
        $state = $this->stateRepository->getState($gameId);

        if (! $state || ! $state->isActive()) {
            return null;
        }

        // Verificar si hay más preguntas
        if ($state->currentQuestionIndex >= $state->totalQuestions) {
            $this->finishGame($gameId);

            return null;
        }

        // Avanzar índice
        $newState = $state->advanceQuestion();
        $this->stateRepository->saveState($gameId, $newState);

        // Obtener pregunta del banco global, evitando repetir preguntas ya enviadas.
        // Filtra por categoría de la sala si está configurada.
        $room = TriviaRoom::find($state->roomId);

        // IDs ya usados en este juego (guardados en metadata del estado)
        $usedIds = $state->metadata['used_question_ids'] ?? [];

        $query = TriviaQuestion::where('is_active', true)
            ->whereNotIn('id', $usedIds);

        if ($room?->category) {
            // Intenta con la categoría de la sala primero
            $questionWithCat = (clone $query)->where('category', $room->category)->inRandomOrder()->first();
            $question = $questionWithCat ?? $query->inRandomOrder()->first();
        } else {
            $question = $query->inRandomOrder()->first();
        }

        if (! $question) {
            // No quedan preguntas disponibles → terminar juego
            $this->finishGame($gameId);

            return null;
        }

        // Guardar el ID usado para no repetirlo
        $usedIds[] = (string) $question->id;
        $updatedMetadata = array_merge($state->metadata, [
            'used_question_ids' => $usedIds,
            'last_activity' => time(),
        ]);
        $newState = new \App\Domains\Games\Trivia\DTOs\GameStateDTO(
            gameId: $newState->gameId,
            roomId: $newState->roomId,
            status: $newState->status,
            currentQuestionIndex: $newState->currentQuestionIndex,
            totalQuestions: $newState->totalQuestions,
            startTime: $newState->startTime,
            endTime: $newState->endTime,
            playerIds: $newState->playerIds,
            scores: $newState->scores,
            metadata: $updatedMetadata,
        );
        $this->stateRepository->saveState($gameId, $newState);

        // Crear DTO desde el banco global (campos: question_text, options jsonb, correct_option_index, time_limit_seconds)
        $questionDto = new QuestionDTO(
            questionId: (string) $question->id,
            gameId: $gameId,
            questionNumber: $newState->currentQuestionIndex,
            questionText: $question->question_text,
            imageUrl: $question->image_url ?? null,
            options: is_array($question->options) ? $question->options : json_decode($question->options, true) ?? [],
            timeLimitSeconds: (int) ($question->time_limit_seconds ?? $room?->time_per_question ?? 20),
            correctAnswer: (string) ($question->correct_option_index ?? 0),
            points: (int) ($question->points ?? 100),
            serverTimestampMs: microtime(true) * 1000,
        );

        // Guardar en Redis
        $this->stateRepository->saveCurrentQuestion($gameId, $questionDto->toArray());

        // Broadcast WebSocket a todos en la sala
        try {
            broadcast(new \App\Events\Games\Trivia\TriviaQuestionSent(
                roomId: $state->roomId,
                gameId: $gameId,
                questionIndex: $newState->currentQuestionIndex,
                totalQuestions: $newState->totalQuestions,
                question: $questionDto->forClient(),
            ));
        } catch (Throwable $e) {
            Log::warning('[GameEngine] broadcast TriviaQuestionSent falló: ' . $e->getMessage());
        }

        // Evento de dominio (para listeners internos)
        event(new QuestionSent(question: $questionDto, gameId: $gameId, serverTimestamp: time()));

        // Job de seguridad: si no todos responden antes del timer, avanzar igual.
        // Delay = time_limit_seconds + 2 s de buffer.
        $delaySeconds = $questionDto->timeLimitSeconds + 2;
        AdvanceTriviaQuestion::dispatch($gameId, $questionDto->questionId)
            ->delay(now()->addSeconds($delaySeconds));

        // Detección de desconexión masiva: se evalúa DESPUÉS de registrar el
        // avance (used_question_ids ya incluye la pregunta que se acaba de
        // enviar) — no tiene efecto en la primera pregunta de la partida,
        // que nunca acumula suficiente historial para juzgar inactividad.
        $this->pauseService->maybeAutoPause($gameId, $state->roomId);

        return $questionDto;
    }
}
