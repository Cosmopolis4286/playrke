<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Actions;

use App\Domains\Games\Trivia\Contracts\GameEngineInterface;
use App\Domains\Games\Trivia\Contracts\RoomServiceInterface;
use App\Domains\Games\Trivia\DTOs\GameStateDTO;
use RuntimeException;

/**
 * Action: Iniciar Juego.
 * 
 * Caso de uso independiente y reutilizable.
 * Valida precondiciones y orquesta el inicio.
 */
class StartGameAction
{
    public function __construct(
        private readonly GameEngineInterface $gameEngine,
        private readonly RoomServiceInterface $roomService
    ) {}

    /**
     * Ejecutar la acción.
     * 
     * @param string $roomId ID de la sala
     * @param string $hostId ID del anfitrión que inicia
     * @param array<string> $playerIds IDs de jugadores a incluir
     * @throws RuntimeException Si no se puede iniciar
     */
    public function execute(string $roomId, string $hostId, array $playerIds): GameStateDTO
    {
        // Validar que el host tiene permisos
        $room = $this->roomService->getRoom($roomId);
        
        if ($room->hostId !== $hostId) {
            throw new RuntimeException('Solo el anfitrión puede iniciar el juego');
        }

        // Validar mínimo de jugadores
        if (count($playerIds) < 1) {
            throw new RuntimeException('Se necesita al menos un jugador');
        }

        // Validar que todos los jugadores están en la sala
        foreach ($playerIds as $playerId) {
            if (!$this->roomService->isPlayerInRoom($roomId, $playerId)) {
                throw new RuntimeException("El jugador {$playerId} no está en la sala");
            }
        }

        // Iniciar juego
        $gameState = $this->gameEngine->startGame($roomId, $playerIds);

        // Enviar primera pregunta automáticamente
        $this->gameEngine->sendNextQuestion($gameState->gameId);

        return $gameState;
    }
}
