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
use App\Domains\Games\Trivia\Models\TriviaGame;
use App\Domains\Games\Trivia\Models\TriviaQuestion;
use App\Domains\Games\Trivia\Models\TriviaRoom;
use Illuminate\Support\Str;
use RuntimeException;

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
        private readonly RoomService $roomService
    ) {}

    /**
     * Iniciar un nuevo juego en una sala.
     */
    public function startGame(string $roomId, array $playerIds): GameStateDTO
    {
        // Validar sala
        $room = TriviaRoom::findOrFail($roomId);
        
        if (!$room->canStart()) {
            throw new RuntimeException('La sala no puede iniciar un juego en este momento');
        }

        // Crear juego en base de datos
        $game = TriviaGame::create([
            'room_id' => $roomId,
            'game_code' => strtoupper(Str::random(6)),
            'status' => 'active',
            'total_questions' => $room->questions()->count(),
            'player_count' => count($playerIds),
            'started_at' => now(),
        ]);

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
            ]
        );

        $this->stateRepository->saveState($gameState->gameId, $gameState);

        // Inicializar puntuaciones
        foreach ($playerIds as $playerId) {
            $this->scoreService->updateScore($gameState->gameId, $playerId, 0);
        }

        // Actualizar sala
        $room->update(['status' => 'playing', 'started_at' => now()]);

        // Emitir evento
        event(new GameStarted($gameState));

        return $gameState;
    }

    /**
     * Obtener pregunta actual.
     */
    public function getCurrentQuestion(string $gameId): ?QuestionDTO
    {
        $questionData = $this->stateRepository->getCurrentQuestion($gameId);
        
        if (!$questionData) {
            return null;
        }

        return QuestionDTO::fromArray($questionData);
    }

    /**
     * Enviar siguiente pregunta.
     */
    public function sendNextQuestion(string $gameId): ?QuestionDTO
    {
        $state = $this->stateRepository->getState($gameId);
        
        if (!$state || !$state->isActive()) {
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

        // Obtener pregunta de la base de datos
        $question = TriviaQuestion::where('room_id', $state->roomId)
            ->where('question_number', $newState->currentQuestionIndex)
            ->first();

        if (!$question) {
            throw new RuntimeException("Pregunta {$newState->currentQuestionIndex} no encontrada");
        }

        // Crear DTO
        $questionDto = QuestionDTO::fromModel($question->toArray(), $gameId);

        // Guardar en Redis
        $this->stateRepository->saveCurrentQuestion($gameId, $questionDto->toArray());

        // Emitir evento
        event(new QuestionSent(
            question: $questionDto,
            gameId: $gameId,
            serverTimestamp: time()
        ));

        return $questionDto;
    }

    /**
     * Validar respuesta de jugador.
     */
    public function submitAnswer(
        string $gameId,
        string $playerId,
        string $answer,
        float $responseTimeMs
    ): AnswerResultDTO {
        $state = $this->stateRepository->getState($gameId);
        
        if (!$state || !$state->isActive()) {
            throw new RuntimeException('Juego no activo');
        }

        // Obtener pregunta actual
        $questionDto = $this->getCurrentQuestion($gameId);
        
        if (!$questionDto) {
            throw new RuntimeException('No hay pregunta activa');
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
            correctAnswer: $questionDto->correctAnswer
        );

        // Actualizar puntuación
        $newScore = $currentScoreDto->addPoints(
            $result->pointsEarned,
            $isCorrect,
            $responseTimeMs
        );
        
        $this->scoreService->updateScore($gameId, $playerId, $newScore->totalScore, [
            'correct_answers' => $newScore->correctAnswers,
            'wrong_answers' => $newScore->wrongAnswers,
            'streak' => $newScore->streak,
            'max_streak' => $newScore->maxStreak,
            'average_response_time_ms' => $newScore->averageResponseTimeMs,
        ]);

        // Persistir respuesta en DB
        // (Se puede hacer async vía Job para no bloquear)
        \App\Domains\Games\Trivia\Models\TriviaAnswer::create([
            'game_id' => $gameId,
            'player_id' => $playerId,
            'question_id' => $questionDto->questionId,
            'submitted_answer' => $answer,
            'is_correct' => $isCorrect,
            'points_earned' => $result->pointsEarned,
            'response_time_ms' => $responseTimeMs,
            'answered_at' => now(),
        ]);

        // Emitir eventos
        event(new \App\Domains\Games\Trivia\Events\AnswerSubmitted($result));

        // Actualizar leaderboard
        $leaderboard = $this->scoreService->getLeaderboard($gameId);
        event(new ScoreUpdated($leaderboard, $playerId));

        return $result;
    }

    /**
     * Finalizar juego.
     */
    public function finishGame(string $gameId): GameStateDTO
    {
        $state = $this->stateRepository->getState($gameId);
        
        if (!$state) {
            throw new RuntimeException('Juego no encontrado');
        }

        if ($state->isFinished()) {
            return $state;
        }

        // Cambiar estado
        $finalState = $state->withStatus('finished');
        $this->stateRepository->saveState($gameId, $finalState);

        // Obtener leaderboard final
        $leaderboard = $this->scoreService->getLeaderboard($gameId);

        // Actualizar DB
        $game = TriviaGame::findOrFail($gameId);
        $game->finish($leaderboard->toArray());

        // Guardar scores finales en DB
        foreach ($leaderboard->scores as $scoreDto) {
            \App\Domains\Games\Trivia\Models\TriviaScore::create([
                'game_id' => $gameId,
                'player_id' => $scoreDto->playerId,
                'final_score' => $scoreDto->totalScore,
                'correct_answers' => $scoreDto->correctAnswers,
                'wrong_answers' => $scoreDto->wrongAnswers,
                'average_response_time_ms' => $scoreDto->averageResponseTimeMs,
                'position' => $scoreDto->position,
                'max_streak' => $scoreDto->maxStreak,
            ]);
        }

        // Actualizar sala
        TriviaRoom::where('id', $state->roomId)->update([
            'status' => 'closed',
            'ended_at' => now(),
        ]);

        // Obtener ganadores (top 3)
        $winners = array_slice(array_map(
            fn($s) => $s->playerId,
            $leaderboard->scores
        ), 0, 3);

        // Estadísticas del juego
        $statistics = [
            'total_players' => count($state->playerIds),
            'total_questions' => $state->totalQuestions,
            'duration_seconds' => time() - ($state->startTime ?? time()),
            'average_score' => array_sum(array_map(
                fn($s) => $s->totalScore,
                $leaderboard->scores
            )) / max(1, count($leaderboard->scores)),
        ];

        // Emitir evento
        event(new GameFinished(
            gameState: $finalState,
            finalLeaderboard: $leaderboard,
            winners: $winners,
            statistics: $statistics
        ));

        return $finalState;
    }

    /**
     * Verificar si juego está activo.
     */
    public function isGameActive(string $gameId): bool
    {
        $state = $this->stateRepository->getState($gameId);
        return $state && $state->isActive();
    }

    /**
     * Obtener estado actual.
     */
    public function getGameState(string $gameId): GameStateDTO
    {
        $state = $this->stateRepository->getState($gameId);
        
        if (!$state) {
            throw new RuntimeException('Juego no encontrado');
        }

        return $state;
    }

    /**
     * Cancelar juego.
     */
    public function cancelGame(string $gameId, string $reason): void
    {
        $state = $this->stateRepository->getState($gameId);
        
        if (!$state) {
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
        // (Implementar evento específico si es necesario)
    }
}
