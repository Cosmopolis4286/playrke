<?php

declare(strict_types=1);

namespace App\Domains\Trivia\Actions;

use App\Domains\Trivia\Contracts\GameEngineInterface;
use App\Domains\Trivia\Contracts\RoomServiceInterface;
use App\Domains\Trivia\DTOs\GameStateDTO;
use App\Domains\Trivia\DTOs\LeaderboardDTO;
use RuntimeException;

/**
 * Action: Finalizar Juego.
 * 
 * Caso de uso para terminar una partida.
 * Puede ser manual o automático.
 */
class FinishGameAction
{
    public function __construct(
        private readonly GameEngineInterface $gameEngine,
        private readonly RoomServiceInterface $roomService
    ) {}

    /**
     * Ejecutar la acción.
     * 
     * @param string $gameId ID del juego
     * @param string $requestedById ID del usuario que solicita el fin
     * @param string $reason Razón de finalización
     * @throws RuntimeException Si no se puede finalizar
     */
    public function execute(
        string $gameId,
        string $requestedById,
        string $reason = 'completed'
    ): array {
        // Obtener estado actual
        $state = $this->gameEngine->getGameState($gameId);

        // Validar permisos (host o sistema)
        $room = $this->roomService->getRoom($state->roomId);
        
        if ($room->hostId !== $requestedById) {
            // Permitir que el sistema finalice automáticamente
            if ($requestedById !== 'system') {
                throw new RuntimeException('Sin permisos para finalizar el juego');
            }
        }

        // Finalizar juego
        $finalState = $this->gameEngine->finishGame($gameId);

        // Obtener leaderboard final
        // Nota: En un caso real, esto vendría del evento GameFinished
        // o se obtiene del servicio de scores

        return [
            'game_state' => $finalState,
            'message' => 'Juego finalizado exitosamente',
            'reason' => $reason,
        ];
    }

    /**
     * Verificar si el juego debe finalizar automáticamente.
     * Útil para procesos en background.
     */
    public function shouldAutoFinish(string $gameId): bool
    {
        try {
            $state = $this->gameEngine->getGameState($gameId);
            
            // Finalizar si todas las preguntas fueron respondidas
            // o si pasó mucho tiempo sin actividad
            
            if ($state->currentQuestionIndex >= $state->totalQuestions) {
                return true;
            }

            // Timeout: 5 minutos sin actividad
            $lastActivity = $this->gameEngine->getGameState($gameId)->metadata['last_activity'] ?? 0;
            if ((time() - $lastActivity) > 300) {
                return true;
            }

            return false;
        } catch (RuntimeException) {
            return false;
        }
    }

    /**
     * Auto-finalizar si las condiciones lo permiten.
     */
    public function autoFinishIfNeeded(string $gameId): ?GameStateDTO
    {
        if ($this->shouldAutoFinish($gameId)) {
            $result = $this->execute($gameId, 'system', 'auto_completed');
            return $result['game_state'];
        }

        return null;
    }
}
